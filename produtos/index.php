<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

exigir_login();

$busca = trim((string) ($_GET['busca'] ?? ''));
$categoria = (int) ($_GET['categoria'] ?? 0);
$faixa = (string) ($_GET['faixa'] ?? '');
$status = (string) ($_GET['status'] ?? '');
$ordem = (string) ($_GET['ordem'] ?? 'recentes');
$pagina = max(1, (int) ($_GET['page'] ?? 1));
$porPagina = 8;

$ordens = [
    'recentes' => 'p.criado_em DESC',
    'preco_asc' => 'p.preco ASC, p.nome ASC',
    'preco_desc' => 'p.preco DESC, p.nome ASC',
];
if (!isset($ordens[$ordem])) {
    $ordem = 'recentes';
}

$where = [];
$params = [];

if ($busca !== '') {
    $where[] = 'p.nome LIKE :busca';
    $params['busca'] = like($busca);
}
if ($categoria > 0) {
    $where[] = 'p.categoria_id = :categoria';
    $params['categoria'] = $categoria;
}
if (isset(faixas_etarias()[$faixa])) {
    $where[] = 'p.faixa_etaria = :faixa';
    $params['faixa'] = $faixa;
} else {
    $faixa = '';
}
if (in_array($status, ['ativo', 'inativo'], true)) {
    $where[] = 'p.status = :status';
    $params['status'] = $status;
} else {
    $status = '';
}

$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$totalStmt = db()->prepare("SELECT COUNT(*) FROM produtos p $sqlWhere");
$totalStmt->execute($params);
$total = (int) $totalStmt->fetchColumn();
$totalPaginas = max(1, (int) ceil($total / $porPagina));
if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
}
$offset = ($pagina - 1) * $porPagina;

$sql = "SELECT p.id, p.nome, p.preco, p.estoque, p.faixa_etaria, p.status, p.codigo,
               c.nome AS categoria_nome, f.nome_completo AS funcionario_nome,
               (
                   SELECT i.caminho
                   FROM produto_imagens i
                   WHERE i.produto_id = p.id
                   ORDER BY i.principal DESC, i.id ASC
                   LIMIT 1
               ) AS imagem
        FROM produtos p
        INNER JOIN categorias c ON c.id = p.categoria_id
        INNER JOIN funcionarios f ON f.id = p.funcionario_id
        $sqlWhere
        ORDER BY {$ordens[$ordem]}
        LIMIT $porPagina OFFSET $offset";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$produtos = $stmt->fetchAll();

$categorias = db()->query('SELECT id, nome FROM categorias ORDER BY nome ASC')->fetchAll();
$paramsPaginacao = [];
if ($busca !== '') {
    $paramsPaginacao['busca'] = $busca;
}
if ($categoria > 0) {
    $paramsPaginacao['categoria'] = $categoria;
}
if ($faixa !== '') {
    $paramsPaginacao['faixa'] = $faixa;
}
if ($status !== '') {
    $paramsPaginacao['status'] = $status;
}
if ($ordem !== 'recentes') {
    $paramsPaginacao['ordem'] = $ordem;
}
$retorno = url_relativa_atual();
$temFiltro = $paramsPaginacao !== [];

$pageTitle = 'Produtos';
$activeMenu = 'produtos';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-head">
    <div>
        <h1>Produtos</h1>
        <p>Cadastre brinquedos, controle o estoque e organize as fotos.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(base_url('produtos/criar.php')) ?>"><?= icon('mais') ?> Novo produto</a>
</section>

