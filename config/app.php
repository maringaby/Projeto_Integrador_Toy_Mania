<?php

declare(strict_types=1);

/**
 * Configurações gerais da Toy Mania.
 * Este arquivo não guarda senha de banco — isso fica em config/database.php.
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_NAME', 'Toy Mania');

define('UPLOAD_DIR', ROOT_PATH . '/uploads');
define('UPLOAD_MAX_BYTES', 2 * 1024 * 1024);
define('UPLOAD_MAX_POR_ENVIO', 6);
define('PRODUTO_MAX_IMAGENS', 10);

/**
 * Com false, erros internos não aparecem no navegador.
 * Mude para true apenas enquanto estiver desenvolvendo no seu computador.
 */
define('APP_DEBUG', false);

date_default_timezone_set('America/Sao_Paulo');
