<?php
/**
 * Admin shared functions
 */

// ── Auth ──────────────────────────────────────────────────────
function isLoggedIn(): bool {
    return !empty($_SESSION['admin_id']);
}

function requireAuth(): void {
    if (!isLoggedIn()) {
        header('Location: /login');
        exit;
    }
}

function currentAdmin(): array {
    return $_SESSION['admin'] ?? [];
}

// ── CSRF ──────────────────────────────────────────────────────
function csrfToken(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf'];
}

function csrfInput(): string {
    return '<input type="hidden" name="csrf" value="' . csrfToken() . '">';
}

function verifyCsrf(): void {
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        jsonError('CSRF token noto\'g\'ri');
    }
}

// ── Rate limiting / Brute-force ───────────────────────────────
function checkBruteForce(string $ip): bool {
    $file = DATA_DIR . '/attempts.json';
    $data = is_file($file) ? (json_decode(file_get_contents($file), true) ?? []) : [];
    $now  = time();
    // Clean old entries (>15 min)
    $data = array_filter($data, fn($e) => ($now - $e['time']) < 900);
    $attempts = array_filter($data, fn($e) => $e['ip'] === $ip);
    return count($attempts) >= 5;
}

function recordFailedAttempt(string $ip): void {
    $file = DATA_DIR . '/attempts.json';
    $data = is_file($file) ? (json_decode(file_get_contents($file), true) ?? []) : [];
    $now  = time();
    $data = array_filter($data, fn($e) => ($now - $e['time']) < 900);
    $data[] = ['ip' => $ip, 'time' => $now];
    file_put_contents($file, json_encode(array_values($data)));
}

function clearAttempts(string $ip): void {
    $file = DATA_DIR . '/attempts.json';
    $data = is_file($file) ? (json_decode(file_get_contents($file), true) ?? []) : [];
    $data = array_filter($data, fn($e) => $e['ip'] !== $ip);
    file_put_contents($file, json_encode(array_values($data)));
}

// ── Activity log ──────────────────────────────────────────────
function logActivity(string $action, string $detail = ''): void {
    $admin = $_SESSION['admin']['username'] ?? 'unknown';
    $ip    = $_SERVER['REMOTE_ADDR'] ?? '-';
    $line  = implode(' | ', [
        date('Y-m-d H:i:s'), $admin, $ip, $action, $detail
    ]) . PHP_EOL;
    file_put_contents(DATA_DIR . '/activity.log', $line, FILE_APPEND | LOCK_EX);
}

// ── Comics data ───────────────────────────────────────────────
function loadComics(): array {
    if (!is_file(COMICS_JSON)) return [];
    return json_decode(file_get_contents(COMICS_JSON), true) ?? [];
}

