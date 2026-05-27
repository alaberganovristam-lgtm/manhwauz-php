<?php
/**
 * Chapter reader — included by router.php
 * Variables available: $readerSlug, $readerCh, $ROOT, $AURA
 */

$slug  = $readerSlug;
$chNum = $readerCh;

// Load comic data
$dataFile = $AURA . '/data/comics.json';
$comics   = is_file($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];

$comic = null;
foreach ($comics as $c) {
    if ($c['slug'] === $slug) { $comic = $c; break; }
}

// Chapters
$chapters = $comic['chapters'] ?? [];
$chNums   = array_column($chapters, 'number');
$chIdx    = array_search($chNum, $chNums);
$prevCh   = ($chIdx !== false && $chIdx + 1 < count($chapters)) ? $chapters[$chIdx + 1]['number'] : null;
$nextCh   = ($chIdx !== false && $chIdx > 0)                    ? $chapters[$chIdx - 1]['number'] : null;

// Images
$imgDir = $AURA . '/uploads/' . $slug . '/chapter-' . $chNum;
$images = [];
if (is_dir($imgDir)) {
    $files = scandir($imgDir);
    foreach ($files as $f) {
        if (preg_match('/\.(jpg|jpeg|png|webp|gif|avif)$/i', $f)) {
            $images[] = '/uploads/' . $slug . '/chapter-' . $chNum . '/' . rawurlencode($f);
        }
    }
    natsort($images);
    $images = array_values($images);
}

$title    = htmlspecialchars($comic['title'] ?? $slug);
$prevUrl  = $prevCh !== null ? "/reader/{$slug}/{$prevCh}" : null;
$nextUrl  = $nextCh !== null ? "/reader/{$slug}/{$nextCh}" : null;

// Chapter select
$chSelect = '';
foreach ($chapters as $ch) {
    $sel = $ch['number'] === $chNum ? ' selected' : '';
    $chSelect .= '<option value="/reader/' . $slug . '/' . $ch['number'] . '"' . $sel . '>Chapter ' . $ch['number'] . '</option>';
}

$cssFile = file_exists($ROOT . '/_astro/_slug_.CvPJ_jpT.css')
    ? '<link rel="stylesheet" href="/_astro/_slug_.CvPJ_jpT.css">'
    : '';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $title ?> — Chapter <?= $chNum ?></title>
