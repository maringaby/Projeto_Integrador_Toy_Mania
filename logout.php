<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.php');
}

verificar_csrf();
encerrar_sessao();
iniciar_sessao();
flash('sucesso', 'Você saiu do sistema.');
redirect('login.php');
