<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$usuario = exigir_login();
$pdo = db();

$totalFuncionarios = (int) $pdo->query('SELECT COUNT(*) FROM funcionarios')->fetchColumn();
$totalProdutos = (int) $pdo->query('SELECT COUNT(*) FROM produtos')->fetchColumn();
$totalCategorias = (int) $pdo->query('SELECT COUNT(*) FROM categorias')->fetchColumn();
$recentes = (int) $pdo->query('SELECT COUNT(*) FROM produtos WHERE criado_em >= (NOW() - INTERVAL 7 DAY)')->fetchColumn();
$estoqueBaixo = (int) $pdo->query('SELECT COUNT(*) FROM produtos WHERE estoque <= 5')->fetchColumn();

$produtos = $pdo->query(
    'SELECT p.id, p.nome, p.preco, p.estoque, p.criado_em, c.nome AS categoria_nome,
            (
                SELECT i.caminho
                FROM produto_imagens i
                WHERE i.produto_id = p.id
                ORDER BY i.principal DESC, i.id ASC
                LIMIT 1
            ) AS imagem
     FROM produtos p
     INNER JOIN categorias c ON c.id = p.categoria_id
     ORDER BY p.criado_em DESC
     LIMIT 6'
)->fetchAll();

$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
require __DIR__ . '/includes/header.php';
?>
<section class="page-head">
    <div>
        <h1>Olá, <?= e(primeiro_nome($usuario['nome_completo'])) ?></h1>
        <p>Acompanhe a equipe, as categorias e o catálogo da Toy Mania.</p>
    </div>
</section>

<section class="stats">
    <a class="stat-card stat-turquesa" href="<?= e(base_url('funcionarios/index.php')) ?>">
        <span class="stat-card__icon"><?= icon('usuarios') ?></span>
        <span>
            <span class="stat-card__label">Funcionários cadastrados</span>
            <span class="stat-card__value"><?= $totalFuncionarios ?></span>
        </span>
    </a>
    <a class="stat-card stat-amarelo" href="<?= e(base_url('produtos/index.php')) ?>">
        <span class="stat-card__icon"><?= icon('caixa') ?></span>
        <span>
            <span class="stat-card__label">Produtos cadastrados</span>
            <span class="stat-card__value"><?= $totalProdutos ?></span>
            <?php if ($estoqueBaixo > 0): ?>
                <small><?= $estoqueBaixo ?> com estoque baixo</small>
            <?php endif; ?>
        </span>
    </a>
    <a class="stat-card stat-laranja" href="<?= e(base_url('categorias/index.php')) ?>">
        <span class="stat-card__icon"><?= icon('etiqueta') ?></span>
        <span>
            <span class="stat-card__label">Categorias</span>
            <span class="stat-card__value"><?= $totalCategorias ?></span>
        </span>
    </a>
    <a class="stat-card stat-turquesa" href="<?= e(base_url('produtos/index.php?ordem=recentes')) ?>">
        <span class="stat-card__icon"><?= icon('estrela') ?></span>
        <span>
            <span class="stat-card__label">Produtos cadastrados recentemente</span>
            <span class="stat-card__value"><?= $recentes ?></span>
            <small>nos últimos 7 dias</small>
        </span>
    </a>
</section>

<section class="card mt">
    <div class="page-head">
        <h2>Últimos produtos cadastrados</h2>
        <a class="btn btn-secondary btn-small" href="<?= e(base_url('produtos/index.php')) ?>">Ver todos</a>
    </div>
    <?php if (!$produtos): ?>
        <div class="empty">
            <?= icon('caixa') ?>
            <p>Nenhum brinquedo cadastrado ainda.</p>
            <p style="margin-top: 12px;"><a class="btn btn-primary" href="<?= e(base_url('produtos/criar.php')) ?>">Cadastrar produto</a></p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Produto</th>
                        <th>Categoria</th>
                        <th>Preço</th>
                        <th>Estoque</th>
                        <th>Cadastro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos as $produto): ?>
                        <tr>
                            <td><?= html_thumb($produto['imagem'], (string) $produto['nome']) ?></td>
                            <td><a href="<?= e(base_url('produtos/visualizar.php?id=' . (int) $produto['id'])) ?>"><?= e($produto['nome']) ?></a></td>
                            <td><?= e($produto['categoria_nome']) ?></td>
                            <td class="money"><?= e(dinheiro($produto['preco'])) ?></td>
                            <td class="<?= e(classe_estoque((int) $produto['estoque'])) ?>"><?= (int) $produto['estoque'] ?></td>
                            <td><?= e(data_br($produto['criado_em'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
