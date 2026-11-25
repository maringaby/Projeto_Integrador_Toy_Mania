<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (!banco_tem_tabelas()) {
    exibir_instalacao();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verificar_csrf();

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $senha = (string) ($_POST['senha'] ?? '');

    $stmt = db()->prepare(
        'SELECT id, nome, senha FROM clientes WHERE email = :email LIMIT 1'
    );

    $stmt->execute(['email' => $email]);

    $cliente = $stmt->fetch();

    if (!$cliente || !password_verify($senha, (string) $cliente['senha'])) {
        flash('erro', 'E-mail ou senha inválidos.');
        redirect('clientes/login.php');
    }

    session_regenerate_id(true);

    $_SESSION['cliente_id'] = (int) $cliente['id'];

    flash('sucesso', 'Bem-vindo, ' . primeiro_nome((string) $cliente['nome']) . '!');

    redirect('loja.php');
}

$pageTitle = 'Entrar';
$bodyClass = 'guest-body';

require __DIR__ . '/../includes/head.php';
?>

<main class="guest">
    <section class="guest-card">

        <img class="guest-logo" src="<?= e(asset('img/logo.svg')) ?>" alt="Toy Mania">

        <h1>Entrar</h1>

        <p class="subtitle">Acesse sua conta Toy Mania.</p>

        <?php render_flash(); ?>

        <form method="post" action="">

            <?= csrf_field() ?>

            <div class="field">
                <label for="email">E-mail</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    maxlength="180"
                    required
                    autofocus
                >
            </div>

            <div class="field">
                <label for="senha">Senha</label>
                <input
                    id="senha"
                    name="senha"
                    type="password"
                    required
                >
            </div>

            <button class="btn btn-primary btn-block" type="submit">
                Entrar
            </button>

        </form>

        <p class="help">
            Ainda não possui conta?
            <a href="<?= e(base_url('clientes/cadastrar.php')) ?>">Cadastre-se</a>.
        </p>

    </section>
</main>

<?php require __DIR__ . '/../includes/guest-footer.php'; ?>
