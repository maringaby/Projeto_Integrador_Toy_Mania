<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

exigir_login();
$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
$categoria = $id > 0 ? buscar_categoria($id) : null;

if (!$categoria) {
    flash('erro', 'Categoria não encontrada.');
    redirect('categorias/index.php');
}

$totalProdutos = contar_produtos_da_categoria($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    if ($totalProdutos > 0) {
        flash('erro', 'Esta categoria possui produtos vinculados e não pode ser excluída. Altere ou exclua esses produtos antes.');
        redirect('categorias/excluir.php?id=' . $id);
    }

    $stmt = db()->prepare('DELETE FROM categorias WHERE id = :id');
    $stmt->execute(['id' => $id]);
    flash('sucesso', 'Categoria excluída com sucesso.');
    redirect('categorias/index.php');
}

$pageTitle = 'Excluir categoria';
$activeMenu = 'categorias';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="card confirm-card">
    <h1>Excluir categoria</h1>
    <?php if ($totalProdutos > 0): ?>
        <div class="alert alert-erro">
            A categoria <?= e($categoria['nome']) ?> possui <?= $totalProdutos ?> produto(s) e não pode ser excluída enquanto essa relação existir.
        </div>
        <div class="form-actions">
            <a class="btn btn-secondary" href="<?= e(base_url('produtos/index.php?categoria=' . $id)) ?>">Ver produtos</a>
            <a class="btn btn-ghost" href="<?= e(base_url('categorias/index.php')) ?>">Voltar</a>
        </div>
    <?php else: ?>
        <p>Deseja excluir a categoria <strong><?= e($categoria['nome']) ?></strong>? Esta ação não pode ser desfeita.</p>
        <form method="post" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Excluir categoria</button>
                <a class="btn btn-ghost" href="<?= e(base_url('categorias/index.php')) ?>">Cancelar</a>
            </div>
        </form>
    <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
