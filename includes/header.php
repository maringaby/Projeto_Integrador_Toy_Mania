<?php

declare(strict_types=1);

$usuario = exigir_login();
$pageTitle = $pageTitle ?? 'Painel';
$activeMenu = $activeMenu ?? '';
$bodyClass = 'app-body';

require __DIR__ . '/head.php';
?>
<a class="skip-link" href="#conteudo">Ir para o conteúdo</a>
<div class="app-shell">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="workspace">
        <header class="topbar">
            <button class="icon-btn menu-toggle" id="menu-toggle" type="button" aria-label="Abrir menu">
                <?= icon('menu') ?>
            </button>
            <a class="topbar-brand" href="<?= e(base_url('index.php')) ?>">
                <img src="<?= e(asset('img/logo-mark.svg')) ?>" width="36" height="36" alt="">
                <span>Toy Mania</span>
            </a>
            <div class="topbar-title"><?= e($pageTitle) ?></div>
            <div class="user-chip">
                <span class="avatar" aria-hidden="true"><?= e(iniciais($usuario['nome_completo'])) ?></span>
                <span>
                    <strong><?= e($usuario['nome_completo']) ?></strong>
                    <small><?= e($usuario['cargo']) ?></small>
                </span>
            </div>
        </header>
        <main class="content" id="conteudo">
            <?php render_flash(); ?>