<meta name="theme-color" content="#913FE2">
<?= $cssFile ?>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#13111A;color:#fff;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;min-height:100vh;display:flex;flex-direction:column}
a{color:inherit;text-decoration:none}
.r-nav{background:#1D1B22;border-bottom:1px solid rgba(255,255,255,.08);position:sticky;top:0;z-index:50}
.r-nav-in{max-width:1285px;margin:0 auto;padding:.6rem 1rem;display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap}
.r-bread{display:flex;align-items:center;gap:.4rem;font-size:.875rem;min-width:0;overflow:hidden}
.r-bread a{color:rgba(255,255,255,.5)}
.r-bread a:hover{color:#fff}
.r-bread span{color:rgba(255,255,255,.3)}
.r-ctrls{display:flex;align-items:center;gap:.4rem;flex-shrink:0}
.r-btn{display:inline-flex;align-items:center;gap:.25rem;padding:.4rem .75rem;border-radius:.5rem;font-size:.8rem;font-weight:500;background:rgba(255,255,255,.06);color:rgba(255,255,255,.8);border:1px solid rgba(255,255,255,.1);transition:background .15s,color .15s;cursor:pointer}
.r-btn:hover{background:rgba(255,255,255,.14);color:#fff}
.r-btn.prim{background:#913FE2;color:#fff;border-color:#913FE2}
.r-btn.prim:hover{background:#7c35c2}
select.r-sel{background:#13111A;border:1px solid rgba(255,255,255,.1);border-radius:.5rem;padding:.4rem .6rem;color:#fff;font-size:.8rem;outline:none;cursor:pointer;max-width:170px}
.r-content{flex:1;background:#0D0B0E}
.r-imgs{max-width:800px;margin:0 auto}
.r-imgs img{width:100%;display:block}
.r-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:5rem 1rem;text-align:center;min-height:50vh}
.r-empty h2{font-size:1.25rem;font-weight:600;margin:.75rem 0 .5rem}
.r-empty p{color:rgba(255,255,255,.5);font-size:.875rem;max-width:400px}
.r-empty code{background:#1D1B22;padding:.25rem .75rem;border-radius:.5rem;font-family:monospace;font-size:.75rem;color:rgba(255,255,255,.4);display:inline-block;margin:.75rem 0 2rem}
.r-btns{display:flex;gap:.75rem;flex-wrap:wrap;justify-content:center}
.r-foot{background:#1D1B22;border-top:1px solid rgba(255,255,255,.08)}
.r-foot-in{max-width:1285px;margin:0 auto;padding:1rem;display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap}
svg.ic{width:1rem;height:1rem;flex-shrink:0}
</style>
</head>
<body>

<div class="r-nav">
  <div class="r-nav-in">
    <div class="r-bread">
      <a href="/">&#127968;</a>
      <span>›</span>
      <a href="/comics/<?= $slug ?>"><?= $title ?></a>
      <span>›</span>
      <span>Ch.<?= $chNum ?></span>
    </div>
    <div class="r-ctrls">
      <?php if ($prevUrl): ?>
      <a href="<?= $prevUrl ?>" class="r-btn">
        <svg class="ic" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Prev
      </a>
      <?php endif; ?>
      <select class="r-sel" onchange="location.href=this.value"><?= $chSelect ?></select>
      <?php if ($nextUrl): ?>
      <a href="<?= $nextUrl ?>" class="r-btn prim">
        Next
        <svg class="ic" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
      </a>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="r-content">
  <?php if (empty($images)): ?>
  <div class="r-empty">
    <div style="width:80px;height:80px;border-radius:50%;background:#1D1B22;display:flex;align-items:center;justify-content:center">
      <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="rgba(255,255,255,.3)" stroke-width="1.5">
        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
      </svg>
    </div>
    <h2>Chapter Images Not Available</h2>
    <p>Chapter <?= $chNum ?> rasmlarini qo'shing:</p>
    <code>asuramanga2/uploads/<?= $slug ?>/chapter-<?= $chNum ?>/</code>
    <div class="r-btns">
      <a href="/comics/<?= $slug ?>" class="r-btn">← Comic Page</a>
      <?php if ($nextUrl): ?>
      <a href="<?= $nextUrl ?>" class="r-btn prim">Next Chapter →</a>
      <?php endif; ?>
    </div>
  </div>
  <?php else: ?>
  <div class="r-imgs">
    <?php foreach ($images as $src): ?>
    <img src="<?= htmlspecialchars($src) ?>" alt="<?= $title ?> Ch.<?= $chNum ?>" loading="lazy"
         onerror="this.style.display='none'">
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<div class="r-foot">
  <div class="r-foot-in">
    <a href="/comics/<?= $slug ?>" class="r-btn">
      <svg class="ic" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
      Comic Page
    </a>
    <div style="display:flex;gap:.5rem">
      <?php if ($prevUrl): ?>
      <a href="<?= $prevUrl ?>" class="r-btn">← Previous</a>
      <?php endif; ?>
      <?php if ($nextUrl): ?>
      <a href="<?= $nextUrl ?>" class="r-btn prim">Next →</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
document.addEventListener('keydown', function(e) {
  <?php if ($prevUrl): ?>if (e.key === 'ArrowLeft')  location.href = '<?= $prevUrl ?>';<?php endif; ?>
  <?php if ($nextUrl): ?>if (e.key === 'ArrowRight') location.href = '<?= $nextUrl ?>';<?php endif; ?>
});
window.addEventListener('load', function() { window.scrollTo(0, 0); });
</script>
</body>
</html>
