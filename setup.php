<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (!banco_tem_tabelas()) {
    exibir_instalacao();
}

$jaInstalado = total_funcionarios() > 0;
$form = [
    'nome_completo' => '',
    'email' => '',
    'telefone' => '',
];
$erros = [];

if (!$jaInstalado && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $form['nome_completo'] = trim((string) ($_POST['nome_completo'] ?? ''));
    $form['email'] = trim((string) ($_POST['email'] ?? ''));
    $form['telefone'] = trim((string) ($_POST['telefone'] ?? ''));

    if (total_funcionarios() > 0) {
        flash('aviso', 'O administrador inicial já foi criado.');
        redirect('login.php');
    }

    $entrada = $_POST;
    $entrada['cargo'] = 'Administrador';
    $entrada['status'] = 'ativo';
    $resultado = validar_funcionario($entrada, true, null);
    $erros = $resultado['erros'];

    if (!$erros) {
        $dados = $resultado['dados'];
        $stmt = db()->prepare(
            'INSERT INTO funcionarios (nome_completo, email, senha, telefone, cargo, status)
             VALUES (:nome, :email, :senha, :telefone, :cargo, :status)'
        );
        $stmt->execute([
            'nome' => $dados['nome_completo'],
            'email' => $dados['email'],
            'senha' => $dados['senha'],
            'telefone' => $dados['telefone'],
            'cargo' => 'Administrador',
            'status' => 'ativo',
        ]);

        flash('sucesso', 'Administrador criado. Entre com o e-mail e a senha que você acabou de definir.');
        redirect('login.php');
    }
}

$pageTitle = 'Configuração inicial';
$bodyClass = 'guest-body';
require __DIR__ . '/includes/head.php';
?>
<main class="guest">
    <section class="guest-card">
        <img class="guest-logo" src="<?= e(asset('img/logo.svg')) ?>" alt="Toy Mania">
        <?php if ($jaInstalado): ?>
            <h1>Configuração encerrada</h1>
            <p class="subtitle">O sistema já possui um administrador. Por segurança, esta tela não cria outro acesso.</p>
            <?php render_flash(); ?>
            <a class="btn btn-primary btn-block" href="<?= e(base_url('login.php')) ?>">Ir para o login</a>
        <?php else: ?>
            <h1>Primeiro acesso</h1>
            <p class="subtitle">Crie o administrador da Toy Mania. Não existe senha padrão.</p>
            <?php render_flash(); ?>
            <?php if ($erros): ?>
                <div class="alert alert-erro">Revise os campos destacados antes de continuar.</div>
            <?php endif; ?>
            <form method="post" action="">
                <?= csrf_field() ?>
                <div class="field">
                    <label for="nome_completo">Nome completo <span class="req">*</span></label>
                    <input class="<?= e(trim(invalido($erros, 'nome_completo'))) ?>" id="nome_completo" name="nome_completo" maxlength="150" required value="<?= e($form['nome_completo']) ?>">
                    <?= erro_campo($erros, 'nome_completo') ?>
                </div>
                <div class="field">
                    <label for="email">E-mail <span class="req">*</span></label>
                    <input class="<?= e(trim(invalido($erros, 'email'))) ?>" id="email" name="email" type="email" maxlength="180" required autocomplete="username" value="<?= e($form['email']) ?>">
                    <?= erro_campo($erros, 'email') ?>
                </div>
                <div class="field">
                    <label for="telefone">Telefone</label>
                    <input class="<?= e(trim(invalido($erros, 'telefone'))) ?>" id="telefone" name="telefone" maxlength="20" value="<?= e($form['telefone']) ?>">
                    <?= erro_campo($erros, 'telefone') ?>
                </div>
                <div class="field">
                    <label for="senha">Senha <span class="req">*</span></label>
                    <input class="<?= e(trim(invalido($erros, 'senha'))) ?>" id="senha" name="senha" type="password" minlength="6" required autocomplete="new-password">
                    <small class="help">Mínimo de 6 caracteres. Escolha uma senha que só a equipe conheça.</small>
                    <?= erro_campo($erros, 'senha') ?>
                </div>
                <div class="field">
                    <label for="senha_confirmacao">Confirmar senha <span class="req">*</span></label>
                    <input class="<?= e(trim(invalido($erros, 'senha_confirmacao'))) ?>" id="senha_confirmacao" name="senha_confirmacao" type="password" required autocomplete="new-password">
                    <?= erro_campo($erros, 'senha_confirmacao') ?>
                </div>
                <p class="help">O cargo deste primeiro acesso será Administrador.</p>
                <button class="btn btn-primary btn-block" type="submit">Criar administrador</button>
            </form>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/includes/guest-footer.php'; ?>
