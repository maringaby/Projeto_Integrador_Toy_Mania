<?php

declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/includes/bootstrap.php';

if (!banco_tem_tabelas()) {
    exibir_instalacao();
}
$clienteLogado = null;

if (!empty($_SESSION['cliente_id'])) {
    $stmtCliente = db()->prepare(
        'SELECT id, nome, email, telefone
         FROM clientes
         WHERE id = :id
         LIMIT 1'
    );

    $stmtCliente->execute([
        'id' => (int) $_SESSION['cliente_id']
    ]);

    $clienteLogado = $stmtCliente->fetch();

    if (!$clienteLogado) {
        unset($_SESSION['cliente_id']);
    }
}

$busca = trim((string) ($_GET['busca'] ?? ''));
$categoria = (int) ($_GET['categoria'] ?? 0);
$faixa = (string) ($_GET['faixa'] ?? '');
$ordem = (string) ($_GET['ordem'] ?? 'recentes');
$pagina = max(1, (int) ($_GET['page'] ?? 1));
$porPagina = 12;

$ordens = [
    'recentes' => 'p.criado_em DESC',
    'preco_asc' => 'p.preco ASC, p.nome ASC',
    'preco_desc' => 'p.preco DESC, p.nome ASC',
];
if (!isset($ordens[$ordem])) {
    $ordem = 'recentes';
}

$where = ["p.status = 'ativo'", "c.status = 'ativo'"];
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

$sqlWhere = 'WHERE ' . implode(' AND ', $where);
$totalStmt = db()->prepare(
    "SELECT COUNT(*)
     FROM produtos p
     INNER JOIN categorias c ON c.id = p.categoria_id
     $sqlWhere"
);
$totalStmt->execute($params);
$total = (int) $totalStmt->fetchColumn();
$totalPaginas = max(1, (int) ceil($total / $porPagina));
if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
}
$offset = ($pagina - 1) * $porPagina;

$sql = "SELECT p.id, p.nome, p.preco, p.estoque, p.faixa_etaria, c.nome AS categoria_nome,
               (
                   SELECT i.caminho
                   FROM produto_imagens i
                   WHERE i.produto_id = p.id
                   ORDER BY i.principal DESC, i.id ASC
                   LIMIT 1
               ) AS imagem
        FROM produtos p
        INNER JOIN categorias c ON c.id = p.categoria_id
        $sqlWhere
        ORDER BY {$ordens[$ordem]}
        LIMIT $porPagina OFFSET $offset";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$produtos = $stmt->fetchAll();

$categorias = db()->query(
    "SELECT id, nome FROM categorias WHERE status = 'ativo' ORDER BY nome ASC"
)->fetchAll();

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
if ($ordem !== 'recentes') {
    $paramsPaginacao['ordem'] = $ordem;
}
$temFiltro = $paramsPaginacao !== [];

$pageTitle = 'Brinquedos';
require __DIR__ . '/includes/loja-header.php';
?>
<section class="loja-hero">
    <div>
        <p class="loja-selo">Loja de brinquedos</p>
        <h1>Encontre o próximo brinquedo</h1>
        <p>Estes são os produtos cadastrados pela equipe da Toy Mania. Escolha por categoria, idade ou preço.</p>
    </div>
</section>

<section class="card loja-filtros">
    <form method="get" action="<?= e(base_url('loja.php')) ?>">
        <div class="loja-filtros-grid">
            <div class="field">
                <label for="busca">Buscar</label>
                <input id="busca" name="busca" value="<?= e($busca) ?>" placeholder="Nome do brinquedo">
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
                <label for="ordem">Ordenar</label>
                <select id="ordem" name="ordem">
                    <option value="recentes" <?= $ordem === 'recentes' ? 'selected' : '' ?>>Mais recentes</option>
                    <option value="preco_asc" <?= $ordem === 'preco_asc' ? 'selected' : '' ?>>Menor preço</option>
                    <option value="preco_desc" <?= $ordem === 'preco_desc' ? 'selected' : '' ?>>Maior preço</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button class="btn btn-secondary" type="submit"><?= icon('busca') ?> Ver produtos</button>
            <?php if ($temFiltro): ?>
                <a class="btn btn-ghost" href="<?= e(base_url('loja.php')) ?>">Limpar</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<?php if (!$produtos): ?>
    <section class="card empty">
        <?= icon('caixa') ?>
        <p><?= $temFiltro ? 'Nenhum brinquedo encontrado com esses filtros.' : 'A loja ainda não tem produtos ativos. Volte em breve.' ?></p>
    </section>
<?php else: ?>
    <section class="vitrine" aria-label="Produtos da loja">
        <?php foreach ($produtos as $produto): ?>
            <a class="vitrine-card" href="<?= e(base_url('produto.php?id=' . (int) $produto['id'])) ?>">
                <?php if (!empty($produto['imagem'])): ?>
                    <img src="<?= e(url_upload((string) $produto['imagem'])) ?>" alt="<?= e('Foto de ' . $produto['nome']) ?>">
                <?php else: ?>
                    <span class="vitrine-sem-foto" aria-hidden="true"><?= icon('imagem') ?></span>
                <?php endif; ?>
                <span class="vitrine-corpo">
                    <span class="badge badge-principal"><?= e($produto['categoria_nome']) ?></span>
                    <strong><?= e($produto['nome']) ?></strong>
                    <span class="muted"><?= e(rotulo_faixa((string) $produto['faixa_etaria'])) ?></span>
                    <span class="vitrine-preco">
                        <span class="money"><?= e(dinheiro($produto['preco'])) ?></span>
                        <?php if ((int) $produto['estoque'] > 0): ?>
                            <span class="badge badge-sucesso">Disponível</span>
                        <?php else: ?>
                            <span class="badge badge-erro">Esgotado</span>
                        <?php endif; ?>
                    </span>
                </span>
            </a>
        <?php endforeach; ?>
    </section>
    <?= html_paginacao($pagina, $totalPaginas, 'loja.php', $paramsPaginacao) ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/loja-footer.php'; ?>
