<?php

declare(strict_types=1);

/**
 * Funções de apoio usadas por todo o sistema.
 * A saída para o HTML passa por e(), que evita XSS.
 */

if (!function_exists('mb_strlen')) {
    function mb_strlen(string $string, ?string $encoding = null): int
    {
        return strlen($string);
    }

    function mb_substr(string $string, int $start, ?int $length = null, ?string $encoding = null): string
    {
        return $length === null ? substr($string, $start) : substr($string, $start, $length);
    }

    function mb_strtoupper(string $string, ?string $encoding = null): string
    {
        return strtoupper($string);
    }
}

function app_base_raw(): string
{
    static $base = null;

    if ($base !== null) {
        return $base;
    }

    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $root = realpath(ROOT_PATH) ?: ROOT_PATH;
    $docRoot = rtrim(str_replace('\\', '/', $docRoot), '/');
    $root = rtrim(str_replace('\\', '/', $root), '/');

    if ($docRoot !== '' && strncasecmp($root, $docRoot, strlen($docRoot)) === 0) {
        $relativo = substr($root, strlen($docRoot));
        $base = rtrim($relativo === false ? '' : $relativo, '/');
    } else {
        $base = '';
    }

    return $base;
}

function base_url(string $path = ''): string
{
    $bruto = app_base_raw();
    $prefixo = '';
    if ($bruto !== '' && $bruto !== '/') {
        $segmentosBase = array_map('rawurlencode', explode('/', trim($bruto, '/')));
        $prefixo = '/' . implode('/', $segmentosBase);
    }

    $path = ltrim($path, '/');
    if ($path === '') {
        return $prefixo === '' ? '/' : $prefixo . '/';
    }

    $query = '';
    if (str_contains($path, '?')) {
        [$path, $query] = explode('?', $path, 2);
        $query = '?' . $query;
    }

    $segmentos = array_map('rawurlencode', explode('/', $path));

    return $prefixo . '/' . implode('/', $segmentos) . $query;
}

function asset(string $path): string
{
    $relativo = 'assets/' . ltrim($path, '/');
    $url = base_url($relativo);
    $arquivo = ROOT_PATH . '/' . $relativo;
    if (is_file($arquivo)) {
        $url .= '?v=' . filemtime($arquivo);
    }

    return $url;
}

function url_upload(string $caminho): string
{
    return base_url('uploads/' . ltrim($caminho, '/'));
}

function redirect(string $path): void
{
    if (preg_match('#^https?://#i', $path)) {
        $path = 'index.php';
    }

    header('Location: ' . base_url($path));
    exit;
}

function redirecionar_referencia(string $padrao): void
{
    $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    if ($referer !== '') {
        $partes = parse_url($referer);
        $host = (string) ($partes['host'] ?? '');
        $porta = isset($partes['port']) ? ':' . $partes['port'] : '';
        $origem = $host . $porta;
        $atual = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $scheme = (string) ($partes['scheme'] ?? '');
        $mesmoHost = $origem !== '' && strcasecmp($origem, $atual) === 0;
        $schemeOk = $scheme === 'http' || $scheme === 'https';
        if ($mesmoHost && $schemeOk) {
            header('Location: ' . $referer);
            exit;
        }
    }

    redirect($padrao);
}

function url_relativa_atual(): string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $path = (string) (parse_url($uri, PHP_URL_PATH) ?? '');
    $query = parse_url($uri, PHP_URL_QUERY);
    $base = app_base_raw();
    $pathDecodificado = rawurldecode($path);

    if ($base !== '' && str_starts_with($pathDecodificado, $base)) {
        $pathDecodificado = substr($pathDecodificado, strlen($base));
    }

    $relativo = ltrim($pathDecodificado, '/');
    if (is_string($query) && $query !== '') {
        $relativo .= '?' . $query;
    }

    return $relativo;
}

function retorno_seguro(string $padrao): string
{
    $retorno = trim((string) ($_POST['retorno'] ?? ''));
    if ($retorno === '' || strlen($retorno) > 300) {
        return $padrao;
    }

    if (preg_match('#^(?:[a-z]+:)?//#i', $retorno) || str_contains($retorno, '..') || str_contains($retorno, '\\')) {
        return $padrao;
    }

    if (!preg_match('#^(index\.php|(?:funcionarios|produtos|categorias)/[a-z]+\.php)(\?[^\r\n]*)?$#i', $retorno)) {
        return $padrao;
    }

    return $retorno;
}

