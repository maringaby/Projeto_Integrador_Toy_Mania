<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/validacao.php';
require_once __DIR__ . '/consultas.php';
require_once __DIR__ . '/uploads.php';

if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

iniciar_sessao();

set_exception_handler(static function (Throwable $e): void {
    error_log('[Toy Mania] ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());

    if ($e instanceof PDOException) {
        $codigo = (int) ($e->errorInfo[1] ?? 0);
        if (in_array($codigo, [1049, 1146, 2002, 1045], true)) {
            falha_banco($e);
        }
    }

    http_response_code(500);
    if (APP_DEBUG) {
        echo '<pre>' . e($e->getMessage()) . '</pre>';
        return;
    }

    echo 'Ocorreu um erro inesperado. Tente novamente.';
});
