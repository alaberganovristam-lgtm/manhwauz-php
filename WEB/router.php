<?php
/**
 * ManhwaUZ — Main Site Router
 * Local:  php -S localhost:8090 router.php
 * Apache: .htaccess barcha so'rovlarni shu faylga yo'naltiradi
 */

$ROOT = __DIR__;

if (is_file($ROOT . '/config.php')) {
    require_once $ROOT . '/config.php';
}

if (!defined('AURA_PATH')) {
    // 1) Env var (cloud hosts: Vercel, Replit, Docker)
    // 2) Sibling folder ../asuramanga2 (local dev)
    // 3) ./data inside webroot (single-folder deploy fallback)
    $envPath = getenv('AURA_PATH') ?: ($_SERVER['AURA_PATH'] ?? '');
    if ($envPath && is_dir($envPath)) {
        define('AURA_PATH', rtrim($envPath, '/\\'));
    } elseif (is_dir(dirname(__DIR__) . '/asuramanga2')) {
        define('AURA_PATH', dirname(__DIR__) . '/asuramanga2');
    } else {
        define('AURA_PATH', __DIR__ . '/data');
        if (!is_dir(__DIR__ . '/data')) @mkdir(__DIR__ . '/data', 0755, true);
    }
}
$AURA = AURA_PATH;

// ── Security headers ─────────────────────────────────────────
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header_remove('X-Powered-By');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// ── Block direct access to sensitive files (when running via php-built-in or non-Apache hosts) ──
if (preg_match('#\.(json|env|log|sql|md|lock|bak|orig|swp|htpasswd|htaccess)$#i', $uri)
    || preg_match('#^/(users\.json|admin/|admin$|\.git|composer\.json|composer\.lock)#i', $uri)) {
    // Allow ONLY manifest.json and the public api routes
    if (!preg_match('#^/(manifest\.json|api/comics\.json|admin)#i', $uri)) {
        http_response_code(403);
        header('Content-Type: text/plain');
        echo '403 Forbidden';
        return;
    }
}

$MIMES = [
    'css'=>'text/css','js'=>'application/javascript','json'=>'application/json',
    'png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif',
    'webp'=>'image/webp','avif'=>'image/avif','svg'=>'image/svg+xml',
    'ico'=>'image/x-icon','woff'=>'font/woff','woff2'=>'font/woff2',
    'ttf'=>'font/ttf','txt'=>'text/plain','xml'=>'application/xml',
    'webmanifest'=>'application/manifest+json',
];

