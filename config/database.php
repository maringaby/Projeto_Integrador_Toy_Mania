<?php

declare(strict_types=1);

/**
 * Conexão com o MySQL.
 * Use o mesmo usuário e a mesma senha do cliente mysql.
 * Deixe DB_PASS vazio apenas se essa conta não tiver senha.
 */
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'toymania_db');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');

/**
 * Retorna uma única conexão PDO para toda a requisição.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        try {
            $pdo->exec("SET time_zone = '-03:00'");
        } catch (PDOException) {
            // Se o servidor não aceitar o fuso, as datas seguem o horário do MySQL.
        }
    } catch (PDOException $e) {
        falha_banco($e);
    }

    return $pdo;
}

/**
 * Página amigável quando o MySQL não está acessível ou o banco não foi importado.
 */
function falha_banco(PDOException $e): void
{
    $codigo = (int) ($e->errorInfo[1] ?? 0);
    error_log('[Toy Mania] Banco: ' . $e->getMessage());

    $mensagens = [
        2002 => 'O MySQL não está em execução. Inicie o serviço do MySQL e tente novamente.',
        2003 => 'Não foi possível encontrar o MySQL. Confira o host e a porta em config/database.php.',
        1044 => 'O usuário do banco não tem permissão para acessar o toymania_db.',
        1045 => 'O MySQL recusou o usuário ou a senha. Ajuste config/database.php.',
        1049 => 'O banco toymania_db ainda não existe. Importe database/toymania.sql com o cliente mysql.',
        1146 => 'As tabelas ainda não foram criadas. Importe database/toymania.sql com o cliente mysql.',
    ];

    $mensagem = $mensagens[$codigo] ?? 'Não foi possível conectar ao banco de dados. Confira o MySQL, o arquivo database/toymania.sql e a configuração em config/database.php.';
    $detalhe = APP_DEBUG ? $e->getMessage() : '';

    http_response_code(500);
    $logo = function_exists('asset') ? asset('img/logo-mark.svg') : '';
    $css = function_exists('asset') ? asset('css/style.css') : '';

    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>Banco de dados · Toy Mania</title>';
    if ($css !== '') {
        echo '<link rel="stylesheet" href="' . e($css) . '">';
    }
    echo '</head><body class="guest-body"><main class="guest"><section class="guest-card">';
    if ($logo !== '') {
        echo '<img class="guest-mark" src="' . e($logo) . '" alt="">';
    }
    echo '<h1>Toy Mania</h1>';
    echo '<p class="subtitle">A conexão com o MySQL precisa de um ajuste.</p>';
    echo '<div class="alert alert-erro">' . e($mensagem) . '</div>';
    if ($detalhe !== '') {
        echo '<pre class="debug">' . e($detalhe) . '</pre>';
    }
    echo '<p class="muted">Passos: iniciar o MySQL, importar database/toymania.sql e revisar config/database.php.</p>';
    echo '</section></main></body></html>';
    exit;
}
