<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (!banco_tem_tabelas()) {
    exibir_instalacao();
}

if (total_funcionarios() === 0) {
    redirect('setup.php');
}

if (!empty($_SESSION['funcionario_id'])) {
    $stmt = db()->prepare('SELECT id, status FROM funcionarios WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => (int) $_SESSION['funcionario_id']]);
    $atual = $stmt->fetch();
    if ($atual && $atual['status'] === 'ativo') {
        redirect('index.php');
    }
    unset($_SESSION['funcionario_id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $senha = (string) ($_POST['senha'] ?? '');
    $_SESSION['login_email'] = $email;

    $stmt = db()->prepare('SELECT id, nome_completo, senha, status FROM funcionarios WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $funcionario = $stmt->fetch();

    $senhaConfere = $funcionario && password_verify($senha, (string) $funcionario['senha']);

    if (!$funcionario || !$senhaConfere) {
        flash('erro', 'E-mail ou senha inválidos.');
        redirect('login.php');
    }

    if ($funcionario['status'] !== 'ativo') {
        flash('erro', 'Sua conta está inativa. Procure um administrador da loja.');
        redirect('login.php');
    }

    if (password_needs_rehash((string) $funcionario['senha'], PASSWORD_DEFAULT)) {
        $novoHash = password_hash($senha, PASSWORD_DEFAULT);
        $atualizar = db()->prepare('UPDATE funcionarios SET senha = :senha WHERE id = :id');
        $atualizar->execute(['senha' => $novoHash, 'id' => (int) $funcionario['id']]);
    }

    session_regenerate_id(true);
    $_SESSION['funcionario_id'] = (int) $funcionario['id'];
    unset($_SESSION['login_email']);
    flash('sucesso', 'Bem-vindo, ' . primeiro_nome((string) $funcionario['nome_completo']) . '.');
    redirect('index.php');
}

$emailLembrado = (string) ($_SESSION['login_email'] ?? '');
unset($_SESSION['login_email']);

$pageTitle = 'Entrar';
$bodyClass = 'guest-body';
require __DIR__ . '/includes/head.php';
?>
<main class="guest">
    <section class="guest-card">
        <img class="guest-logo" src="<?= e(asset('img/logo.svg')) ?>" alt="Toy Mania">
        <h1>Entrar</h1>
        <p class="subtitle">Painel administrativo da loja de brinquedos.</p>
        <?php render_flash(); ?>
        <form method="post" action="">
            <?= csrf_field() ?>
            <div class="field">
                <label for="email">E-mail</label>
                <input id="email" name="email" type="email" maxlength="180" required autofocus autocomplete="username" value="<?= e($emailLembrado) ?>">
            </div>
            <div class="field">
                <label for="senha">Senha</label>
                <input id="senha" name="senha" type="password" required autocomplete="current-password">
            </div>
            <button class="btn btn-primary btn-block" type="submit">Entrar</button>     
        </form>
        <p class="loja-atalho"><a href="<?= e(base_url('loja.php')) ?>">Ver os brinquedos da loja</a></p>
    </section>
</main>
<?php require __DIR__ . '/includes/guest-footer.php'; ?>
