<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

exigir_login();

$busca = trim((string) ($_GET['busca'] ?? ''));
$pagina = max(1, (int) ($_GET['page'] ?? 1));
$porPagina = 10;
$where = '';
$params = [];

if ($busca !== '') {
    $where = 'WHERE c.nome LIKE :busca';
    $params['busca'] = like($busca);
}

$totalStmt = db()->prepare("SELECT COUNT(*) FROM categorias c $where");
$totalStmt->execute($params);
$total = (int) $totalStmt->fetchColumn();
$totalPaginas = max(1, (int) ceil($total / $porPagina));
if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
}
$offset = ($pagina - 1) * $porPagina;

$sql = "SELECT c.id, c.nome, c.descricao, c.status, c.criado_em,
               (SELECT COUNT(*) FROM produtos p WHERE p.categoria_id = c.id) AS total_produtos
        FROM categorias c
        $where
        ORDER BY c.nome ASC
        LIMIT $porPagina OFFSET $offset";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$categorias = $stmt->fetchAll();
$paramsPaginacao = $busca !== '' ? ['busca' => $busca] : [];
$retorno = url_relativa_atual();

$pageTitle = 'Categorias';
$activeMenu = 'categorias';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-head">
    <div>
        <h1>Categorias</h1>
        <p>Organize o catálogo de brinquedos da loja.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(base_url('categorias/criar.php')) ?>"><?= icon('mais') ?> Nova categoria</a>
</section>

<section class="card stack">
    <form class="search-bar" method="get" action="">
        <label class="sr-only" for="busca">Pesquisar categoria</label>
        <input id="busca" name="busca" value="<?= e($busca) ?>" placeholder="Pesquisar por nome">
        <button class="btn btn-secondary" type="submit"><?= icon('busca') ?> Pesquisar</button>
        <?php if ($busca !== ''): ?>
            <a class="btn btn-ghost" href="<?= e(base_url('categorias/index.php')) ?>">Limpar</a>
        <?php endif; ?>
    </form>

    <?php if (!$categorias): ?>
        <div class="empty">
            <?= icon('etiqueta') ?>
            <p><?= $busca !== '' ? 'Nenhuma categoria encontrada.' : 'Nenhuma categoria cadastrada.' ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Produtos</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categorias as $categoria): ?>
                        <tr>
                            <td><?= e($categoria['nome']) ?></td>
                            <td><?= e((string) ($categoria['descricao'] ?: '—')) ?></td>
                            <td><?= (int) $categoria['total_produtos'] ?></td>
                            <td><?= badge_status((string) $categoria['status']) ?></td>
                            <td>
                                <div class="actions">
                                    <a class="btn-icon" href="<?= e(base_url('categorias/editar.php?id=' . (int) $categoria['id'])) ?>" title="Editar" aria-label="<?= e('Editar ' . $categoria['nome']) ?>"><?= icon('lapis') ?></a>
                                    <form method="post" action="<?= e(base_url('categorias/status.php')) ?>" <?= $categoria['status'] === 'ativo' ? 'data-confirm="Deseja desativar esta categoria?"' : '' ?>>
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $categoria['id'] ?>">
                                        <input type="hidden" name="retorno" value="<?= e($retorno) ?>">
                                        <button class="btn btn-small btn-ghost" type="submit"><?= $categoria['status'] === 'ativo' ? 'Desativar' : 'Ativar' ?></button>
                                    </form>
                                    <a class="btn-icon btn-icon-danger"
                                       href="<?= e(base_url('categorias/excluir.php?id=' . (int) $categoria['id'])) ?>"
                                       data-post="1"
                                       data-id="<?= (int) $categoria['id'] ?>"
                                       data-confirm="Deseja excluir esta categoria?"
                                       title="Excluir"
                                       aria-label="<?= e('Excluir ' . $categoria['nome']) ?>"><?= icon('lixeira') ?></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= html_paginacao($pagina, $totalPaginas, 'categorias/index.php', $paramsPaginacao) ?>
    <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
