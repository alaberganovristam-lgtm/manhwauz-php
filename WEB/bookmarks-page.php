<?php
/**
 * bookmarks-page.php — Saqlanganlar sahifasi
 */
$AURA = defined('AURA_PATH') ? AURA_PATH : dirname(__DIR__) . '/asuramanga2';
$ROOT = __DIR__;

// Nav + footer + nav styles
$navHtml = $footHtml = $navStyles = '';
$tplFile = is_file($ROOT . '/nav_template.html')
    ? $ROOT . '/nav_template.html'
    : (($tplFiles = glob($ROOT . '/comics/*-????????.html')) ? $tplFiles[0] : '');
if ($tplFile) {
    $t = file_get_contents($tplFile);
    if (preg_match('/<header[^>]*>.*?<\/header>/s', $t, $m)) $navHtml  = $m[0];
    if (preg_match('/<footer[^>]*>.*?<\/footer>/s', $t, $m)) $footHtml = $m[0];
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
<title>Saqlanganlar — Manhwa UZ</title>
<meta name="description" content="Saqlangan manhwa va manga seriyalaringiz">
<link rel="stylesheet" href="/_astro/_slug_.CvPJ_jpT.css">
<link rel="manifest" href="/manifest.json">
<link rel="icon" href="/favicon.ico">
<?= $navStyles ?>
<style>
  body { background:#13111A; color:#fff; margin:0 }
  .bm-wrap  { max-width:1100px; margin:0 auto; padding:2rem 1rem 4rem }
  .bm-title { font-size:1.75rem; font-weight:700; margin-bottom:1.75rem }
  #bm-loading {
    display:flex; align-items:center; justify-content:center;
    min-height:40vh; color:rgba(255,255,255,.4); gap:.75rem; font-size:.95rem;
  }
  #bm-loading::before {
    content:''; display:block; width:1.5rem; height:1.5rem; border-radius:50%;
    border:2.5px solid rgba(145,63,226,.3); border-top-color:#913FE2;
    animation:bm-spin .7s linear infinite;
  }
  @keyframes bm-spin { to { transform:rotate(360deg) } }
  #bm-login-notice, #bm-empty { display:none; text-align:center; padding:3rem 1rem; color:rgba(255,255,255,.5) }
  #bm-login-notice a { color:#913FE2; text-decoration:none; font-weight:600 }
  #bm-grid { display:none; grid-template-columns:repeat(2,1fr); gap:1rem }
  @media(min-width:540px)  { #bm-grid { grid-template-columns:repeat(3,1fr) } }
  @media(min-width:768px)  { #bm-grid { grid-template-columns:repeat(4,1fr) } }
  @media(min-width:1024px) { #bm-grid { grid-template-columns:repeat(5,1fr) } }
</style>
</head>
<body class="min-h-screen" style="background:#13111A;opacity:1">
<?= $navHtml ?>

<main class="bm-wrap">
  <h1 class="bm-title">Saqlanganlar</h1>

  <div id="bm-loading">Yuklanmoqda</div>

  <div id="bm-login-notice">
    Saqlangan seriyalarni ko'rish uchun
    <a href="/login">kiring</a> yoki
    <a href="/register">ro'yxatdan o'ting</a>.
  </div>

  <div id="bm-empty">
    <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="color:rgba(145,63,226,.5);margin:0 auto 1rem;display:block"><path stroke-linecap="round" stroke-linejoin="round" d="M17 3H7a2 2 0 00-2 2v16l7-3 7 3V5a2 2 0 00-2-2z"/></svg>
    <p style="margin:0 0 .5rem;font-size:1rem;color:rgba(255,255,255,.6)">Hali saqlangan seriyalar yo'q</p>
    <p style="margin:0;font-size:.875rem"><a href="/browse" style="color:#913FE2;text-decoration:none">Browse</a> bo'limiga o'ting va yoqtirgan seriyalaringizni saqlang</p>
  </div>

  <div id="bm-grid"></div>
</main>

<?= $footHtml ?>
</body>
</html>