function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function iniciar_sessao(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    $path = app_base_raw();
    if ($path === '') {
        $path = '/';
    }

    session_name('TOYMANIASESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $path,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function encerrar_sessao(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => (bool) $params['secure'],
            'httponly' => (bool) $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verificar_csrf(): void
{
    $enviado = (string) ($_POST['csrf_token'] ?? '');
    $sessao = (string) ($_SESSION['csrf_token'] ?? '');

    if ($enviado === '' || $sessao === '' || !hash_equals($sessao, $enviado)) {
        flash('erro', 'Não foi possível validar o formulário. Atualize a página e tente novamente.');
        redirecionar_referencia('index.php');
    }
}

function flash(string $tipo, string $mensagem): void
{
    $_SESSION['flash'] = [
        'tipo' => $tipo,
        'mensagem' => $mensagem,
    ];
}

function render_flash(): void
{
    if (empty($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    $tipo = (string) ($flash['tipo'] ?? 'erro');
    if (!in_array($tipo, ['sucesso', 'erro', 'aviso'], true)) {
        $tipo = 'erro';
    }

    echo '<div class="alert alert-' . e($tipo) . '" role="status">' . e((string) ($flash['mensagem'] ?? '')) . '</div>';
}

function erro_campo(array $erros, string $campo): string
{
    if (empty($erros[$campo])) {
        return '';
    }

    return '<small class="field-error">' . e((string) $erros[$campo]) . '</small>';
}

function invalido(array $erros, string $campo): string
{
    return isset($erros[$campo]) ? ' is-invalid' : '';
}

function like(string $termo): string
{
    $termo = str_replace(['%', '_'], '', $termo);

    return '%' . $termo . '%';
}

function faixas_etarias(): array
{
    return [
        '0-2' => '0 a 2 anos',
        '3-5' => '3 a 5 anos',
        '6-8' => '6 a 8 anos',
        '9-12' => '9 a 12 anos',
        '13+' => '13 anos ou mais',
        'todas' => 'Todas as idades',
    ];
}

function rotulo_faixa(string $chave): string
{
    return faixas_etarias()[$chave] ?? $chave;
}

function rotulo_status(string $status): string
{
    return $status === 'ativo' ? 'Ativo' : 'Inativo';
}

function badge_status(string $status): string
{
    $classe = $status === 'ativo' ? 'badge-sucesso' : 'badge-neutro';

    return '<span class="badge ' . $classe . '">' . e(rotulo_status($status)) . '</span>';
}

function classe_estoque(int $quantidade): string
{
    if ($quantidade === 0) {
        return 'estoque-zero';
    }

    if ($quantidade <= 5) {
        return 'estoque-baixo';
    }

    return 'estoque-ok';
}

function dinheiro(float|string|null $valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

function data_br(?string $data): string
{
    if ($data === null || $data === '') {
        return '—';
    }

    try {
        return (new DateTime($data))->format('d/m/Y H:i');
    } catch (Exception) {
        return '—';
    }
}

function primeiro_nome(string $nome): string
{
    $partes = preg_split('/\s+/', trim($nome)) ?: [];

    return ($partes[0] ?? '') !== '' ? $partes[0] : $nome;
}

function iniciais(string $nome): string
{
    $partes = preg_split('/\s+/u', trim($nome)) ?: [];
    $letras = '';

    if (!empty($partes[0])) {
        $letras .= mb_substr($partes[0], 0, 1);
    }

    if (count($partes) > 1) {
        $letras .= mb_substr($partes[count($partes) - 1], 0, 1);
    }

    $letras = mb_strtoupper($letras);

    return $letras !== '' ? $letras : 'TM';
}

function html_thumb(?string $caminho, string $alt): string
{
    if ($caminho === null || $caminho === '') {
        return '<span class="thumb thumb-empty" aria-hidden="true">' . icon('imagem') . '</span>';
    }

    return '<span class="thumb"><img src="' . e(url_upload($caminho)) . '" alt="' . e($alt) . '"></span>';
}

function html_paginacao(int $pagina, int $totalPaginas, string $arquivo, array $params): string
{
    if ($totalPaginas <= 1) {
        return '';
    }

    $html = '<nav class="pagination" aria-label="Paginação">';

    $item = static function (int $numero, string $rotulo, bool $atual = false, bool $desativado = false) use ($arquivo, $params): string {
        if ($atual) {
            return '<span class="is-current" aria-current="page">' . e($rotulo) . '</span>';
        }

        if ($desativado) {
            return '<span class="is-disabled">' . e($rotulo) . '</span>';
        }

        $params['page'] = $numero;
        $href = base_url($arquivo . '?' . http_build_query($params));

        return '<a href="' . e($href) . '">' . e($rotulo) . '</a>';
    };

    $html .= $item($pagina - 1, 'Anterior', false, $pagina <= 1);

    $inicio = max(1, $pagina - 2);
    $fim = min($totalPaginas, $pagina + 2);

    if ($inicio > 1) {
        $html .= $item(1, '1');
    }
    if ($inicio > 2) {
        $html .= '<span>…</span>';
    }

    for ($i = $inicio; $i <= $fim; $i++) {
        $html .= $item($i, (string) $i, $i === $pagina);
    }

    if ($fim < $totalPaginas - 1) {
        $html .= '<span>…</span>';
    }
    if ($fim < $totalPaginas) {
        $html .= $item($totalPaginas, (string) $totalPaginas);
    }

    $html .= $item($pagina + 1, 'Próxima', false, $pagina >= $totalPaginas);
    $html .= '</nav>';

    return $html;
}

function envio_excedeu_limite(): bool
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return false;
    }

    $tamanho = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);

    return $tamanho > 0 && $_POST === [] && $_FILES === [];
}
