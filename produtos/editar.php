<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

exigir_login();
$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
$produto = buscar_produto($id);

if (!$produto) {
    flash('erro', 'Produto não encontrado.');
    redirect('produtos/index.php');
}

$categorias = categorias_para_select((int) $produto['categoria_id']);
$form = [
    'nome' => (string) $produto['nome'],
    'descricao' => (string) $produto['descricao'],
    'categoria_id' => (string) $produto['categoria_id'],
    'preco' => number_format((float) $produto['preco'], 2, ',', ''),
    'faixa_etaria' => (string) $produto['faixa_etaria'],
    'estoque' => (string) $produto['estoque'],
    'marca' => (string) ($produto['marca'] ?? ''),
    'codigo' => (string) $produto['codigo'],
    'status' => (string) $produto['status'],
];
$erros = [];
$rotuloArquivos = 'Adicionar novas fotos';
$imagens = imagens_do_produto($id);
$produtoId = $id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    foreach (['nome', 'descricao', 'categoria_id', 'preco', 'faixa_etaria', 'estoque', 'marca', 'codigo', 'status'] as $campo) {
        $form[$campo] = trim((string) ($_POST[$campo] ?? ''));
    }

    if (envio_excedeu_limite()) {
        $erros['imagens'] = 'O envio excedeu o limite do servidor. Envie menos fotos ou reduza o tamanho.';
        $arquivos = [];
    } else {
        $arquivos = normalizar_uploads($_FILES['imagens'] ?? []);
    }

    $resultado = validar_produto($_POST, $id, (int) $produto['categoria_id']);
    $erros = array_merge($resultado['erros'], $erros);
    $errosUpload = validar_lista_uploads($arquivos, count($imagens));
    if ($errosUpload) {
        $erros['imagens'] = implode(' ', $errosUpload);
    }

    if (!$erros) {
        $dados = $resultado['dados'];
        $pdo = db();
        $caminhos = [];
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'UPDATE produtos SET
                    nome = :nome,
                    descricao = :descricao,
                    categoria_id = :categoria_id,
                    preco = :preco,
                    faixa_etaria = :faixa_etaria,
                    estoque = :estoque,
                    marca = :marca,
                    codigo = :codigo,
                    status = :status
                 WHERE id = :id'
            );
            $stmt->execute([
                'nome' => $dados['nome'],
                'descricao' => $dados['descricao'],
                'categoria_id' => $dados['categoria_id'],
                'preco' => $dados['preco'],
                'faixa_etaria' => $dados['faixa_etaria'],
                'estoque' => $dados['estoque'],
                'marca' => $dados['marca'],
                'codigo' => $dados['codigo'],
                'status' => $dados['status'],
                'id' => $id,
            ]);
            $caminhos = salvar_imagens($arquivos);
            registrar_imagens($pdo, $id, $caminhos);
            $pdo->commit();
            flash('sucesso', 'Produto atualizado com sucesso.');
            redirect('produtos/visualizar.php?id=' . $id);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($caminhos as $caminho) {
                apagar_arquivo_upload($caminho);
            }
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                $erros['codigo'] = 'Este código já está em uso.';
            } else {
                throw $e;
            }
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($caminhos as $caminho) {
                apagar_arquivo_upload($caminho);
            }
            $erros['imagens'] = $e->getMessage();
        }
    }
}

$pageTitle = 'Editar produto';
$activeMenu = 'produtos';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-head">
    <div>
        <h1>Editar produto</h1>
        <p>Responsável pelo cadastro: <?= e($produto['funcionario_nome']) ?>.</p>
    </div>
</section>

<section class="card">
    <?php if ($erros): ?><div class="alert alert-erro">Revise os campos destacados antes de salvar.</div><?php endif; ?>
    <form method="post" action="" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <?php require dirname(__DIR__) . '/includes/form-produto.php'; ?>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Salvar alterações</button>
            <a class="btn btn-ghost" href="<?= e(base_url('produtos/visualizar.php?id=' . $id)) ?>">Cancelar</a>
        </div>
    </form>
</section>

<section class="card mt">
    <h2>Fotos atuais</h2>
    <?php
    $retornoGaleria = 'produtos/editar.php?id=' . $id;
    require dirname(__DIR__) . '/includes/galeria-produto.php';
    ?>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
