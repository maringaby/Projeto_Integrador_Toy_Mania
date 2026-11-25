<?php

declare(strict_types=1);

/**
 * Controle de sessão do funcionário.
 * Cada página interna chama exigir_login() antes de mostrar ou alterar dados.
 */

function usuario_autenticado(bool $obrigatorio = true): ?array
{
    static $cache = false;

    if ($cache !== false) {
        return $cache ?: null;
    }

    if (empty($_SESSION['funcionario_id'])) {
        $cache = null;
    } else {
        $stmt = db()->prepare(
            'SELECT id, nome_completo, email, telefone, cargo, status, criado_em
             FROM funcionarios
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => (int) $_SESSION['funcionario_id']]);
        $usuario = $stmt->fetch();

        if (!$usuario || $usuario['status'] !== 'ativo') {
            encerrar_sessao();
            iniciar_sessao();
            flash('erro', 'Sua sessão foi encerrada porque a conta está inativa ou não existe mais.');
            redirect('login.php');
        }

        $cache = $usuario;
    }

    if ($obrigatorio && $cache === null) {
        flash('erro', 'Faça login para acessar o painel.');
        redirect('login.php');
    }

    return $cache ?: null;
}

function exigir_login(): array
{
    $usuario = usuario_autenticado(true);
    if ($usuario === null) {
        redirect('login.php');
    }

    return $usuario;
}

function total_funcionarios(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM funcionarios')->fetchColumn();
}

function exibir_instalacao(): void
{
    $pageTitle = 'Instalação';
    $bodyClass = 'guest-body';
    require __DIR__ . '/head.php';
    ?>
    <main class="guest">
        <section class="guest-card">
            <img class="guest-mark" src="<?= e(asset('img/logo-mark.svg')) ?>" alt="">
            <h1>Falta importar o banco</h1>
            <p class="subtitle">O MySQL respondeu, mas as tabelas da Toy Mania ainda não existem.</p>
            <div class="alert alert-aviso">No terminal, na pasta do projeto, execute mysql -u root -p &lt; database/toymania.sql e atualize esta página.</div>
        </section>
    </main>
    <?php
    require __DIR__ . '/guest-footer.php';
    exit;
}

function banco_tem_tabelas(): bool
{
    try {
        db()->query('SELECT 1 FROM funcionarios LIMIT 1');
        return true;
    } catch (PDOException $e) {
        $codigo = (int) ($e->errorInfo[1] ?? 0);
        if ($codigo === 1146) {
            return false;
        }

        throw $e;
    }
}
