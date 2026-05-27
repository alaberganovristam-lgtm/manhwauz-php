<?php
/**
 * api/comic-save.php
 * Creates or updates a comic entry in comics.json.
 * POST params (FormData):
 *   csrf, oldSlug (empty = new), title, slug, author, artist,
 *   status, type, description, rating, viewCount, year,
 *   genres[], coverPath (path returned by upload-cover)
 */
require_once dirname(__DIR__) . '/includes/functions.php';
requireAuth();
verifyCsrf();

header('Content-Type: application/json');

// ── Input validation ──────────────────────────────────────────
$title = trim($_POST['title'] ?? '');
if (!$title) jsonError('Sarlavha kiritilishi shart');

$oldSlug = preg_replace('/[^a-z0-9-]/', '', $_POST['oldSlug'] ?? '');
$isNew   = $oldSlug === '';

// Slug: use provided or auto-generate from title
$slug = trim($_POST['slug'] ?? '');
if (!$slug) {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s]+/', '-', $slug);
    $slug = trim(preg_replace('/-+/', '-', $slug), '-');
}
$slug = preg_replace('/[^a-z0-9-]/', '', $slug);

if (!$slug) jsonError('Slug hosil qilib bo\'lmadi');

// ── Load existing comics ──────────────────────────────────────
$comics = loadComics();

// Check slug uniqueness for new comics (or if slug changed)
if ($isNew || $slug !== $oldSlug) {
    foreach ($comics as $c) {
        if ($c['slug'] === $slug) {
            jsonError('Bu slug allaqachon mavjud: ' . $slug);
        }
    }
}

// ── Build comic data ──────────────────────────────────────────
$genres = array_values(array_filter(array_map('trim', $_POST['genres'] ?? [])));

$now = time();

if ($isNew) {
    // New comic — start fresh
    $comic = [
        'slug'         => $slug,
        'title'        => $title,
        'author'       => trim($_POST['author'] ?? ''),
        'illustrator'  => trim($_POST['illustrator'] ?? ''),
        'status'       => $_POST['status'] ?? 'Ongoing',
        'type'         => $_POST['type']   ?? 'Manhwa',
        'description'  => trim($_POST['description'] ?? ''),
        'rating'       => (float)($_POST['rating'] ?? 0),
        'ratingCount'  => 0,
        'viewCount'    => (int)($_POST['viewCount'] ?? 0),
        'episodeCount' => 0,
        'year'         => (int)($_POST['year'] ?? date('Y')),
        'genres'       => $genres,
        'cover'        => trim($_POST['coverPath'] ?? '') ?: trim($_POST['cdnCover'] ?? ''),
        'chapters'     => [],
        'latestTs'     => 0,
        'createdAt'    => date('Y-m-d'),
        'updatedAt'    => date('Y-m-d'),
    ];
    $comics[] = $comic;
    logActivity('comic.create', $slug . ' — ' . $title);

} else {
    // Update existing
    $found = false;
    foreach ($comics as &$c) {
        if ($c['slug'] !== $oldSlug) continue;
        $found = true;

        $c['title']        = $title;
        $c['slug']         = $slug;          // slug may have changed
        $c['author']       = trim($_POST['author'] ?? '');
        $c['illustrator']  = trim($_POST['illustrator'] ?? '');
        $c['status']       = $_POST['status'] ?? $c['status'];
        $c['type']         = $_POST['type']   ?? $c['type'];
        $c['description']  = trim($_POST['description'] ?? '');
        $c['rating']       = (float)($_POST['rating'] ?? $c['rating'] ?? 0);
        $c['viewCount']    = (int)($_POST['viewCount'] ?? $c['viewCount'] ?? 0);
        $c['year']         = (int)($_POST['year'] ?? $c['year'] ?? date('Y'));
        $c['genres']       = $genres;
        $c['updatedAt']    = date('Y-m-d');

        // Only overwrite cover if a new one was uploaded
        $newCover = trim($_POST['coverPath'] ?? '');
        if ($newCover) $c['cover'] = $newCover;

        break;
    }
    unset($c);

    if (!$found) jsonError('Komiks topilmadi: ' . $oldSlug, 404);

    // Rename chapter upload directory if slug changed
    if ($slug !== $oldSlug) {
        $oldDir = UPLOADS_DIR . '/' . $oldSlug;
        $newDir = UPLOADS_DIR . '/' . $slug;
        if (is_dir($oldDir) && !is_dir($newDir)) {
            rename($oldDir, $newDir);
        }
        // Rename cover file if local
        foreach (glob(COVERS_DIR . '/' . $oldSlug . '.*') ?: [] as $cf) {
            $ext = pathinfo($cf, PATHINFO_EXTENSION);
            rename($cf, COVERS_DIR . '/' . $slug . '.' . $ext);
        }
    }

    logActivity('comic.edit', $slug . ' — ' . $title);
}

// ── Sort by latestTs desc (newest chapters first) ─────────────
usort($comics, fn($a, $b) => ($b['latestTs'] ?? 0) - ($a['latestTs'] ?? 0));

saveComics($comics);

jsonOk(['slug' => $slug]);