<section class="card stack">
    <form method="get" action="">
        <div class="filters">
            <div class="field">
                <label for="busca">Nome</label>
                <input id="busca" name="busca" value="<?= e($busca) ?>" placeholder="Pesquisar por nome">
            </div>
            <div class="field">
                <label for="categoria">Categoria</label>
                <select id="categoria" name="categoria">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $item): ?>
                        <option value="<?= (int) $item['id'] ?>" <?= $categoria === (int) $item['id'] ? 'selected' : '' ?>><?= e($item['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="faixa">Faixa etária</label>
                <select id="faixa" name="faixa">
                    <option value="">Todas</option>
                    <?php foreach (faixas_etarias() as $chave => $rotulo): ?>
                        <option value="<?= e($chave) ?>" <?= $faixa === $chave ? 'selected' : '' ?>><?= e($rotulo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Todos</option>
                    <option value="ativo" <?= $status === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                    <option value="inativo" <?= $status === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                </select>
            </div>
            <div class="field">
                <label for="ordem">Ordenação</label>
                <select id="ordem" name="ordem">
                    <option value="recentes" <?= $ordem === 'recentes' ? 'selected' : '' ?>>Mais recentes</option>
                    <option value="preco_asc" <?= $ordem === 'preco_asc' ? 'selected' : '' ?>>Menor preço</option>
                    <option value="preco_desc" <?= $ordem === 'preco_desc' ? 'selected' : '' ?>>Maior preço</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button class="btn btn-secondary" type="submit"><?= icon('busca') ?> Filtrar</button>
            <?php if ($temFiltro): ?>
                <a class="btn btn-ghost" href="<?= e(base_url('produtos/index.php')) ?>">Limpar</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (!$produtos): ?>
        <div class="empty">
            <?= icon('caixa') ?>
            <p><?= $temFiltro ? 'Nenhum produto encontrado com esses filtros.' : 'Nenhum produto cadastrado ainda.' ?></p>
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
                        <th class="hide-md">Faixa</th>
                        <th>Status</th>
                        <th class="hide-md">Responsável</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos as $produto): ?>
                        <tr>
                            <td><?= html_thumb($produto['imagem'], (string) $produto['nome']) ?></td>
                            <td>
                                <a href="<?= e(base_url('produtos/visualizar.php?id=' . (int) $produto['id'])) ?>"><?= e($produto['nome']) ?></a>
                                <div class="code"><?= e($produto['codigo']) ?></div>
                            </td>
                            <td><?= e($produto['categoria_nome']) ?></td>
                            <td class="money"><?= e(dinheiro($produto['preco'])) ?></td>
                            <td class="<?= e(classe_estoque((int) $produto['estoque'])) ?>"><?= (int) $produto['estoque'] ?></td>
                            <td class="hide-md"><?= e(rotulo_faixa((string) $produto['faixa_etaria'])) ?></td>
                            <td><?= badge_status((string) $produto['status']) ?></td>
                            <td class="hide-md"><?= e($produto['funcionario_nome']) ?></td>
                            <td>
                                <div class="actions">
                                    <a class="btn-icon" href="<?= e(base_url('produtos/visualizar.php?id=' . (int) $produto['id'])) ?>" title="Visualizar" aria-label="<?= e('Visualizar ' . $produto['nome']) ?>"><?= icon('olho') ?></a>
                                    <a class="btn-icon" href="<?= e(base_url('produtos/editar.php?id=' . (int) $produto['id'])) ?>" title="Editar" aria-label="<?= e('Editar ' . $produto['nome']) ?>"><?= icon('lapis') ?></a>
                                    <form method="post" action="<?= e(base_url('produtos/status.php')) ?>" <?= $produto['status'] === 'ativo' ? 'data-confirm="Deseja desativar este produto?"' : '' ?>>
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $produto['id'] ?>">
                                        <input type="hidden" name="retorno" value="<?= e($retorno) ?>">
                                        <button class="btn btn-small btn-ghost" type="submit"><?= $produto['status'] === 'ativo' ? 'Desativar' : 'Ativar' ?></button>
                                    </form>
                                    <a class="btn-icon btn-icon-danger"
                                       href="<?= e(base_url('produtos/excluir.php?id=' . (int) $produto['id'])) ?>"
                                       data-post="1"
                                       data-id="<?= (int) $produto['id'] ?>"
                                       data-confirm="Deseja excluir este produto e as fotos vinculadas?"
                                       title="Excluir"
                                       aria-label="<?= e('Excluir ' . $produto['nome']) ?>"><?= icon('lixeira') ?></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= html_paginacao($pagina, $totalPaginas, 'produtos/index.php', $paramsPaginacao) ?>
    <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