// ── Gzip helper ───────────────────────────────────────────────
$_canGzip = extension_loaded('zlib')
    && strpos($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip') !== false;

function serveGzippedHtml(string $html): void {
    global $_canGzip;
    if ($_canGzip) {
        $gz = gzencode($html, 5);
        header('Content-Encoding: gzip');
        header('Content-Length: ' . strlen($gz));
        header('Vary: Accept-Encoding');
        echo $gz;
    } else {
        header('Content-Length: ' . strlen($html));
        echo $html;
    }
}

// ── Static files ──────────────────────────────────────────────
$staticPath = $ROOT . $uri;
if ($uri !== '/' && is_file($staticPath) && !str_ends_with($uri, '.php') && !str_ends_with($uri, '.html')) {
    // Path traversal himoyasi: fayl yo'li ROOT ichida ekanini tasdiqlash
    $realStatic = realpath($staticPath);
    $realRoot   = realpath($ROOT);
    if (!$realStatic || !$realRoot || strncmp($realStatic, $realRoot, strlen($realRoot)) !== 0) {
        http_response_code(403);
        return;
    }
    $ext = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
    header('Content-Type: ' . ($MIMES[$ext] ?? 'application/octet-stream'));
    header('Cache-Control: public, max-age=86400');
    // Gzip text-based assets (CSS, JS, SVG, JSON)
    if ($_canGzip && in_array($ext, ['css','js','svg','json','xml','webmanifest'])) {
        $gz = gzencode(file_get_contents($realStatic), 5);
        header('Content-Encoding: gzip');
        header('Content-Length: ' . strlen($gz));
        header('Vary: Accept-Encoding');
        echo $gz;
    } else {
        header('Content-Length: ' . filesize($realStatic));
        readfile($realStatic);
    }
    return;
}

// ── Uploads from asuramanga2 ──────────────────────────────────
if (str_starts_with($uri, '/uploads/')) {
    $fp = $AURA . $uri;
    if (is_file($fp)) {
        $ext = strtolower(pathinfo($fp, PATHINFO_EXTENSION));
        header('Content-Type: ' . ($MIMES[$ext] ?? 'application/octet-stream'));
        readfile($fp);
    } else {
        http_response_code(404);
    }
    return;
}

// ── Cover images from asuramanga2 ─────────────────────────────
if (str_starts_with($uri, '/public/images/')) {
    $fp = $AURA . $uri;
    if (is_file($fp)) {
        $ext = strtolower(pathinfo($fp, PATHINFO_EXTENSION));
        header('Content-Type: ' . ($MIMES[$ext] ?? 'application/octet-stream'));
        header('Cache-Control: public, max-age=86400');
        readfile($fp);
    } else {
        http_response_code(404);
    }
    return;
}

// ── Admin panel proxy (non-Apache fallback) ──
// Looks for admin/ sub-folder OR sibling ../WEBAD/ folder
if ($uri === '/admin' || str_starts_with($uri, '/admin/')) {
    $candidates = [
        $ROOT . '/admin/router.php',           // sub-folder mode
        dirname($ROOT) . '/WEBAD/router.php',  // sibling mode (manhwauz.com layout)
    ];
    foreach ($candidates as $adminRouter) {
        if (is_file($adminRouter)) {
            $_SERVER['REQUEST_URI'] = '/' . ltrim(substr($uri, strlen('/admin')), '/')
                . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
                    ? '?' . $_SERVER['QUERY_STRING'] : '');
            chdir(dirname($adminRouter));
            require $adminRouter;
            return;
        }
    }
}

// ── Auth API ──────────────────────────────────────────────────
if (str_starts_with($uri, '/api/auth/')) {
    $_GET['action'] = substr($uri, strlen('/api/auth/'));
    require $ROOT . '/auth.php';
    return;
}

// ── Bookmarks API ─────────────────────────────────────────────
if ($uri === '/api/bookmarks' || str_starts_with($uri, '/api/bookmarks/')) {
    if (preg_match('#^/api/bookmarks/([a-z0-9-]+)$#', $uri, $bm)) {
        $_GET['slug'] = $bm[1];
    }
    require $ROOT . '/bookmarks.php';
    return;
}

// ── Comments API ──────────────────────────────────────────────
if (preg_match('#^/api/comments/([a-z0-9-]+)(?:/([a-z0-9]+))?$#', $uri, $cm)) {
    $_GET['slug'] = $cm[1];
    $_GET['id']   = $cm[2] ?? '';
    require $ROOT . '/comments.php';
    return;
}

// ── Ranking sahifasi ──────────────────────────────────────────
if ($uri === '/ranking') {
    ob_start();
    require $ROOT . '/ranking.php';
    serveGzippedHtml(patchHtmlStr(ob_get_clean(), $ROOT));
    return;
}

// ── Bookmarks sahifasi ────────────────────────────────────────
if ($uri === '/bookmarks') {
    ob_start();
    require $ROOT . '/bookmarks-page.php';
    serveGzippedHtml(patchHtmlStr(ob_get_clean(), $ROOT));
    return;
}

// ── Login / Register pages ────────────────────────────────────
if ($uri === '/login' || $uri === '/login.html') {
    $_GET['mode'] = 'login';
    require $ROOT . '/login-page.php';
    return;
}
if ($uri === '/register' || $uri === '/register.html') {
    $_GET['mode'] = 'register';
    require $ROOT . '/login-page.php';
    return;
}