function saveComics(array $comics): void {
    file_put_contents(COMICS_JSON,
        json_encode(array_values($comics), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
}

function getComic(string $slug): ?array {
    foreach (loadComics() as $c) {
        if ($c['slug'] === $slug) return $c;
    }
    return null;
}

function slugify(string $title): string {
    $s = strtolower(trim($title));
    $s = preg_replace('/[^a-z0-9\s-]/', '', $s);
    $s = preg_replace('/[\s-]+/', '-', $s);
    return trim($s, '-');
}

// ── Image conversion ──────────────────────────────────────────
function convertImage(string $src, string $dest, string $fmt, int $quality = 85): bool {
    if (!function_exists('imagecreatefromjpeg')) return false;
    $info = @getimagesize($src);
    if (!$info) return false;

    $img = match($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($src),
        IMAGETYPE_PNG  => @imagecreatefrompng($src),
        IMAGETYPE_GIF  => @imagecreatefromgif($src),
        IMAGETYPE_WEBP => @imagecreatefromwebp($src),
        IMAGETYPE_BMP  => @imagecreatefrombmp($src),
        default        => false,
    };
    if (!$img) return false;

    // Preserve transparency for PNG sources
    if ($info[2] === IMAGETYPE_PNG && $fmt !== 'jpg') {
        imagealphablending($img, false);
        imagesavealpha($img, true);
    }

    $ok = match($fmt) {
        'webp'  => imagewebp($img, $dest, $quality),
        'jpg'   => imagejpeg($img, $dest, $quality),
        'png'   => imagepng($img, $dest),
        default => false,
    };
    imagedestroy($img);
    return $ok;
}

// ── Simple PDF writer (pure PHP, no library needed) ───────────
function imagesToPdf(array $imagePaths, string $destFile): bool {
    $pages = [];
    foreach ($imagePaths as $imgPath) {
        if (!is_file($imgPath)) continue;
        $info = @getimagesize($imgPath);
        if (!$info) continue;

        // Convert to JPEG first (PDF embeds JPEG natively)
        $tmpJpg = sys_get_temp_dir() . '/' . uniqid('pdf_') . '.jpg';
        $img = match($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($imgPath),
            IMAGETYPE_PNG  => @imagecreatefrompng($imgPath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($imgPath),
            IMAGETYPE_GIF  => @imagecreatefromgif($imgPath),
            default        => false,
        };
        if (!$img) continue;
        imagejpeg($img, $tmpJpg, 90);
        imagedestroy($img);

        $jpgData = file_get_contents($tmpJpg);
        unlink($tmpJpg);
        if (!$jpgData) continue;

        [$w, $h] = $info;
        $pages[] = ['data' => $jpgData, 'w' => $w, 'h' => $h];
    }
    if (empty($pages)) return false;

    // Build minimal PDF
    $pdf = "%PDF-1.4\n";
    $offsets = [];

    // Objects: catalog=1, pages=2, then per-page: page=3+i*3, content=4+i*3, img=5+i*3
    $nPages = count($pages);

    $obj = function(int $n, string $body) use (&$pdf, &$offsets): void {
        $offsets[$n] = strlen($pdf);
        $pdf .= "$n 0 obj\n$body\nendobj\n";
    };

    $obj(1, "<</Type /Catalog /Pages 2 0 R>>");

    // Pages object
    $kids = implode(' ', array_map(fn($i) => (3 + $i * 3) . ' 0 R', range(0, $nPages - 1)));
    $obj(2, "<</Type /Pages /Kids [$kids] /Count $nPages>>");

    foreach ($pages as $i => $pg) {
        $pw   = round($pg['w'] * 0.75, 2); // px→pt (72dpi)
        $ph   = round($pg['h'] * 0.75, 2);
        $pObj = 3 + $i * 3;
        $cObj = 4 + $i * 3;
        $iObj = 5 + $i * 3;

        $obj($pObj, "<</Type /Page /Parent 2 0 R /MediaBox [0 0 $pw $ph] /Contents $cObj 0 R /Resources <</XObject <</Img $iObj 0 R>>>>>>");

        $stream = "q $pw 0 0 $ph 0 0 cm /Img Do Q";
        $obj($cObj, "<</Length " . strlen($stream) . ">>\nstream\n$stream\nendstream");

        $len = strlen($pg['data']);
        $obj($iObj, "<</Type /XObject /Subtype /Image /Width {$pg['w']} /Height {$pg['h']} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length $len>>\nstream\n" . $pg['data'] . "\nendstream");
    }

    $xrefPos = strlen($pdf);
    $total   = 2 + $nPages * 3 + 1; // objects count + 0
    $pdf    .= "xref\n0 $total\n0000000000 65535 f \n";
    for ($i = 1; $i < $total; $i++) {
        $pdf .= str_pad((string)($offsets[$i] ?? 0), 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    }
    $pdf .= "trailer\n<</Size $total /Root 1 0 R>>\nstartxref\n$xrefPos\n%%EOF";

    return file_put_contents($destFile, $pdf, LOCK_EX) !== false;
}

// ── Cover URL helper ─────────────────────────────────────────
function coverUrl(string $path): string {
    if (!$path) return '';
    if (str_starts_with($path, 'http')) return $path;  // CDN URL — use as-is
    return SITE_URL . $path;                            // local path via main site
}

// ── Delete directory recursively ─────────────────────────────
function deleteDir(string $dir): bool {
    if (!is_dir($dir)) return true;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
    return rmdir($dir);
}

// ── Helpers ───────────────────────────────────────────────────
function jsonOk(array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['ok' => true], $data));
    exit;
}

function jsonError(string $msg, int $code = 400): void {
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

function formatBytes(int $bytes): string {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . ' GB';
    if ($bytes >= 1048576)    return round($bytes / 1048576,    1) . ' MB';
    if ($bytes >= 1024)       return round($bytes / 1024,       1) . ' KB';
    return $bytes . ' B';
}

function timeAgo(int $ts): string {
    $diff = time() - $ts;
    if ($diff < 60)   return $diff . 's oldin';
    if ($diff < 3600) return round($diff/60) . ' daq oldin';
    if ($diff < 86400)return round($diff/3600) . ' soat oldin';
    return round($diff/86400) . ' kun oldin';
}
