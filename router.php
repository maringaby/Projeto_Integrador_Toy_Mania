<?php

declare(strict_types=1);

/**
 * Roteador do servidor embutido do PHP:
 * php -d upload_max_filesize=8M -d post_max_size=24M -S localhost:8000 router.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

if (preg_match('#^/(config|includes|database)(/|$)#', $uri)) {
    http_response_code(403);
    exit('Acesso negado.');
}

if (str_starts_with($uri, '/uploads/')) {
    $base = realpath(__DIR__ . '/uploads');
    $arquivo = realpath(__DIR__ . $uri);
    $extensao = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
    $permitidas = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    $dentro = $arquivo !== false
        && $base !== false
        && (str_starts_with($arquivo, $base . DIRECTORY_SEPARATOR));

    if (!$dentro || !isset($permitidas[$extensao]) || !is_file($arquivo)) {
        http_response_code(404);
        exit('Arquivo não disponível.');
    }

    header('Content-Type: ' . $permitidas[$extensao]);
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: ' . (string) filesize($arquivo));
    readfile($arquivo);
    exit;
}

if ($uri === '/' || $uri === '') {
    require __DIR__ . '/loja.php';
    exit;
}

$caminho = __DIR__ . $uri;
if (is_dir($caminho) && is_file(rtrim($caminho, '/') . '/index.php')) {
    require rtrim($caminho, '/') . '/index.php';
    exit;
}

if (is_file($caminho)) {
    return false;
}

http_response_code(404);
echo 'Página não encontrada.';