// ── Comics API ────────────────────────────────────────────────
if ($uri === '/api/comics.json') {
    $fp = $AURA . '/data/comics.json';
    if (is_file($fp)) {
        header('Content-Type: application/json');
        header('Cache-Control: public, max-age=60');
        $cjson = ltrim(file_get_contents($fp), "\xEF\xBB\xBF");
        if ($_canGzip) {
            $gz = gzencode($cjson, 5);
            header('Content-Encoding: gzip');
            header('Content-Length: ' . strlen($gz));
            header('Vary: Accept-Encoding');
            echo $gz;
        } else {
            header('Content-Length: ' . strlen($cjson));
            echo $cjson;
        }
    } else {
        http_response_code(404);
        echo '[]';
    }
    return;
}

// ── Reader: /reader/slug/N ────────────────────────────────────
if (preg_match('#^/reader/([^/]+)/(\d+)$#', $uri, $m)) {
    $readerSlug = $m[1];
    $readerCh   = (int)$m[2];
    ob_start();
    require $ROOT . '/reader.php';
    serveGzippedHtml(ob_get_clean());
    return;
}

// ── Browse ────────────────────────────────────────────────────
if ($uri === '/browse' || $uri === '/browse.html') {
    serveGzippedHtml(patchHtml($ROOT . '/browse.html', $ROOT, 'index'));
    return;
}

// ── Comic detail: /comics/slug ────────────────────────────────
if (preg_match('#^/comics/([^/?.]+)$#', $uri, $m)) {
    $slug = $m[1];

    $_dj    = ltrim(file_get_contents($AURA . '/data/comics.json'), "\xEF\xBB\xBF");
    $comics = json_decode($_dj, true) ?? [];
    $comic  = null;
    foreach ($comics as $c) { if ($c['slug'] === $slug) { $comic = $c; break; } }

    if ($comic) {
        $_GET['slug'] = $slug;
        ob_start();
        require $ROOT . '/comic-dynamic.php';
        serveGzippedHtml(patchHtmlStr(ob_get_clean(), $ROOT));
    } else {
        $files = glob($ROOT . '/comics/' . $slug . '-????????.html');
        if ($files) {
            serveGzippedHtml(patchHtml($files[0], $ROOT, 'comic'));
        } else {
            http_response_code(404);
            serveGzippedHtml('<!DOCTYPE html><html><body style="font-family:sans-serif;background:#13111A;color:#fff;text-align:center;padding:5rem"><h1 style="font-size:3rem;color:#913FE2">404</h1><p>Komiks topilmadi: <b>' . htmlspecialchars($slug) . '</b></p><a href="/" style="color:#913FE2">← Bosh sahifa</a></body></html>');
        }
    }
    return;
}

// ── Home ──────────────────────────────────────────────────────
if ($uri === '/' || $uri === '/index.html') {
    serveGzippedHtml(patchHtml($ROOT . '/index.html', $ROOT, 'index'));
    return;
}

// ── Other HTML pages ──────────────────────────────────────────
$htmlFile = $ROOT . $uri;
if (!str_ends_with($uri, '.html')) $htmlFile .= '.html';
if (is_file($htmlFile)) {
    serveGzippedHtml(patchHtml($htmlFile, $ROOT, 'index'));
    return;
}

// ── 404 ──────────────────────────────────────────────────────
http_response_code(404);
serveGzippedHtml('<!DOCTYPE html><html><body style="font-family:sans-serif;background:#13111A;color:#fff;text-align:center;padding:5rem"><h1 style="font-size:5rem;color:#913FE2">404</h1><p><a href="/" style="color:#913FE2">Home</a></p></body></html>');


/* ════════════════════════════════════════════════
   HTML PATCHING
   ════════════════════════════════════════════════ */

