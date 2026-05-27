<?php
/**
 * api/upload-chunk.php
 * Receives a batch of images, converts to chosen format, saves to uploads/
 * POST params:
 *   csrf, slug, chapter, format (webp|jpg|pdf), quality, autoNum, startIdx
 *   images[] — up to 20 files per request
 */
require_once dirname(__DIR__) . '/includes/functions.php';
requireAuth();
verifyCsrf();

header('Content-Type: application/json');

$slug     = preg_replace('/[^a-z0-9-]/', '', strtolower($_POST['slug']    ?? ''));
$chapter  = (int)($_POST['chapter']  ?? 0);
$fmt      = in_array($_POST['format'] ?? '', ['webp','jpg','pdf']) ? $_POST['format'] : 'webp';
$quality  = max(50, min(100, (int)($_POST['quality'] ?? 85)));
$autoNum  = ($_POST['autoNum'] ?? '1') === '1';
$startIdx = (int)($_POST['startIdx'] ?? 0);

if (!$slug || $chapter < 1) jsonError('slug yoki chapter noto\'g\'ri');
if (empty($_FILES['images'])) jsonError('Fayllar topilmadi');

// ── Magic bytes — haqiqiy rasm fayl ekanligini tekshirish ────
function isRealImage(string $path): bool {
    $f = @fopen($path, 'rb');
    if (!$f) return false;
    $header = fread($f, 12);
    fclose($f);
    // JPEG: FF D8 FF
    if (str_starts_with($header, "\xFF\xD8\xFF")) return true;
    // PNG:  89 50 4E 47
    if (str_starts_with($header, "\x89PNG")) return true;
    // GIF:  47 49 46 38
    if (str_starts_with($header, 'GIF8')) return true;
    // WebP: 52 49 46 46 ... 57 45 42 50
    if (str_starts_with($header, 'RIFF') && substr($header, 8, 4) === 'WEBP') return true;
    // AVIF / HEIF: ftyp box
    if (substr($header, 4, 4) === 'ftyp') return true;
    return false;
}

// Max fayl hajmi: 15 MB
define('MAX_IMG_SIZE', 15 * 1024 * 1024);

// Target directory
$chapterDir = UPLOADS_DIR . '/' . $slug . '/chapter-' . $chapter;
if (!is_dir($chapterDir)) {
    mkdir($chapterDir, 0755, true);
}

$files   = $_FILES['images'];
$count   = count($files['name']);
$results = [];

// For PDF mode: collect converted JPEGs, then build PDF at end
$pdfImages = [];

for ($i = 0; $i < $count; $i++) {
    $origName = $files['name'][$i];
    $tmpPath  = $files['tmp_name'][$i];
    $error    = $files['error'][$i];

    if ($error !== UPLOAD_ERR_OK) {
        $results[] = ['ok' => false, 'file' => $origName, 'error' => 'Upload hatosi: ' . $error];
        continue;
    }

    if (!is_uploaded_file($tmpPath)) {
        $results[] = ['ok' => false, 'file' => $origName, 'error' => 'Noto\'g\'ri fayl'];
        continue;
    }

    // Fayl hajmini tekshirish
    if ($files['size'][$i] > MAX_IMG_SIZE) {
        $results[] = ['ok' => false, 'file' => $origName, 'error' => 'Fayl juda katta (max 15 MB)'];
        continue;
    }

    // Magic bytes — haqiqiy rasm ekanligini tekshirish (MIME aldashdan himoya)
    if (!isRealImage($tmpPath)) {
        $results[] = ['ok' => false, 'file' => $origName, 'error' => 'Rasm fayl emas yoki buzilgan'];
        continue;
    }

    // Determine output filename
    $globalIdx = $startIdx + $i + 1; // 1-based
    if ($autoNum) {
        $baseName = str_pad($globalIdx, 3, '0', STR_PAD_LEFT);
    } else {
        $baseName = pathinfo($origName, PATHINFO_FILENAME);
        // Sanitize
        $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
    }

    if ($fmt === 'pdf') {
        // Convert to tmp JPEG for PDF assembly
        $tmpJpg = sys_get_temp_dir() . '/mu_' . uniqid() . '.jpg';
        $ok = convertImage($tmpPath, $tmpJpg, 'jpg', $quality);
        if ($ok) {
            $pdfImages[] = ['path' => $tmpJpg, 'idx' => $globalIdx, 'orig' => $origName];
            $results[]   = ['ok' => true, 'file' => $origName, 'saved' => $baseName . '.jpg (PDF için)'];
        } else {
            $results[] = ['ok' => false, 'file' => $origName, 'error' => 'Konvertatsiya xatosi'];
        }
    } else {
        $ext      = $fmt; // webp or jpg
        $destName = $baseName . '.' . $ext;
        $destPath = $chapterDir . '/' . $destName;

        $ok = convertImage($tmpPath, $destPath, $fmt, $quality);
        if ($ok) {
            $results[] = ['ok' => true, 'file' => $origName, 'saved' => $destName];
        } else {
            // Fallback: just copy original if GD fails
            $fallbackName = $baseName . '.' . strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (move_uploaded_file($tmpPath, $chapterDir . '/' . $fallbackName)) {
                $results[] = ['ok' => true, 'file' => $origName, 'saved' => $fallbackName . ' (original, konvertatsiya bajarilmadi)'];
            } else {
                $results[] = ['ok' => false, 'file' => $origName, 'error' => 'Saqlash xatosi'];
            }
        }
    }
}

// PDF: if last batch finished, build final PDF
// We detect "last batch" if a special flag is sent, but here we just build PDF per-batch
// (full PDF is built when all files are assembled — handled by client sending finalize request)
// For simplicity: build PDF per batch, named chapter-N-part-M.pdf
// Client sends finalize after all batches; here we just save temp jpegs and return
if ($fmt === 'pdf' && !empty($pdfImages)) {
    // Save temp jpeg paths to a session file for later finalization
    $tempIndex = $chapterDir . '/.pdf_parts.json';
    $existing  = is_file($tempIndex) ? (json_decode(file_get_contents($tempIndex), true) ?? []) : [];
    foreach ($pdfImages as $pi) {
        $existing[] = $pi;
    }
    // Sort by index
    usort($existing, fn($a,$b) => $a['idx'] - $b['idx']);
    file_put_contents($tempIndex, json_encode($existing));
}

// Update comics.json to record chapter if not already there
$comics = loadComics();
foreach ($comics as &$c) {
    if ($c['slug'] !== $slug) continue;
    $chNums = array_column($c['chapters'] ?? [], 'number');
    if (!in_array($chapter, $chNums)) {
        array_unshift($c['chapters'], [
            'number'    => $chapter,
            'date'      => date('Y-m-d'),
            'timestamp' => time(),
        ]);
    }
    // Sort desc
    usort($c['chapters'], fn($a,$b) => $b['number'] - $a['number']);
    $c['latestTs']    = time();
    // Keep episodeCount in sync (used by comic.php for display)
    $c['episodeCount'] = count($c['chapters']);
    break;
}
unset($c);
saveComics($comics);

logActivity('chapter.upload', "$slug | bob $chapter | $count fayl | $fmt");

echo json_encode(['ok' => true, 'results' => $results, 'dir' => "uploads/$slug/chapter-$chapter"]);
