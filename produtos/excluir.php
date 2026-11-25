<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

exigir_login();
$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
$produto = $id > 0 ? buscar_produto($id) : null;

if (!$produto) {
    flash('erro', 'Produto não encontrado.');
    redirect('produtos/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $imagens = imagens_do_produto($id);
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('DELETE FROM produtos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[Toy Mania] ' . $e->getMessage());
        flash('erro', 'Não foi possível excluir este produto.');
        redirect('produtos/index.php');
    }

    foreach ($imagens as $imagem) {
        apagar_arquivo_upload((string) $imagem['caminho']);
    }

    flash('sucesso', 'Produto excluído com sucesso.');
    redirect('produtos/index.php');
}

$pageTitle = 'Excluir produto';
$activeMenu = 'produtos';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="card confirm-card">
    <h1>Excluir produto</h1>
    <p>Deseja excluir <strong><?= e($produto['nome']) ?></strong>?</p>
    <p>As fotos vinculadas também serão apagadas. Esta ação não pode ser desfeita.</p>
    <form method="post" action="">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Excluir produto</button>
            <a class="btn btn-ghost" href="<?= e(base_url('produtos/visualizar.php?id=' . $id)) ?>">Cancelar</a>
        </div>
    </form>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
