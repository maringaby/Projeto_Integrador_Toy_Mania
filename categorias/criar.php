<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

exigir_login();

$form = ['nome' => '', 'descricao' => '', 'status' => 'ativo'];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $form['nome'] = trim((string) ($_POST['nome'] ?? ''));
    $form['descricao'] = trim((string) ($_POST['descricao'] ?? ''));
    $form['status'] = (string) ($_POST['status'] ?? 'ativo');
    $resultado = validar_categoria($_POST, null);
    $erros = $resultado['erros'];

    if (!$erros) {
        $dados = $resultado['dados'];
        try {
            $stmt = db()->prepare('INSERT INTO categorias (nome, descricao, status) VALUES (:nome, :descricao, :status)');
            $stmt->execute($dados);
            flash('sucesso', 'Categoria cadastrada com sucesso.');
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

$pageTitle = 'Nova categoria';
$activeMenu = 'categorias';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-head">
    <div>
        <h1>Nova categoria</h1>
        <p>Use nomes claros, como Bonecas, Carrinhos ou Jogos educativos.</p>
    </div>
</section>
<section class="card">
    <?php if ($erros): ?><div class="alert alert-erro">Revise os campos destacados antes de salvar.</div><?php endif; ?>
    <form method="post" action="">
        <?= csrf_field() ?>
        <?php require dirname(__DIR__) . '/includes/form-categoria.php'; ?>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Cadastrar categoria</button>
            <a class="btn btn-ghost" href="<?= e(base_url('categorias/index.php')) ?>">Cancelar</a>
        </div>
    </form>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
