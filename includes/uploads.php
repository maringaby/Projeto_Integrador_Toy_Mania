<?php

declare(strict_types=1);

/**
 * Upload de fotos dos produtos.
 * O arquivo original não é guardado: a imagem é relida e gravada de novo
 * com a biblioteca GD. Isso descarta conteúdo escondido em arquivos falsos.
 */

function tipos_de_imagem(): array
{
    $tipos = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    if (function_exists('imagecreatefromwebp') && function_exists('imagewebp')) {
        $tipos['image/webp'] = 'webp';
    }

    return $tipos;
}

function normalizar_uploads(array $files): array
{
    if (!isset($files['name'])) {
        return [];
    }

    if (!is_array($files['name'])) {
        if ((int) ($files['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [];
        }

        return [[
            'name' => (string) $files['name'],
            'type' => (string) ($files['type'] ?? ''),
            'tmp_name' => (string) ($files['tmp_name'] ?? ''),
            'error' => (int) ($files['error'] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int) ($files['size'] ?? 0),
        ]];
    }

    $lista = [];
    foreach ($files['name'] as $indice => $nome) {
        $erro = (int) ($files['error'][$indice] ?? UPLOAD_ERR_NO_FILE);
        if ($erro === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $lista[] = [
            'name' => (string) $nome,
            'type' => (string) ($files['type'][$indice] ?? ''),
            'tmp_name' => (string) ($files['tmp_name'][$indice] ?? ''),
            'error' => $erro,
            'size' => (int) ($files['size'][$indice] ?? 0),
        ];
    }

    return $lista;
}

function mensagem_erro_upload(int $codigo): string
{
    return match ($codigo) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o tamanho permitido pelo servidor.',
        UPLOAD_ERR_PARTIAL => 'O envio de um arquivo foi interrompido. Tente novamente.',
        UPLOAD_ERR_NO_TMP_DIR => 'A pasta temporária do PHP não está disponível.',
        UPLOAD_ERR_CANT_WRITE => 'O servidor não conseguiu gravar o arquivo temporário.',
        UPLOAD_ERR_EXTENSION => 'Uma extensão do PHP bloqueou o arquivo.',
        default => 'Não foi possível enviar o arquivo.',
    };
}

function inspecionar_imagem(array $arquivo): ?string
{
    if (($arquivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return mensagem_erro_upload((int) $arquivo['error']);
    }

    if (!is_uploaded_file((string) $arquivo['tmp_name'])) {
        return 'O arquivo enviado não é um upload válido.';
    }

    if ((int) $arquivo['size'] <= 0 || (int) $arquivo['size'] > UPLOAD_MAX_BYTES) {
        return 'Cada imagem deve ter no máximo 2 MB.';
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file((string) $arquivo['tmp_name']);
    if ($mime === 'image/jpg') {
        $mime = 'image/jpeg';
    }

    $permitidos = tipos_de_imagem();
    if (!isset($permitidos[$mime])) {
        if ($mime === 'image/webp') {
            return 'Este servidor não consegue processar WEBP. Envie JPG ou PNG.';
        }

        return 'Envie apenas imagens JPG, JPEG, PNG ou WEBP.';
    }

    $info = @getimagesize((string) $arquivo['tmp_name']);
    if ($info === false) {
        return 'O arquivo não é uma imagem válida.';
    }

    $mapa = [
        IMAGETYPE_JPEG => 'image/jpeg',
        IMAGETYPE_PNG => 'image/png',
    ];
    if (defined('IMAGETYPE_WEBP')) {
        $mapa[IMAGETYPE_WEBP] = 'image/webp';
    }

    $tipoDetectado = $mapa[$info[2]] ?? '';
    if ($tipoDetectado === '' || $tipoDetectado !== $mime) {
        return 'O conteúdo do arquivo não corresponde a uma imagem permitida.';
    }

    $largura = (int) $info[0];
    $altura = (int) $info[1];
    if ($largura < 1 || $altura < 1 || $largura > 6000 || $altura > 6000) {
        return 'A imagem precisa ter entre 1 e 6000 pixels de cada lado.';
    }

    return null;
}

function validar_lista_uploads(array $arquivos, int $jaExistentes): array
{
    $erros = [];

    if ($arquivos === []) {
        return $erros;
    }

    if (!function_exists('imagecreatetruecolor')) {
        return ['A biblioteca GD do PHP é necessária para salvar as fotos com segurança.'];
    }

    if (count($arquivos) > UPLOAD_MAX_POR_ENVIO) {
        $erros[] = 'Envie no máximo ' . UPLOAD_MAX_POR_ENVIO . ' imagens por vez.';
    }

    if ($jaExistentes + count($arquivos) > PRODUTO_MAX_IMAGENS) {
        $erros[] = 'Cada produto pode ter no máximo ' . PRODUTO_MAX_IMAGENS . ' fotos.';
    }

    foreach ($arquivos as $arquivo) {
        $erro = inspecionar_imagem($arquivo);
        if ($erro !== null) {
            $erros[] = $erro . ' (' . (string) ($arquivo['name'] ?? 'arquivo') . ')';
        }
    }

    return $erros;
}

function garantir_pasta_uploads(): string
{
    $pasta = UPLOAD_DIR . '/produtos';
    if (!is_dir($pasta) && !mkdir($pasta, 0775, true) && !is_dir($pasta)) {
        throw new RuntimeException('Não foi possível criar a pasta uploads/produtos.');
    }

    if (!is_writable($pasta)) {
        throw new RuntimeException('A pasta uploads/produtos não tem permissão de escrita.');
    }

    return $pasta;
}

function corrigir_orientacao(GdImage $imagem, string $temporario): GdImage
{
    if (!function_exists('exif_read_data')) {
        return $imagem;
    }

    $exif = @exif_read_data($temporario);
    if (!is_array($exif)) {
        return $imagem;
    }

    $orientacao = (int) ($exif['Orientation'] ?? 1);
    $graus = match ($orientacao) {
        3 => 180,
        6 => -90,
        8 => 90,
        default => 0,
    };

    if ($graus === 0) {
        return $imagem;
    }

    $rotacionada = imagerotate($imagem, $graus, 0);
    if ($rotacionada instanceof GdImage) {
        imagedestroy($imagem);

        return $rotacionada;
    }

    return $imagem;
}

function reprocessar_imagem(string $temporario, string $destino, string $mime): bool
{
    $origem = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($temporario),
        'image/png' => @imagecreatefrompng($temporario),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($temporario) : false,
        default => false,
    };

    if (!$origem instanceof GdImage) {
        return false;
    }

    if ($mime === 'image/jpeg') {
        $origem = corrigir_orientacao($origem, $temporario);
        $ok = imagejpeg($origem, $destino, 85);
        imagedestroy($origem);

        return $ok;
    }

    if ($mime === 'image/png') {
        imagealphablending($origem, false);
        imagesavealpha($origem, true);
        $ok = imagepng($origem, $destino, 6);
        imagedestroy($origem);

        return $ok;
    }

    if ($mime === 'image/webp' && function_exists('imagewebp')) {
        $ok = imagewebp($origem, $destino, 85);
        imagedestroy($origem);

        return $ok;
    }

    imagedestroy($origem);

    return false;
}

function salvar_imagens(array $arquivos): array
{
    if ($arquivos === []) {
        return [];
    }

    $pasta = garantir_pasta_uploads();
    $permitidos = tipos_de_imagem();
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $salvos = [];

    try {
        foreach ($arquivos as $arquivo) {
            $erro = inspecionar_imagem($arquivo);
            if ($erro !== null) {
                throw new RuntimeException($erro);
            }

            $mime = (string) $finfo->file((string) $arquivo['tmp_name']);
            if ($mime === 'image/jpg') {
                $mime = 'image/jpeg';
            }

            $extensao = $permitidos[$mime];
            $nome = '';
            for ($tentativa = 0; $tentativa < 5; $tentativa++) {
                $candidato = bin2hex(random_bytes(16)) . '.' . $extensao;
                if (!is_file($pasta . '/' . $candidato)) {
                    $nome = $candidato;
                    break;
                }
            }

            if ($nome === '') {
                throw new RuntimeException('Não foi possível gerar um nome seguro para a imagem.');
            }

            $destino = $pasta . '/' . $nome;
            if (!reprocessar_imagem((string) $arquivo['tmp_name'], $destino, $mime)) {
                if (is_file($destino)) {
                    unlink($destino);
                }
                throw new RuntimeException('Não foi possível processar uma das imagens enviadas.');
            }

            $salvos[] = 'produtos/' . $nome;
        }
    } catch (Throwable $e) {
        foreach ($salvos as $caminho) {
            apagar_arquivo_upload($caminho);
        }

        throw $e;
    }

    return $salvos;
}

function apagar_arquivo_upload(string $relativo): void
{
    $relativo = ltrim(str_replace('\\', '/', $relativo), '/');
    if ($relativo === '' || str_contains($relativo, '..')) {
        return;
    }

    $base = realpath(UPLOAD_DIR);
    $completo = UPLOAD_DIR . '/' . $relativo;
    $diretorio = realpath(dirname($completo));

    if ($base === false || $diretorio === false || !str_starts_with($diretorio, $base)) {
        return;
    }

    if (is_file($completo)) {
        unlink($completo);
    }
}

function registrar_imagens(PDO $pdo, int $produtoId, array $caminhos): void
{
    if ($caminhos === []) {
        return;
    }

    $stmtPrincipal = $pdo->prepare(
        'SELECT COUNT(*) FROM produto_imagens WHERE produto_id = :id AND principal = 1'
    );
    $stmtPrincipal->execute(['id' => $produtoId]);
    $temPrincipal = (int) $stmtPrincipal->fetchColumn() > 0;

    $stmt = $pdo->prepare(
        'INSERT INTO produto_imagens (produto_id, caminho, principal)
         VALUES (:produto, :caminho, :principal)'
    );

    foreach ($caminhos as $indice => $caminho) {
        $stmt->execute([
            'produto' => $produtoId,
            'caminho' => $caminho,
            'principal' => (!$temPrincipal && $indice === 0) ? 1 : 0,
        ]);
    }

    $pdo->prepare('UPDATE produtos SET atualizado_em = NOW() WHERE id = :id')->execute(['id' => $produtoId]);
}
