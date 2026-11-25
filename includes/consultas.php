<?php

declare(strict_types=1);

function buscar_funcionario(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT id, nome_completo, email, telefone, cargo, status, criado_em, atualizado_em
         FROM funcionarios
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $id]);
    $registro = $stmt->fetch();

    return $registro ?: null;
}

function buscar_categoria(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT id, nome, descricao, status, criado_em, atualizado_em
         FROM categorias
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $id]);
    $registro = $stmt->fetch();

    return $registro ?: null;
}

function buscar_produto(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT p.*, c.nome AS categoria_nome, c.status AS categoria_status,
                f.nome_completo AS funcionario_nome
         FROM produtos p
         INNER JOIN categorias c ON c.id = p.categoria_id
         INNER JOIN funcionarios f ON f.id = p.funcionario_id
         WHERE p.id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $id]);
    $registro = $stmt->fetch();

    return $registro ?: null;
}

function imagens_do_produto(int $produtoId): array
{
    $stmt = db()->prepare(
        'SELECT id, produto_id, caminho, principal, criado_em
         FROM produto_imagens
         WHERE produto_id = :id
         ORDER BY principal DESC, id ASC'
    );
    $stmt->execute(['id' => $produtoId]);

    return $stmt->fetchAll();
}

function categorias_para_select(?int $categoriaAtual = null): array
{
    if ($categoriaAtual !== null && $categoriaAtual > 0) {
        $stmt = db()->prepare(
            "SELECT id, nome, status
             FROM categorias
             WHERE status = 'ativo' OR id = :id
             ORDER BY nome ASC"
        );
        $stmt->execute(['id' => $categoriaAtual]);
    } else {
        $stmt = db()->query(
            "SELECT id, nome, status
             FROM categorias
             WHERE status = 'ativo'
             ORDER BY nome ASC"
        );
    }

    return $stmt->fetchAll();
}

function contar_produtos_da_categoria(int $categoriaId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM produtos WHERE categoria_id = :id');
    $stmt->execute(['id' => $categoriaId]);

    return (int) $stmt->fetchColumn();
}

function contar_produtos_do_funcionario(int $funcionarioId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM produtos WHERE funcionario_id = :id');
    $stmt->execute(['id' => $funcionarioId]);

    return (int) $stmt->fetchColumn();
}
