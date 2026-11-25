<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

exigir_login();

$form = [
    'nome_completo' => '',
    'email' => '',
    'telefone' => '',
    'cargo' => '',
    'status' => 'ativo',
];
$erros = [];
$senhaObrigatoria = true;
$bloqueiaInativo = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $form['nome_completo'] = trim((string) ($_POST['nome_completo'] ?? ''));
    $form['email'] = trim((string) ($_POST['email'] ?? ''));
    $form['telefone'] = trim((string) ($_POST['telefone'] ?? ''));
    $form['cargo'] = trim((string) ($_POST['cargo'] ?? ''));
    $form['status'] = (string) ($_POST['status'] ?? 'ativo');

    $resultado = validar_funcionario($_POST, true, null);
    $erros = $resultado['erros'];

    if (!$erros) {
        $dados = $resultado['dados'];
        try {
            $stmt = db()->prepare(
                'INSERT INTO funcionarios (nome_completo, email, senha, telefone, cargo, status)
                 VALUES (:nome, :email, :senha, :telefone, :cargo, :status)'
            );
            $stmt->execute([
                'nome' => $dados['nome_completo'],
                'email' => $dados['email'],
                'senha' => $dados['senha'],
                'telefone' => $dados['telefone'],
                'cargo' => $dados['cargo'],
                'status' => $dados['status'],
            ]);
            flash('sucesso', 'Funcionário cadastrado com sucesso.');
            redirect('funcionarios/index.php');
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                $erros['email'] = 'Este e-mail já está cadastrado.';
            } else {
                throw $e;
            }
        }
    }
}

$pageTitle = 'Novo funcionário';
$activeMenu = 'funcionarios';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-head">
    <div>
        <h1>Novo funcionário</h1>
        <p>A senha é armazenada com criptografia e não pode ser lida depois.</p>
    </div>
</section>

<section class="card">
    <?php if ($erros): ?>
        <div class="alert alert-erro">Revise os campos destacados antes de salvar.</div>
    <?php endif; ?>
    <form method="post" action="">
        <?= csrf_field() ?>
        <?php require dirname(__DIR__) . '/includes/form-funcionario.php'; ?>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Cadastrar funcionário</button>
            <a class="btn btn-ghost" href="<?= e(base_url('funcionarios/index.php')) ?>">Cancelar</a>
        </div>
    </form>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
