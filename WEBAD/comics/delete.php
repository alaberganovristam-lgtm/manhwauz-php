<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /comics'); exit; }
verifyCsrf();

$slug   = preg_replace('/[^a-z0-9-]/', '', $_POST['slug'] ?? '');
$comics = loadComics();
$title  = '';
$comics = array_filter($comics, function($c) use ($slug, &$title) {
    if ($c['slug'] === $slug) { $title = $c['title']; return false; }
    return true;
});
saveComics(array_values($comics));

// Delete chapter upload directory
$uploadDir = UPLOADS_DIR . '/' . $slug;
if (is_dir($uploadDir)) {
    deleteDir($uploadDir);
}

// Delete cover image if local
$comic = getComic($slug); // won't find (already deleted), cover path lost
// Cover cleanup done via slug pattern
foreach (glob(COVERS_DIR . '/' . $slug . '.*') ?: [] as $coverFile) {
    unlink($coverFile);
}

logActivity('comic.delete', $title . ' (' . $slug . ')');
$_SESSION['flash'] = ['type' => 'success', 'msg' => '"' . $title . '" o\'chirildi'];
header('Location: /comics');
exit;
