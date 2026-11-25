<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('loja.php');
}

verificar_csrf();

unset($_SESSION['cliente_id']);

session_regenerate_id(true);

flash('sucesso', 'Você saiu da sua conta.');

redirect('loja.php');
