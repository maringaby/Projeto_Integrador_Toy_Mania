<?php

declare(strict_types=1);

/**
 * Validação no servidor. O JavaScript do navegador apenas ajuda;
 * estas regras é que decidem se o dado pode ser gravado.
 */

function validar_funcionario(array $entrada, bool $senhaObrigatoria, ?int $ignorarId = null): array
{
    $nome = preg_replace('/\s+/u', ' ', trim((string) ($entrada['nome_completo'] ?? ''))) ?? '';
    $email = strtolower(trim((string) ($entrada['email'] ?? '')));
    $telefone = trim((string) ($entrada['telefone'] ?? ''));
    $telefone = preg_replace('/[^0-9()+\-\s]/', '', $telefone) ?? '';
    $cargo = preg_replace('/\s+/u', ' ', trim((string) ($entrada['cargo'] ?? ''))) ?? '';
    $status = (string) ($entrada['status'] ?? 'ativo');
    $senha = (string) ($entrada['senha'] ?? '');
    $confirmar = (string) ($entrada['senha_confirmacao'] ?? '');
    $erros = [];

    if (mb_strlen($nome) < 3 || mb_strlen($nome) > 150) {
        $erros['nome_completo'] = 'Informe o nome completo (entre 3 e 150 caracteres).';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 180) {
        $erros['email'] = 'Informe um e-mail válido.';
    } else {
        $sql = 'SELECT id FROM funcionarios WHERE email = :email';
        $params = ['email' => $email];
        if ($ignorarId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $ignorarId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetch()) {
            $erros['email'] = 'Este e-mail já está cadastrado.';
        }
    }

    if ($telefone !== '') {
        $digitos = preg_replace('/\D/', '', $telefone) ?? '';
        if (strlen($digitos) < 10 || strlen($digitos) > 13) {
            $erros['telefone'] = 'Informe um telefone válido com DDD.';
        }
    }

    if (mb_strlen($cargo) < 2 || mb_strlen($cargo) > 80) {
        $erros['cargo'] = 'Informe o cargo (entre 2 e 80 caracteres).';
    }

    if (!in_array($status, ['ativo', 'inativo'], true)) {
        $erros['status'] = 'Selecione um status válido.';
        $status = 'ativo';
    }

    $senhaHash = null;
    if ($senhaObrigatoria || $senha !== '' || $confirmar !== '') {
        if (strlen($senha) < 6) {
            $erros['senha'] = 'A senha deve ter pelo menos 6 caracteres.';
        }
        if (!hash_equals($senha, $confirmar)) {
            $erros['senha_confirmacao'] = 'A confirmação de senha não confere.';
        }
        if (!isset($erros['senha']) && !isset($erros['senha_confirmacao']) && $senha !== '') {
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        }
    }

    return [
        'erros' => $erros,
        'dados' => [
            'nome_completo' => $nome,
            'email' => $email,
            'telefone' => $telefone !== '' ? $telefone : null,
            'cargo' => $cargo,
            'status' => $status,
            'senha' => $senhaHash,
        ],
    ];
}

function validar_categoria(array $entrada, ?int $ignorarId = null): array
{
    $nome = preg_replace('/\s+/u', ' ', trim((string) ($entrada['nome'] ?? ''))) ?? '';
    $descricao = trim((string) ($entrada['descricao'] ?? ''));
    $status = (string) ($entrada['status'] ?? 'ativo');
    $erros = [];

    if (mb_strlen($nome) < 2 || mb_strlen($nome) > 100) {
        $erros['nome'] = 'Informe o nome da categoria (entre 2 e 100 caracteres).';
    } else {
        $sql = 'SELECT id FROM categorias WHERE nome = :nome';
        $params = ['nome' => $nome];
        if ($ignorarId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $ignorarId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetch()) {
            $erros['nome'] = 'Já existe uma categoria com este nome.';
        }
    }

    if (mb_strlen($descricao) > 2000) {
        $erros['descricao'] = 'A descrição deve ter no máximo 2000 caracteres.';
    }

    if (!in_array($status, ['ativo', 'inativo'], true)) {
        $erros['status'] = 'Selecione um status válido.';
        $status = 'ativo';
    }

    return [
        'erros' => $erros,
        'dados' => [
            'nome' => $nome,
            'descricao' => $descricao !== '' ? $descricao : null,
            'status' => $status,
        ],
    ];
}

