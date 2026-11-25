<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

exigir_login();
$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
$categoria = buscar_categoria($id);

if (!$categoria) {
    flash('erro', 'Categoria não encontrada.');
    redirect('categorias/index.php');
}

$form = [
    'nome' => (string) $categoria['nome'],
    'descricao' => (string) ($categoria['descricao'] ?? ''),
    'status' => (string) $categoria['status'],
];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $form['nome'] = trim((string) ($_POST['nome'] ?? ''));
    $form['descricao'] = trim((string) ($_POST['descricao'] ?? ''));
    $form['status'] = (string) ($_POST['status'] ?? 'ativo');
    $resultado = validar_categoria($_POST, $id);
    $erros = $resultado['erros'];

    if (!$erros) {
        try {
            $stmt = db()->prepare('UPDATE categorias SET nome = :nome, descricao = :descricao, status = :status WHERE id = :id');
            $stmt->execute($resultado['dados'] + ['id' => $id]);
            flash('sucesso', 'Categoria atualizada com sucesso.');
            redirect('categorias/index.php');
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                $erros['nome'] = 'Já existe uma categoria com este nome.';
            } else {
                throw $e;
            }
        }
    }
}

$pageTitle = 'Editar categoria';
$activeMenu = 'categorias';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-head">
    <div>
        <h1>Editar categoria</h1>
        <p><?= contar_produtos_da_categoria($id) ?> produto(s) vinculado(s).</p>
    </div>
</section>
<section class="card">
    <?php if ($erros): ?><div class="alert alert-erro">Revise os campos destacados antes de salvar.</div><?php endif; ?>
    <form method="post" action="">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <?php require dirname(__DIR__) . '/includes/form-categoria.php'; ?>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Salvar alterações</button>
            <a class="btn btn-ghost" href="<?= e(base_url('categorias/index.php')) ?>">Cancelar</a>
        </div>
    </form>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
