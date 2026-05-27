<?php
/**
 * ranking.php — Seriyalar reytingi sahifasi
 * /ranking?by=rating|views|chapters|updated
 */
$AURA   = defined('AURA_PATH') ? AURA_PATH : dirname(__DIR__) . '/asuramanga2';
$ROOT   = __DIR__;

$_rjson = file_get_contents($AURA . '/data/comics.json');
$_rjson = ltrim($_rjson, "\xEF\xBB\xBF");
$comics = json_decode($_rjson, true) ?? [];
$by     = $_GET['by'] ?? 'rating';
if (!in_array($by, ['rating', 'views', 'chapters', 'updated'])) $by = 'rating';

usort($comics, function ($a, $b) use ($by) {
    return match ($by) {
        'rating'   => (float)($b['rating'] ?? 0) <=> (float)($a['rating'] ?? 0),
        'views'    => (int)($b['viewCount'] ?? 0) <=> (int)($a['viewCount'] ?? 0),
        'chapters' => count($b['chapters'] ?? []) <=> count($a['chapters'] ?? []),
        'updated'  => strcmp($b['lastUpdated'] ?? '', $a['lastUpdated'] ?? ''),
        default    => 0,
    };
});

$tabs = [
    'rating'   => 'Reyting',
    'views'    => "Ko'rishlar",
    'chapters' => 'Boblar soni',
    'updated'  => 'Yangilangan',
];

function rk_cover(string $cover): string {
    if (!$cover) return '/public/images/placeholder.webp';
    return htmlspecialchars($cover, ENT_QUOTES);
}
function rk_n(int|float $n): string {
    if ($n >= 1000000) return round($n / 1000000, 1) . 'M';
    if ($n >= 1000)    return round($n / 1000, 1) . 'K';
    return (string)$n;
}

// Nav + footer + nav styles
$navHtml = $footHtml = $navStyles = '';
$tplFile = is_file($ROOT . '/nav_template.html')
    ? $ROOT . '/nav_template.html'
    : (($tplFiles = glob($ROOT . '/comics/*-????????.html')) ? $tplFiles[0] : '');
