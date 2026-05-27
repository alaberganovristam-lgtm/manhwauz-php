<?php
/**
 * ManhwaUZ Admin Panel Router
 * Local:  php -S localhost:8091 router.php
 * Apache: .htaccess barcha so'rovlarni shu faylga yo'naltiradi
 *
 * Papka strukturasi (production):
 *   public_html/          ← manhwauz.com (asurascans.com fayllari)
 *   public_html/admin/    ← bu fayl shu yerda
 *   asuramanga2/          ← public_html bilan yonma-yon (web-accessible EMAS)
 */

// ── Security headers (admin panel) ───────────────────────────
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store, no-cache, must-revalidate');
header_remove('X-Powered-By');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// ── PHP limits for large uploads ──────────────────────────────
ini_set('max_file_uploads',  '350');
ini_set('upload_max_filesize', '30M');
ini_set('post_max_size',     '600M');
ini_set('memory_limit',      '512M');
ini_set('max_execution_time','600');
ini_set('max_input_time',    '600');

define('ROOT',     __DIR__);
define('DATA_DIR', ROOT . '/data');

// ── asuramanga2 yo'lini aniqlash ──────────────────────────────
// 1-usul: asurascans.com/config.php mavjud bo'lsa (AURA_PATH oldindan aniqlanadi)
$_mainConfig = ROOT . '/../config.php';  // admin/../config.php = asurascans.com/config.php
if (is_file($_mainConfig) && !defined('AURA_PATH')) {
    require_once $_mainConfig;
}

// 2-usul: config.php yo'q — yo'lni avtomatik hisoblash
if (!defined('AURA_PATH')) {
    // Local (php -S): admin/ — asurascans.com/ bilan yonma-yon
    //   dirname(__DIR__) = asurascans.com/../ = proekt root → /asuramanga2
    // Production (admin pastki papka sifatida: public_html/admin/):
    //   dirname(__DIR__)         = public_html/
    //   dirname(dirname(__DIR__))= /home/user/ → /home/user/asuramanga2
    $tryLocal = dirname(__DIR__) . '/asuramanga2';
    $tryProd  = dirname(dirname(__DIR__)) . '/asuramanga2';
    if (is_dir($tryLocal . '/data')) {
        define('AURA_PATH', $tryLocal);   // local dev
    } elseif (is_dir($tryProd . '/data')) {
        define('AURA_PATH', $tryProd);    // production
    } else {
        define('AURA_PATH', $tryLocal);   // fallback
    }
}

define('SITE_ROOT',   AURA_PATH);
define('COMICS_JSON', SITE_ROOT . '/data/comics.json');
define('COVERS_DIR',  SITE_ROOT . '/public/images/covers');
define('UPLOADS_DIR', SITE_ROOT . '/uploads');

// ── Site URL (main site, for cover previews & external links) ──
if (!defined('SITE_URL')) {
    $_host = $_SERVER['HTTP_HOST'] ?? 'localhost:8091';
    if ($_host === 'localhost:8091') {
        define('SITE_URL', 'http://localhost:8090');
    } else {
        $_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        define('SITE_URL', $_scheme . '://' . $_host);
    }
}

session_name('mu_admin');
session_start();

// ── Session timeout (2 hours inactivity) ─────────────────────
if (!empty($_SESSION['admin_id'])) {
    $lastActive = $_SESSION['last_active'] ?? time();
    if (time() - $lastActive > 7200) {
        session_unset(); session_destroy(); session_start();
    } else {
        $_SESSION['last_active'] = time();
    }
}

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// ── Static assets ─────────────────────────────────────────────
$staticPath = ROOT . $uri;
if ($uri !== '/' && is_file($staticPath) && !str_ends_with($uri, '.php')) {
    $ext  = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
    $mime = ['css'=>'text/css','js'=>'application/javascript',
             'png'=>'image/png','jpg'=>'image/jpeg','webp'=>'image/webp',
             'gif'=>'image/gif','svg'=>'image/svg+xml','ico'=>'image/x-icon',
             'woff2'=>'font/woff2','woff'=>'font/woff'][$ext] ?? 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=3600');
    readfile($staticPath);
    return;
}

// ── Route map ─────────────────────────────────────────────────
$routes = [
    '/'                   => 'index.php',
    '/dashboard'          => 'index.php',
    '/login'              => 'login.php',
    '/logout'             => 'logout.php',
    '/comics'             => 'comics/index.php',
    '/comics/create'      => 'comics/edit.php',
    '/comics/edit'        => 'comics/edit.php',
    '/comics/delete'      => 'comics/delete.php',
    '/chapters'           => 'chapters/index.php',
    '/chapters/upload'    => 'chapters/upload.php',
    '/chapters/delete'    => 'chapters/delete.php',
    '/users'              => 'users/index.php',
    '/logs'               => 'logs/index.php',
    '/settings'           => 'settings/index.php',
    '/api/upload-chunk'   => 'api/upload-chunk.php',
    '/api/upload-cover'   => 'api/upload-cover.php',
    '/api/comic-save'     => 'api/comic-save.php',
    '/api/comic-delete'   => 'api/comic-delete.php',
    '/api/chapter-delete' => 'api/chapter-delete.php',
    '/api/stats'          => 'api/stats.php',
    '/api/admin-save'     => 'api/admin-save.php',
    '/api/log-clear'      => 'api/log-clear.php',
];

$file = $routes[$uri] ?? null;
if ($file && is_file(ROOT . '/' . $file)) {
    require ROOT . '/' . $file;
} else {
    http_response_code(404);
    echo '<!DOCTYPE html><html><body style="font-family:sans-serif;background:#0D0B14;color:#fff;text-align:center;padding:5rem">
    <h1 style="font-size:4rem;color:#913FE2">404</h1><p><a href="/" style="color:#913FE2">Dashboard</a></p></body></html>';
}
