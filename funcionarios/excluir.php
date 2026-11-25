<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$usuario = exigir_login();
$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
$funcionario = $id > 0 ? buscar_funcionario($id) : null;

if (!$funcionario) {
    flash('erro', 'Funcionário não encontrado.');
    redirect('funcionarios/index.php');
}

$souEu = (int) $usuario['id'] === $id;
$produtosVinculados = contar_produtos_do_funcionario($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    if ($souEu) {
        flash('erro', 'Você não pode excluir a sua própria conta.');
        redirect('funcionarios/index.php');
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $atualizar = $pdo->prepare('UPDATE produtos SET funcionario_id = :novo WHERE funcionario_id = :antigo');
        $atualizar->execute([
            'novo' => (int) $usuario['id'],
            'antigo' => $id,
        ]);
        $apagar = $pdo->prepare('DELETE FROM funcionarios WHERE id = :id');
        $apagar->execute(['id' => $id]);
        $pdo->commit();
        flash('sucesso', 'Funcionário excluído com sucesso.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[Toy Mania] ' . $e->getMessage());
        flash('erro', 'Não foi possível excluir este funcionário.');
    }

    redirect('funcionarios/index.php');
}

$pageTitle = 'Excluir funcionário';
$activeMenu = 'funcionarios';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="card confirm-card">
    <h1>Excluir funcionário</h1>
    <?php if ($souEu): ?>
        <div class="alert alert-erro">Você não pode excluir a sua própria conta.</div>
        <a class="btn btn-ghost" href="<?= e(base_url('funcionarios/index.php')) ?>">Voltar</a>
    <?php else: ?>
        <p>Deseja excluir <strong><?= e($funcionario['nome_completo']) ?></strong> (<?= e($funcionario['email']) ?>)?</p>
        <?php if ($produtosVinculados > 0): ?>
            <p>Esta pessoa cadastrou <?= $produtosVinculados ?> produto(s). Se você confirmar, esses produtos passarão a ter você como responsável.</p>
        <?php endif; ?>
        <p>Esta ação não pode ser desfeita.</p>
        <form method="post" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Excluir funcionário</button>
                <a class="btn btn-ghost" href="<?= e(base_url('funcionarios/index.php')) ?>">Cancelar</a>
            </div>
        </form>
    <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