function patchHtmlStr(string $html, string $root): string
{
    return _applyPatches($html, $root, 'comic');
}

function patchHtml(string $file, string $root, string $ctx = 'index'): string
{
    if (!is_file($file)) return '<h1>File not found: ' . htmlspecialchars($file) . '</h1>';
    $html = file_get_contents($file);
    return _applyPatches($html, $root, $ctx);
}

function _applyPatches(string $html, string $root, string $ctx = 'index'): string
{
    // ── Strip dead astro-island hydration data ────────────────
    // React JS files are missing — hydration never fires.
    // props= can be 300+ KB of JSON per page. Safe to remove.
    $html = preg_replace('/\s+props="[^"]*"/', '', $html);
    $html = preg_replace('/\s+renderer-url="[^"]*"/', '', $html);
    $html = preg_replace('/\s+component-url="[^"]*"/', '', $html);
    $html = preg_replace('/\s+component-export="[^"]*"/', '', $html);

    // ── Remove external tracking/analytics scripts ────────────
    $html = preg_replace('#<script[^>]+cloudflareinsights\.com[^>]*>.*?</script>#s', '', $html);
    $html = preg_replace('#<script[^>]+data-cf-beacon[^>]*>.*?</script>#s', '', $html);
    // Remove status widget (asurascans status page)
    $html = preg_replace('#<script[^>]+status\.asurascans\.com[^>]*>.*?</script>#s', '', $html);

    // 1. Chapter links: https://asurascans.com/comics/SLUG-HASH/chapter/N → /reader/SLUG/N
    $html = preg_replace(
        '#https?://asurascans\.com/comics/([a-z0-9-]+)-[a-f0-9]{8}/chapter/(\d+)#',
        '/reader/$1/$2', $html
    );

    // 2. Absolute comic links → /comics/SLUG
    $html = preg_replace(
        '#https?://asurascans\.com/comics/([a-z0-9-]+)-[a-f0-9]{8}/?(?=["\s])#',
        '/comics/$1', $html
    );

    // 3. Relative comic HTML links from index: comics/SLUG-HASH.html
    $html = preg_replace(
        '#href="comics/([a-z0-9-]+)-[a-f0-9]{8}\.html#',
        'href="/comics/$1', $html
    );

    // 4. Same-dir comic links from comic pages
    if ($ctx === 'comic') {
        $html = preg_replace(
            '#href="([a-z0-9][a-z0-9-]+)-[a-f0-9]{8}\.html#',
            'href="/comics/$1', $html
        );
        $html = preg_replace('#(href|src)="\.\./([^"]+)"#', '$1="/$2"', $html);
    }

    // 5. Browse hash variants → /browse
    $html = preg_replace(
        '#href="/?(?:\.\.\/)?browse[a-f0-9]{4}\.html(\?[^"]*)?#',
        'href="/browse$1', $html
    );
    $html = str_replace(
        ['href="browse.html"','href="../browse.html"','href="/browse.html"'],
        'href="/browse"', $html
    );

    // 6. Home links
    $html = str_replace(
        ['href="index.html"','href="../index.html"','href="/index.html"'],
        'href="/"', $html
    );

    // 7. Bookmarks → /bookmarks
    $html = preg_replace('#href="/?(?:\.\.\/)?bookmarks\.html"#', 'href="/bookmarks"', $html);

    // 7b. Announcements → home
    $html = str_replace(['href="announcements.html"','href="/announcements.html"'], 'href="/"', $html);
    $html = preg_replace('#href="/?announcement/[^"]*"#', 'href="/"', $html);

    // 8. External browse links
    $html = preg_replace('#https?://asurascans\.com/browse(\?[^"]*)?#', '/browse$1', $html);

    // 9. Fix body opacity
    $html = preg_replace('/style="([^"]*?)opacity:0([^"]*?)"/', 'style="${1}opacity:1${2}"', $html);

    // 10. Inject _fix.js before </body>
    $fixVer = filemtime(__DIR__ . '/_fix.js');
    $html = str_replace('</body>', '<script src="/_fix.js?v=' . $fixVer . '"></script></body>', $html);

    // 11. Remove HTTrack comment
    $html = preg_replace('/<!-- Mirrored from .+?-->/s', '', $html);

    // Remove Astro prefetch
    $html = str_replace(' data-astro-prefetch="hover"', '', $html);
    $html = str_replace(' data-astro-prefetch', '', $html);

    // ── Rebranding: Asura Scans → Manhwa UZ ──────────────────
    $html = preg_replace('/<title>([^<]*?)Asura Scans([^<]*?)<\/title>/',
        '<title>$1Manhwa UZ$2</title>', $html);

    $html = str_replace('>Asura Scans</span>', '>Manhwa UZ</span>', $html);
    $html = str_replace(
        '&copy; 2026 Asura Scans. All rights reserved.',
        '&copy; 2026 Manhwa UZ. All rights reserved.',
        $html
    );

    $html = str_replace('alt="Asura Scans"', 'alt="Manhwa UZ"', $html);
    $html = str_replace('"Asura Scans"', '"Manhwa UZ"', $html);
    $html = str_replace('>Asura Scans<', '>Manhwa UZ<', $html);
    $html = str_replace('content="Asura Scans"', 'content="Manhwa UZ"', $html);
    $html = preg_replace('/content="Asura Scans([^"]+)"/', 'content="Manhwa UZ$1"', $html);
    $html = str_replace('"url":"https://asurascans.com"', '"url":"https://manhwauz.com"', $html);
    $html = str_replace('"url":"https://asurascans.com/', '"url":"https://manhwauz.com/', $html);
    $html = preg_replace('#<link[^>]+apple-touch-icon[^>]*>#', '', $html);

    $logoSpan = '<span style="display:inline-flex;align-items:center;gap:.5rem;text-decoration:none">'
        . '<span style="width:40px;height:40px;border-radius:50%;background:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:900;font-size:.9rem;color:#913FE2;flex-shrink:0;letter-spacing:-.5px">MU</span>'
        . '<span style="font-size:1rem;font-weight:700;color:#fff;letter-spacing:-.02em;white-space:nowrap">Manhwa UZ</span>'
        . '</span>';
    $html = preg_replace(
        '#<img[^>]+asurascans\.com/images/logo\.webp[^>]*>#',
        $logoSpan,
        $html
    );

    $html = preg_replace('/<div[^>]*>[^<]*<a[^>]*toraka\.com[^>]*>.*?<\/a>[^<]*<\/div>/s', '', $html);

    $html = preg_replace('#href="/?([a-z0-9][a-z0-9-]+)\.html([^"]*)"#', 'href="/$1$2"', $html);

    $html = preg_replace('#<link[^>]+asurascans\.com/images/logo\.webp[^>]*>#', '', $html);
    $html = str_replace('</head>',
        '<meta name="referrer" content="no-referrer">'
        . '<link rel="icon" href="data:image/svg+xml,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 32 32\'><rect width=\'32\' height=\'32\' rx=\'16\' fill=\'%23913FE2\'/><text x=\'50%25\' y=\'55%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' fill=\'white\' font-weight=\'800\' font-size=\'13\' font-family=\'sans-serif\'>MU</text></svg>">'
        . '</head>',
        $html
    );

    // 13. Hero carousel fix
    $heroFix = '<style id="_fix-hero">'
        . '.hero-skeleton{opacity:0!important;pointer-events:none!important}'
        . '.embla-hero{opacity:1!important;cursor:grab}'
        . '.embla-hero__slide .slide-link{pointer-events:auto!important}'
        . '.embla-trending{opacity:1!important}'
        . '.embla-trending__container{transition:none}'
        . '</style>';
    $html = str_replace('</head>', $heroFix . '</head>', $html);

    return $html;
}
