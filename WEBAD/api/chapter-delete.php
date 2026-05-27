<?php
/**
 * api/chapter-delete.php
 * Removes a chapter from comics.json and deletes its upload directory.
 * POST: csrf, slug, chapter (number)
 */
require_once dirname(__DIR__) . '/includes/functions.php';
requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /chapters'); exit; }
verifyCsrf();

$slug    = preg_replace('/[^a-z0-9-]/', '', $_POST['slug']    ?? '');
$chapter = (int)($_POST['chapter'] ?? 0);

if (!$slug || $chapter < 1) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Noto\'g\'ri so\'rov'];
    header('Location: /chapters');
    exit;
}

// Remove from comics.json
$comics = loadComics();
$found  = false;
foreach ($comics as &$c) {
    if ($c['slug'] !== $slug) continue;
    $before = count($c['chapters'] ?? []);
    $c['chapters'] = array_values(array_filter(
        $c['chapters'] ?? [],
        fn($ch) => (int)$ch['number'] !== $chapter
    ));
    $found = $before > count($c['chapters']);
    // Update latestTs
    if (!empty($c['chapters'])) {
        usort($c['chapters'], fn($a,$b) => $b['number'] - $a['number']);
        $c['latestTs'] = $c['chapters'][0]['timestamp'] ?? 0;
    } else {
        $c['latestTs'] = 0;
    }
    break;
}
unset($c);

if ($found) {
    saveComics($comics);
}

// Delete upload directory
$chapterDir = UPLOADS_DIR . '/' . $slug . '/chapter-' . $chapter;
if (is_dir($chapterDir)) {
    // Recursive delete
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($chapterDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $fileinfo) {
        $fileinfo->isDir() ? rmdir($fileinfo->getPathname()) : unlink($fileinfo->getPathname());
    }
    rmdir($chapterDir);
}

logActivity('chapter.delete', "$slug | bob $chapter");
$_SESSION['flash'] = ['type' => 'success', 'msg' => "$slug — Bob $chapter o'chirildi"];
header('Location: /chapters?slug=' . urlencode($slug));
exit;
