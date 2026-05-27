<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireAuth();

// comics.json dan admin boshqargan comiclar
$jsonComics  = loadComics();
$jsonSlugs   = array_column($jsonComics, 'slug');

// HTTrack static fayllardan sluglar
$staticFiles = glob(SITE_ROOT . '/../asurascans.com/comics/*-????????.html') ?: [];
$staticComics = [];
foreach ($staticFiles as $file) {
    $base = basename($file, '.html');
    if (preg_match('/^(.+)-[a-f0-9]{8}$/', $base, $m)) {
        $staticSlug = $m[1];
        if (!in_array($staticSlug, $jsonSlugs)) {
            // Fayldan sarlavha va cover o'qish
            $html  = file_get_contents($file);
            $title = $staticSlug;
            if (preg_match('/<title>([^<|]+)/', $html, $tm)) {
                $title = trim(preg_replace('/\s*\|\s*Asura Scans.*$/i', '', $tm[1]));
            }
            $cover = '';
            if (preg_match('/<meta property="og:image" content="([^"]+)"/', $html, $cm)) {
                $cover = $cm[1];
            }
            $staticComics[] = [
                '_static' => true,
                'slug'    => $staticSlug,
                'title'   => $title,
                'cover'   => $cover,
                'status'  => 'Ongoing',
                'author'  => '',
                'chapters'=> [],
            ];
        }
    }
}

// Qidirish
$search = strtolower(trim($_GET['q'] ?? ''));
$tab    = $_GET['tab'] ?? 'all'; // all | admin | httrack

$allComics = [];
foreach ($jsonComics as $c)   { $c['_static'] = false; $allComics[] = $c; }
foreach ($staticComics as $c) { $allComics[] = $c; }

if ($search) {
    $allComics = array_filter($allComics, fn($c) =>
        str_contains(strtolower($c['title']), $search) ||
        str_contains(strtolower($c['slug']), $search)
    );
}

$adminList   = array_filter($allComics, fn($c) => !$c['_static']);
$staticList  = array_filter($allComics, fn($c) =>  $c['_static']);
$displayList = $tab === 'admin'   ? $adminList
             : ($tab === 'httrack' ? $staticList : $allComics);

$pageTitle  = 'Komikslar';
$activePage = 'comics';
ob_start();
?>
<div class="flex items-center justify-between mb-4 gap-3" style="flex-wrap:wrap">
  <form method="GET" style="display:flex;gap:.5rem;flex:1;max-width:340px">
    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
    <input class="inp" type="text" name="q" placeholder="Qidirish..." value="<?= htmlspecialchars($search) ?>">
    <button class="btn btn-ghost">Qidirish</button>
  </form>
  <a href="/comics/create" class="btn btn-primary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
    Yangi komiks
  </a>
</div>

<!-- Tabs -->
<div style="display:flex;gap:.5rem;margin-bottom:1rem">
  <a href="?tab=all<?= $search ? '&q='.urlencode($search) : '' ?>"
     class="btn <?= $tab==='all' ? 'btn-primary' : 'btn-ghost' ?>">
     Barchasi (<?= count($allComics) ?>)
  </a>
  <a href="?tab=admin<?= $search ? '&q='.urlencode($search) : '' ?>"
     class="btn <?= $tab==='admin' ? 'btn-primary' : 'btn-ghost' ?>">
     Admin qo'shgan (<?= count($adminList) ?>)
  </a>
  <a href="?tab=httrack<?= $search ? '&q='.urlencode($search) : '' ?>"
     class="btn <?= $tab==='httrack' ? 'btn-primary' : 'btn-ghost' ?>">
     Saytdagi (<?= count($staticList) ?>)
  </a>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title">Komikslar ro'yxati</span>
    <span class="badge badge-purple"><?= count($displayList) ?> ta</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Cover</th>
          <th>Nomi</th>
          <th>Muallif</th>
          <th>Status</th>
          <th>Boblar</th>
          <th>Amallar</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($displayList as $c):
        $isStatic = $c['_static'] ?? false;
        $nCh = count($c['chapters'] ?? []);
        $statusClass = match(strtolower($c['status']??'')) {
          'completed' => 'badge-green',
          'hiatus'    => 'badge-yellow',
          'dropped'   => 'badge-red',
          default     => 'badge-purple',
        };
        $coverUrl = coverUrl($c['cover'] ?? '');
      ?>
      <tr>
        <td class="td-cover">
          <img src="<?= htmlspecialchars($coverUrl) ?>" loading="lazy"
               onerror="this.style.background='#252230'">
        </td>
        <td>
          <div class="font-semibold truncate" style="max-width:200px" title="<?= htmlspecialchars($c['title']) ?>">
            <?= htmlspecialchars($c['title']) ?>
          </div>
          <div style="display:flex;align-items:center;gap:.4rem;margin-top:.2rem">
            <span class="text-xs text-muted"><?= htmlspecialchars($c['slug']) ?></span>
            <?php if($isStatic): ?>
            <span class="badge badge-gray" style="font-size:.65rem">HTTrack</span>
            <?php endif; ?>
          </div>
        </td>
        <td class="text-sm text-muted"><?= htmlspecialchars($c['author'] ?? '—') ?></td>
        <td><span class="badge <?= $statusClass ?>"><?= htmlspecialchars($c['status'] ?? 'Ongoing') ?></span></td>
        <td>
          <?php if(!$isStatic): ?>
          <a href="/chapters/upload?slug=<?= urlencode($c['slug']) ?>" class="badge badge-gray" style="text-decoration:none" title="Bob yuklash">
            <?= $nCh ?> bob
          </a>
          <?php else: ?>
          <span class="text-xs text-muted">—</span>
          <?php endif; ?>
        </td>
        <td>
          <div class="td-actions">
            <a href="/comics/edit?slug=<?= urlencode($c['slug']) ?>" class="btn btn-sm btn-ghost" title="<?= $isStatic ? 'Import & tahrirlash' : 'Tahrirlash' ?>">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </a>
            <?php if(!$isStatic): ?>
            <a href="/chapters/upload?slug=<?= urlencode($c['slug']) ?>" class="btn btn-sm btn-success" title="Bob yuklash">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            </a>
            <form method="POST" action="/comics/delete" onsubmit="return confirmDelete('\"<?= htmlspecialchars(addslashes($c['title'])) ?>\" ni o\'chirasizmi?', this)">
              <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
              <input type="hidden" name="slug" value="<?= htmlspecialchars($c['slug']) ?>">
              <button type="submit" class="btn btn-sm btn-danger" title="O'chirish">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($displayList)): ?>
      <tr><td colspan="6" style="text-align:center;padding:3rem;color:var(--text3)">Hech narsa topilmadi</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$content = ob_get_clean();
require ROOT . '/includes/layout.php';