function validar_produto(array $entrada, ?int $ignorarId = null, ?int $categoriaAtual = null): array
{
    $nome = preg_replace('/\s+/u', ' ', trim((string) ($entrada['nome'] ?? ''))) ?? '';
    $descricao = trim((string) ($entrada['descricao'] ?? ''));
    $categoriaId = (int) ($entrada['categoria_id'] ?? 0);
    $precoBruto = trim((string) ($entrada['preco'] ?? ''));
    $faixa = (string) ($entrada['faixa_etaria'] ?? '');
    $estoqueBruto = trim((string) ($entrada['estoque'] ?? ''));
    $marca = preg_replace('/\s+/u', ' ', trim((string) ($entrada['marca'] ?? ''))) ?? '';
    $codigo = trim((string) ($entrada['codigo'] ?? ''));
    $status = (string) ($entrada['status'] ?? 'ativo');
    $erros = [];
    $estoque = 0;
    $preco = null;

    if (mb_strlen($nome) < 3 || mb_strlen($nome) > 180) {
        $erros['nome'] = 'Informe o nome do produto (entre 3 e 180 caracteres).';
    }

    if (mb_strlen($descricao) < 10 || mb_strlen($descricao) > 5000) {
        $erros['descricao'] = 'Descreva o produto com pelo menos 10 caracteres.';
    }

    if ($categoriaId <= 0) {
        $erros['categoria_id'] = 'Selecione uma categoria.';
    } else {
        $stmt = db()->prepare('SELECT id, status FROM categorias WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $categoriaId]);
        $categoria = $stmt->fetch();
        if (!$categoria) {
            $erros['categoria_id'] = 'A categoria selecionada não existe.';
        } elseif ($categoria['status'] !== 'ativo' && $categoriaId !== (int) $categoriaAtual) {
            $erros['categoria_id'] = 'Escolha uma categoria ativa.';
        }
    }

    $normalizado = str_replace(' ', '', $precoBruto);
    if (str_contains($normalizado, ',') && str_contains($normalizado, '.')) {
        $normalizado = str_replace('.', '', $normalizado);
        $normalizado = str_replace(',', '.', $normalizado);
    } elseif (str_contains($normalizado, ',')) {
        $normalizado = str_replace(',', '.', $normalizado);
    }

    if ($normalizado === '' || !is_numeric($normalizado)) {
        $erros['preco'] = 'Informe um preço válido. Exemplo: 59,90.';
    } else {
        $preco = round((float) $normalizado, 2);
        if ($preco <= 0 || $preco > 99999999.99) {
            $erros['preco'] = 'Informe um preço maior que zero.';
            $preco = null;
        }
    }

    if (!isset(faixas_etarias()[$faixa])) {
        $erros['faixa_etaria'] = 'Selecione a faixa etária recomendada.';
    }

    if ($estoqueBruto === '' || !preg_match('/^\d+$/', $estoqueBruto)) {
        $erros['estoque'] = 'Informe a quantidade em estoque, usando apenas números.';
    } else {
        $estoque = (int) $estoqueBruto;
        if ($estoque > 999999) {
            $erros['estoque'] = 'A quantidade informada é muito alta.';
        }
    }

    if (mb_strlen($marca) > 100) {
        $erros['marca'] = 'A marca deve ter no máximo 100 caracteres.';
    }

    if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9\-_]{2,59}$/', $codigo)) {
        $erros['codigo'] = 'Use um código de 3 a 60 caracteres: letras, números, hífen ou _.';
    } else {
        $sql = 'SELECT id FROM produtos WHERE codigo = :codigo';
        $params = ['codigo' => $codigo];
        if ($ignorarId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $ignorarId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetch()) {
            $erros['codigo'] = 'Este código já está em uso.';
        }
    }

    if (!in_array($status, ['ativo', 'inativo'], true)) {
        $erros['status'] = 'Selecione um status válido.';
        $status = 'ativo';
    }

    return [
        'erros' => $erros,
        'dados' => [
            'nome' => $nome,
            'descricao' => $descricao,
            'categoria_id' => $categoriaId,
            'preco' => $preco !== null ? number_format($preco, 2, '.', '') : '',
            'faixa_etaria' => $faixa,
            'estoque' => $estoque,
            'marca' => $marca !== '' ? $marca : null,
            'codigo' => $codigo,
            'status' => $status,
        ],
    ];
}
