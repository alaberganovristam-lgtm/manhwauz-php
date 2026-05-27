<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireAuth();

$comics     = loadComics();
$filterSlug = preg_replace('/[^a-z0-9-]/', '', $_GET['slug'] ?? '');
$page       = max(1, (int)($_GET['p'] ?? 1));
$perPage    = 30;

// Build flat chapter list
$rows = [];
foreach ($comics as $c) {
    if ($filterSlug && $c['slug'] !== $filterSlug) continue;
    foreach ($c['chapters'] ?? [] as $ch) {
        $chDir  = UPLOADS_DIR . '/' . $c['slug'] . '/chapter-' . $ch['number'];
        $nFiles = is_dir($chDir)
            ? count(array_filter(scandir($chDir), fn($f) => $f[0] !== '.'))
            : 0;
        $rows[] = [
            'title'   => $c['title'],
            'slug'    => $c['slug'],
            'cover'   => $c['cover'] ?? '',
            'chapter' => $ch['number'],
            'date'    => $ch['date'] ?? '—',
            'ts'      => $ch['timestamp'] ?? 0,
            'files'   => $nFiles,
        ];
    }
}
usort($rows, fn($a,$b) => $b['ts'] - $a['ts']);

$total    = count($rows);
$pages    = max(1, (int)ceil($total / $perPage));
$page     = min($page, $pages);
$display  = array_slice($rows, ($page - 1) * $perPage, $perPage);

$pageTitle  = 'Boblar';
$activePage = 'chapters';
ob_start();
?>

<div style="display:flex;gap:.75rem;align-items:center;margin-bottom:1.25rem;flex-wrap:wrap">
  <form method="GET" style="display:flex;gap:.5rem;flex:1;max-width:360px">
    <select class="inp" name="slug" onchange="this.form.submit()">
      <option value="">— Barcha komikslar —</option>
      <?php foreach($comics as $c): ?>
      <option value="<?= htmlspecialchars($c['slug']) ?>"
              <?= $c['slug']===$filterSlug?'selected':'' ?>>
        <?= htmlspecialchars($c['title']) ?>
      </option>
      <?php endforeach; ?>
    </select>
  </form>
  <a href="/chapters/upload<?= $filterSlug ? '?slug='.urlencode($filterSlug) : '' ?>" class="btn btn-primary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
    &nbsp;Bob yuklash
  </a>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title">Boblar ro'yxati</span>
    <span class="badge badge-purple"><?= $total ?> ta</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Komiks</th>
          <th style="width:80px;text-align:center">Bob #</th>
          <th style="width:90px;text-align:center">Fayllar</th>
          <th style="width:110px">Sana</th>
          <th style="width:100px">Amallar</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($display as $row): ?>
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:.625rem">
            <?php $cUrl = coverUrl($row['cover']); if($cUrl): ?>
            <img src="<?= htmlspecialchars($cUrl) ?>"
                 style="width:32px;height:44px;object-fit:cover;border-radius:4px;flex-shrink:0;background:#252230"
                 loading="lazy" onerror="this.style.display='none'">
            <?php endif; ?>
            <div>
              <a href="/comics/edit?slug=<?= urlencode($row['slug']) ?>"
                 class="font-semibold truncate" style="max-width:200px;display:block;color:var(--purple-l);text-decoration:none"
                 title="<?= htmlspecialchars($row['title']) ?>"><?= htmlspecialchars($row['title']) ?></a>
              <div class="text-xs text-muted"><?= htmlspecialchars($row['slug']) ?></div>
            </div>
          </div>
        </td>
        <td style="text-align:center">
          <span class="badge badge-purple">Bob <?= intval($row['chapter']) ?></span>
        </td>
        <td style="text-align:center">
          <?php if($row['files'] > 0): ?>
          <span class="badge badge-gray"><?= $row['files'] ?> fayl</span>
          <?php else: ?>
          <span class="text-xs text-muted">—</span>
          <?php endif; ?>
        </td>
        <td class="text-sm text-muted"><?= htmlspecialchars($row['date']) ?></td>
        <td>
          <div class="td-actions">
            <a href="/chapters/upload?slug=<?= urlencode($row['slug']) ?>&chapter=<?= intval($row['chapter']) ?>"
               class="btn btn-sm btn-ghost" title="Qayta yuklash">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            </a>
            <form method="POST" action="/api/chapter-delete"
                  onsubmit="return confirmDelete('Bob <?= intval($row['chapter']) ?>ni o\'chirasizmi?', this)">
              <input type="hidden" name="csrf"    value="<?= csrfToken() ?>">
              <input type="hidden" name="slug"    value="<?= htmlspecialchars($row['slug']) ?>">
              <input type="hidden" name="chapter" value="<?= intval($row['chapter']) ?>">
              <button type="submit" class="btn btn-sm btn-danger" title="O'chirish">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($display)): ?>
      <tr><td colspan="5" style="text-align:center;padding:3rem;color:var(--text3)">Hali bob yo'q</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if($pages > 1): ?>
  <div style="display:flex;justify-content:center;gap:.375rem;padding:1rem;border-top:1px solid var(--border)">
    <?php
    $base = '?slug=' . urlencode($filterSlug) . '&p=';
    for ($i = 1; $i <= $pages; $i++):
    ?>
    <a href="<?= $base.$i ?>" class="btn btn-sm <?= $i===$page?'btn-primary':'btn-ghost' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require ROOT . '/includes/layout.php';
