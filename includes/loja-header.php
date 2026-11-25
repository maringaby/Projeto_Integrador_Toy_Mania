
<?php


$pageTitle = $pageTitle ?? 'Loja';
$bodyClass = 'loja-body';

require __DIR__ . '/head.php';

$cliente = $clienteLogado ?? null;

?>

<a class="skip-link" href="#conteudo">Ir para os produtos</a>

<header class="loja-topo">

    <a class="brand" href="<?= e(base_url('loja.php')) ?>">
        <img src="<?= e(asset('img/logo-mark.svg')) ?>"
             width="44"
             height="44"
             alt="">

        <span>Toy <strong>Mania</strong></span>
    </a>

    <nav class="loja-nav" aria-label="Loja">

        <a href="<?= e(base_url('loja.php')) ?>">
            Brinquedos
        </a>

        <?php if ($cliente): ?>

            <span class="cliente-boas-vindas">
                Olá, <?= e(primeiro_nome((string) $cliente['nome'])) ?>!
            </span>

            <form method="post" action="<?= e(base_url('clientes/logout.php')) ?>">
                <?= csrf_field() ?>

                <button class="btn btn-ghost btn-small" type="submit">
                    Sair
                </button>
            </form>

        <?php else: ?>

            <a class="btn btn-ghost btn-small"
               href="<?= e(base_url('clientes/login.php')) ?>">
                Entrar
            </a>

            <a class="btn btn-primary btn-small"
               href="<?= e(base_url('clientes/cadastrar.php')) ?>">
                Cadastrar-se
            </a>

        <?php endif; ?>

        <a class="btn btn-ghost btn-small"
           href="<?= e(base_url('login.php')) ?>">
            Área da equipe
        </a>

    </nav>

</header>

<main class="loja-main" id="conteudo">
