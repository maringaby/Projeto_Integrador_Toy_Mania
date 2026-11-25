<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$usuario = exigir_login();
$busca = trim((string) ($_GET['busca'] ?? ''));
$pagina = max(1, (int) ($_GET['page'] ?? 1));
$porPagina = 10;
$where = '';
$params = [];

if ($busca !== '') {
    $where = 'WHERE nome_completo LIKE :busca OR email LIKE :busca_email';
    $termo = like($busca);
    $params['busca'] = $termo;
    $params['busca_email'] = $termo;
}

$totalStmt = db()->prepare("SELECT COUNT(*) FROM funcionarios $where");
$totalStmt->execute($params);
$total = (int) $totalStmt->fetchColumn();
$totalPaginas = max(1, (int) ceil($total / $porPagina));
if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
}
$offset = ($pagina - 1) * $porPagina;

$sql = "SELECT id, nome_completo, email, telefone, cargo, status, criado_em
        FROM funcionarios
        $where
        ORDER BY nome_completo ASC
        LIMIT $porPagina OFFSET $offset";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$funcionarios = $stmt->fetchAll();

$paramsPaginacao = $busca !== '' ? ['busca' => $busca] : [];
$retorno = url_relativa_atual();

$pageTitle = 'Funcionários';
$activeMenu = 'funcionarios';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-head">
    <div>
        <h1>Funcionários</h1>
        <p>Cadastre a equipe que acessa o painel da Toy Mania.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(base_url('funcionarios/criar.php')) ?>"><?= icon('mais') ?> Novo funcionário</a>
</section>

<section class="card stack">
    <form class="search-bar" method="get" action="">
        <label class="sr-only" for="busca">Pesquisar por nome ou e-mail</label>
        <input id="busca" name="busca" value="<?= e($busca) ?>" placeholder="Pesquisar por nome ou e-mail">
        <button class="btn btn-secondary" type="submit"><?= icon('busca') ?> Pesquisar</button>
        <?php if ($busca !== ''): ?>
            <a class="btn btn-ghost" href="<?= e(base_url('funcionarios/index.php')) ?>">Limpar</a>
        <?php endif; ?>
    </form>

    <?php if (!$funcionarios): ?>
        <div class="empty">
            <?= icon('usuarios') ?>
            <p><?= $busca !== '' ? 'Nenhum funcionário encontrado para esta pesquisa.' : 'Nenhum funcionário cadastrado.' ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th class="hide-md">Telefone</th>
                        <th>Cargo</th>
                        <th>Status</th>
                        <th class="hide-sm">Cadastro</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($funcionarios as $funcionario): ?>
                        <?php $souEu = (int) $funcionario['id'] === (int) $usuario['id']; ?>
                        <tr>
                            <td>
                                <?= e($funcionario['nome_completo']) ?>
                                <?php if ($souEu): ?><span class="badge badge-voce">Você</span><?php endif; ?>
                            </td>
                            <td><?= e($funcionario['email']) ?></td>
                            <td class="hide-md"><?= e((string) ($funcionario['telefone'] ?: '—')) ?></td>
                            <td><?= e($funcionario['cargo']) ?></td>
                            <td><?= badge_status((string) $funcionario['status']) ?></td>
                            <td class="hide-sm"><?= e(data_br($funcionario['criado_em'])) ?></td>
                            <td>
                                <div class="actions">
                                    <a class="btn-icon" href="<?= e(base_url('funcionarios/editar.php?id=' . (int) $funcionario['id'])) ?>" title="Editar" aria-label="<?= e('Editar ' . $funcionario['nome_completo']) ?>"><?= icon('lapis') ?></a>
                                    <?php if ($souEu): ?>
                                        <span class="btn-icon" aria-disabled="true" title="Você não pode desativar a sua própria conta"><?= icon('alerta') ?></span>
                                        <span class="btn-icon btn-icon-danger" aria-disabled="true" title="Você não pode excluir a sua própria conta"><?= icon('lixeira') ?></span>
                                    <?php else: ?>
                                        <form method="post" action="<?= e(base_url('funcionarios/status.php')) ?>" <?= $funcionario['status'] === 'ativo' ? 'data-confirm="Deseja desativar este funcionário? Ele não conseguirá entrar no sistema."' : '' ?>>
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $funcionario['id'] ?>">
                                            <input type="hidden" name="retorno" value="<?= e($retorno) ?>">
                                            <button class="btn btn-small btn-ghost" type="submit"><?= $funcionario['status'] === 'ativo' ? 'Desativar' : 'Ativar' ?></button>
                                        </form>
                                        <a class="btn-icon btn-icon-danger"
                                           href="<?= e(base_url('funcionarios/excluir.php?id=' . (int) $funcionario['id'])) ?>"
                                           data-post="1"
                                           data-id="<?= (int) $funcionario['id'] ?>"
                                           data-confirm="Deseja excluir este funcionário? Esta ação não pode ser desfeita."
                                           title="Excluir"
                                           aria-label="<?= e('Excluir ' . $funcionario['nome_completo']) ?>"><?= icon('lixeira') ?></a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= html_paginacao($pagina, $totalPaginas, 'funcionarios/index.php', $paramsPaginacao) ?>
    <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
