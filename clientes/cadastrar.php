<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$form = [
    'nome' => '',
    'email' => '',
    'telefone' => '',
];

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verificar_csrf();

    $form['nome'] = trim((string) ($_POST['nome'] ?? ''));
    $form['email'] = trim((string) ($_POST['email'] ?? ''));
    $form['telefone'] = trim((string) ($_POST['telefone'] ?? ''));

    $senha = (string) ($_POST['senha'] ?? '');
    $senhaConfirmacao = (string) ($_POST['senha_confirmacao'] ?? '');

    // Validação dos campos

    if ($form['nome'] === '') {
        $erros['nome'] = 'Informe seu nome.';
    }

    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $erros['email'] = 'Informe um e-mail válido.';
    }

    if (strlen($senha) < 6) {
        $erros['senha'] = 'A senha deve ter pelo menos 6 caracteres.';
    }

    if ($senha !== $senhaConfirmacao) {
        $erros['senha_confirmacao'] = 'As senhas não coincidem.';
    }

    // Verifica se o e-mail já está cadastrado

    if (!$erros) {
        $stmt = db()->prepare(
            'SELECT id FROM clientes WHERE email = :email'
        );

        $stmt->execute([
            'email' => $form['email']
        ]);

        if ($stmt->fetch()) {
            $erros['email'] = 'Este e-mail já está cadastrado.';
        }
    }

    // Salva o cliente no banco

    if (!$erros) {

        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = db()->prepare(
            'INSERT INTO clientes (nome, email, senha, telefone)
             VALUES (:nome, :email, :senha, :telefone)'
        );

        $stmt->execute([
            'nome' => $form['nome'],
            'email' => $form['email'],
            'senha' => $senhaHash,
            'telefone' => $form['telefone'],
        ]);

        flash('sucesso', 'Cadastro realizado com sucesso!');

        redirect('clientes/login.php');
    }
}

$pageTitle = 'Cadastro de Cliente';
$bodyClass = 'guest-body';

require __DIR__ . '/../includes/head.php';  

?>

<main class="guest">
    <section class="guest-card">

        <img class="guest-logo"
             src="<?= e(asset('img/logo.svg')) ?>"
             alt="Toy Mania">

        <h1>Cadastre-se</h1>

        <p class="subtitle">
            Crie sua conta na Toy Mania.
        </p>

        <?php render_flash(); ?>

        <?php if ($erros): ?>
            <div class="alert alert-erro">
                Revise os campos destacados antes de continuar.
            </div>
        <?php endif; ?>

        <form method="post" action="">

            <?= csrf_field() ?>

            <div class="field">
                <label for="nome">Nome completo</label>

                <input
                    id="nome"
                    name="nome"
                    maxlength="150"
                    required
                    value="<?= e($form['nome']) ?>"
                >

                <?= erro_campo($erros, 'nome') ?>
            </div>

            <div class="field">
                <label for="email">E-mail</label>

                <input
                    id="email"
                    name="email"
                    type="email"
                    maxlength="180"
                    required
                    value="<?= e($form['email']) ?>"
                >

                <?= erro_campo($erros, 'email') ?>
            </div>

            <div class="field">
                <label for="telefone">Telefone</label>

                <input
                    id="telefone"
                    name="telefone"
                    maxlength="20"
                    value="<?= e($form['telefone']) ?>"
                >

                <?= erro_campo($erros, 'telefone') ?>
            </div>

            <div class="field">
                <label for="senha">Senha</label>

                <input
                    id="senha"
                    name="senha"
                    type="password"
                    minlength="6"
                    required
                >

                <?= erro_campo($erros, 'senha') ?>
            </div>

            <div class="field">
                <label for="senha_confirmacao">
                    Confirmar senha
                </label>

                <input
                    id="senha_confirmacao"
                    name="senha_confirmacao"
                    type="password"
                    required
                >

                <?= erro_campo($erros, 'senha_confirmacao') ?>
            </div>

            <button class="btn btn-primary btn-block" type="submit">
                Criar minha conta
            </button>

        </form>

        <p class="help">
            Já possui uma conta?
            <a href="<?= e(base_url('clientes/login.php')) ?>">Faça login</a>.
        </p>

    </section>
</main>

<?php require __DIR__ . '/../includes/guest-footer.php'; ?>
