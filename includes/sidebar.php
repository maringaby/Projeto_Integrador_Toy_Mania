<?php

declare(strict_types=1);

$activeMenu = $activeMenu ?? '';
$itens = [
    'dashboard' => ['Dashboard', 'index.php', 'painel'],
    'funcionarios' => ['Funcionários', 'funcionarios/index.php', 'usuarios'],
    'produtos' => ['Produtos', 'produtos/index.php', 'caixa'],
    'categorias' => ['Categorias', 'categorias/index.php', 'etiqueta'],
];
?>
<aside class="sidebar" id="sidebar">
    <a class="brand" href="<?= e(base_url('index.php')) ?>">
        <img src="<?= e(asset('img/logo-mark.svg')) ?>" width="44" height="44" alt="">
        <span>Toy <strong>Mania</strong></span>
    </a>

    <nav aria-label="Menu principal">
        <?php foreach ($itens as $chave => [$rotulo, $destino, $icone]): ?>
            <a class="<?= $activeMenu === $chave ? 'is-active' : '' ?>"
               href="<?= e(base_url($destino)) ?>"
               <?= $activeMenu === $chave ? 'aria-current="page"' : '' ?>>
                <?= icon($icone) ?>
                <span><?= e($rotulo) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <a class="sidebar-loja" href="<?= e(base_url('loja.php')) ?>"><?= icon('olho') ?> <span>Ver a loja</span></a>
    <form class="sidebar-logout" method="post" action="<?= e(base_url('logout.php')) ?>">
        <?= csrf_field() ?>
        <button type="submit"><?= icon('sair') ?> <span>Sair</span></button>
    </form>
    <p class="sidebar-note">Projeto Integrador 2026</p>
</aside>
<div class="sidebar-backdrop" id="sidebar-backdrop" hidden></div>