if ($tplFile) {
    $t = file_get_contents($tplFile);
    if (preg_match('/<header[^>]*>.*?<\/header>/s', $t, $m)) $navHtml  = $m[0];
    if (preg_match('/<footer[^>]*>.*?<\/footer>/s', $t, $m)) $footHtml = $m[0];
    // Extract all <style> blocks from template for nav CSS
    if (preg_match_all('/<style[^>]*>(.*?)<\/style>/s', $t, $sm)) {
        $navStyles = '<style>' . implode("\n", $sm[1]) . '</style>';
    }
}
?>
<!DOCTYPE html>
<html lang="uz" class="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Seriyalar Reytingi — Manhwa UZ</title>
<meta name="description" content="Manhwa UZ dagi eng yaxshi manhwa va manga seriyalari reytingi">
<link rel="stylesheet" href="/_astro/_slug_.CvPJ_jpT.css">
<link rel="manifest" href="/manifest.json">
<link rel="icon" href="/favicon.ico">
<?= $navStyles ?>
<style>
  body { background:#13111A; color:#fff; font-family:inherit; margin:0 }
  .rk-wrap  { max-width:900px; margin:0 auto; padding:2rem 1rem 4rem }
  .rk-title { font-size:1.75rem; font-weight:700; margin-bottom:1.5rem }
  .rk-tabs  { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1.75rem }
  .rk-tab   {
    padding:.45rem 1.1rem; border-radius:999px; font-size:.85rem; font-weight:600;
    text-decoration:none; border:1.5px solid rgba(255,255,255,.15); color:rgba(255,255,255,.6);
    transition:all .15s;
  }
  .rk-tab:hover { border-color:#913FE2; color:#fff }
  .rk-tab.active { background:#913FE2; border-color:#913FE2; color:#fff }
  .rk-list  { display:flex; flex-direction:column; gap:.75rem }
  .rk-row   {
    display:flex; align-items:center; gap:1rem;
    background:#1C1924; border-radius:.875rem; padding:.75rem 1rem;
    text-decoration:none; color:inherit; transition:background .15s;
  }
  .rk-row:hover { background:#25222f }
  .rk-rank  { font-size:1.25rem; font-weight:800; width:2.25rem; text-align:center; flex-shrink:0; color:rgba(255,255,255,.35) }
  .rk-rank.top3 { color:#913FE2; font-size:1.5rem }
  .rk-cover { width:3.25rem; height:4.25rem; object-fit:cover; border-radius:.5rem; flex-shrink:0; background:#1D1B22 }
  .rk-info  { flex:1; min-width:0 }
  .rk-name  { font-weight:600; font-size:.9375rem; overflow:hidden; white-space:nowrap; text-overflow:ellipsis }
  .rk-meta  { font-size:.78rem; color:rgba(255,255,255,.45); margin-top:.2rem }
  .rk-stat  { text-align:right; flex-shrink:0 }
  .rk-val   { font-size:1.1rem; font-weight:700; color:#913FE2 }
  .rk-lbl   { font-size:.7rem; color:rgba(255,255,255,.4) }
  .medal-1  { color:#FFD700 }
  .medal-2  { color:#C0C0C0 }
  .medal-3  { color:#CD7F32 }
</style>
</head>
<body>
<?= $navHtml ?>

<main class="rk-wrap">
  <h1 class="rk-title">Seriyalar Reytingi</h1>

  <div class="rk-tabs">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="/ranking?by=<?= $key ?>"
       class="rk-tab<?= $by === $key ? ' active' : '' ?>">
      <?= htmlspecialchars($label) ?>
    </a>
    <?php endforeach ?>
  </div>

  <div class="rk-list">
    <?php foreach ($comics as $i => $c):
      $rank = $i + 1;
      $rankClass = $rank <= 3 ? ' top3' : '';
      $medalClass = match($rank) { 1 => ' medal-1', 2 => ' medal-2', 3 => ' medal-3', default => '' };
      $slug  = htmlspecialchars($c['slug'] ?? '', ENT_QUOTES);
      $title = htmlspecialchars($c['title'] ?? 'Nomsiz', ENT_QUOTES);
      $chCnt = count($c['chapters'] ?? []);
      $statuses = ['Ongoing' => 'Davom etmoqda', 'Completed' => 'Tugallangan', 'Hiatus' => 'To\'xtatilgan', 'Dropped' => 'Bekor qilingan'];
      $statusUz = $statuses[$c['status'] ?? ''] ?? ($c['status'] ?? '');

      [$statVal, $statLbl] = match($by) {
        'rating'   => [(string)number_format((float)($c['rating'] ?? 0), 1), 'Reyting'],
        'views'    => [rk_n((int)($c['viewCount'] ?? 0)), "Ko'rishlar"],
        'chapters' => [(string)$chCnt, 'Bob'],
        'updated'  => [substr($c['lastUpdated'] ?? '', 0, 10), 'Sana'],
        default    => ['', ''],
      };
    ?>
    <a href="/comics/<?= $slug ?>" class="rk-row">
      <div class="rk-rank<?= $rankClass . $medalClass ?>"><?= $rank ?></div>
      <img class="rk-cover" src="<?= rk_cover($c['cover'] ?? '') ?>"
           alt="<?= $title ?>" loading="lazy"
           onerror="this.style.background='#1D1B22'">
      <div class="rk-info">
        <div class="rk-name"><?= $title ?></div>
        <div class="rk-meta">
          <?= htmlspecialchars($c['author'] ?? '', ENT_QUOTES) ?>
          <?php if ($c['author'] ?? ''): ?> · <?php endif ?>
          <?= htmlspecialchars($statusUz) ?>
          · <?= $chCnt ?> bob
        </div>
      </div>
      <div class="rk-stat">
        <div class="rk-val"><?= htmlspecialchars((string)$statVal, ENT_QUOTES) ?></div>
        <div class="rk-lbl"><?= htmlspecialchars($statLbl, ENT_QUOTES) ?></div>
      </div>
    </a>
    <?php endforeach ?>
  </div>
</main>

<?= $footHtml ?>
</body>
</html>
