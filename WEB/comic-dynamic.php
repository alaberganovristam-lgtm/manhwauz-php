<?php
/**
 * Dynamic comic page — exact structural match with HTTrack Astro pages.
 * Buffered by router.php → patchHtmlStr() for branding + _fix.js.
 */
$slug   = preg_replace('/[^a-z0-9-]/', '', $_GET['slug'] ?? '');
$_cjson = file_get_contents($AURA . '/data/comics.json');
$_cjson = ltrim($_cjson, "\xEF\xBB\xBF"); // strip UTF-8 BOM if present
$comics = json_decode($_cjson, true) ?? [];
$comic  = null;
foreach ($comics as $c) { if ($c['slug'] === $slug) { $comic = $c; break; } }
if (!$comic) { http_response_code(404); exit; }

$chapters = $comic['chapters'] ?? [];
usort($chapters, fn($a,$b) => $b['number'] - $a['number']);
$firstCh  = !empty($chapters) ? end($chapters) : null;
$latestCh = !empty($chapters) ? $chapters[0]   : null;
$chTotal  = count($chapters);

// Nav + footer — birinchi nav_template.html, yo'q bo'lsa HTTrack fallback
$navHtml = $footHtml = '';
$tplFile = is_file($ROOT . '/nav_template.html')
    ? $ROOT . '/nav_template.html'
    : (($tplFiles = glob($ROOT . '/comics/*-????????.html')) ? $tplFiles[0] : '');
if ($tplFile) {
    $t = file_get_contents($tplFile);
    if (preg_match('/<header[^>]*>.*?<\/header>/s', $t, $m)) $navHtml  = $m[0];
    if (preg_match('/<footer[^>]*>.*?<\/footer>/s', $t, $m)) $footHtml = $m[0];
}

function hd(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function chUrl(string $sl, int $n): string { return '/reader/' . rawurlencode($sl) . '/' . $n; }

$title    = $comic['title']  ?? '';
$desc     = $comic['description'] ?? '';
$cover    = $comic['cover']  ?? '';
$status   = $comic['status'] ?? 'Ongoing';
$type     = $comic['type']   ?? 'Manhwa';
$rating   = (float)($comic['rating']      ?? 0);
$bookmarks= (int)  ($comic['ratingCount'] ?? 0);  // use ratingCount as bookmark proxy
$author   = $comic['author']      ?? '';
$illust   = $comic['illustrator'] ?? $comic['artist'] ?? '';
$genres   = $comic['genres']      ?? [];
$altName  = $comic['alternateNames'] ?? '';
$statusLow = strtolower($status);
$typeLow   = strtolower($type);

$firstUrl  = $firstCh  ? chUrl($slug, (int)$firstCh['number'])  : '#';
$latestUrl = $latestCh ? chUrl($slug, (int)$latestCh['number']) : '#';

// Formatted bookmark count (95940 → 95.9K)
function fmtNum(int $n): string {
    if ($n >= 1000000) return round($n/1000000, 1) . 'M';
    if ($n >= 1000)    return round($n/1000, 1) . 'K';
    return (string)$n;
}
?>
<!DOCTYPE html><html lang="en" class="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
<title><?= hd($title) ?> | Asura Scans</title>
<meta name="description" content="<?= hd(substr(strip_tags($desc), 0, 160)) ?>">
<meta name="theme-color" content="#913FE2">
<link rel="preload" as="image" href="https://asurascans.com/images/logo.webp" type="image/webp">
<link rel="stylesheet" href="../_astro/_slug_.CvPJ_jpT.css">
<style>
.user-menu-skeleton .user-menu-login{display:block}.user-menu-skeleton .user-menu-avatar{display:none!important}
html.has-user .user-menu-skeleton .user-menu-login{display:none}
html.has-user .user-menu-skeleton .user-menu-avatar,html.has-user .notif-bell-skeleton{display:flex!important}
body.mobile-menu-open header,body.profile-menu-open header,body.notifications-menu-open header{background-color:#1c1924!important}
.asura-desktop-flex,.asura-desktop-block{display:none}.asura-mobile-button{display:flex}.asura-mobile-layer{display:block}
@media(min-width:901px){.asura-site-header{height:4rem}.asura-header-inner{width:95%;padding-left:0;padding-right:0}.asura-desktop-flex{display:flex}.asura-desktop-block{display:block}.asura-mobile-button,.asura-mobile-layer{display:none!important}}
@media(max-width:900px){body.mobile-menu-open .mobile-menu-hide,body.profile-menu-open .profile-menu-hide,body.notifications-menu-open .notifications-menu-hide{display:none!important}}
#resources-menu.touch-open{visibility:visible!important;opacity:1!important}
#resources-chevron.touch-open{transform:rotate(180deg)}
</style>
<script>if(localStorage.getItem("user")){document.documentElement.classList.add("has-user")}</script>
</head>
<body class="min-h-screen bg-[#13111A] text-white" style="background:#13111A;opacity:0">

<?= $navHtml ?>

<main class="flex-1">
<div class="relative">

 <!-- Background Banner (Desktop only) -->
 <div class="hidden lg:block absolute top-0 left-0 w-full h-[450px] overflow-hidden -mt-19 -z-1">
  <?php if($cover): ?>
  <img src="<?= hd($cover) ?>" alt="<?= hd($title) ?>" class="w-full h-full object-cover blur-[6px] scale-110 pointer-events-none select-none" draggable="false">
  <?php endif; ?>
  <div class="absolute inset-0 bg-black/50"></div>
  <div class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent via-40% to-[#0D0B0E]"></div>
 </div>

 <!-- Mobile Cover Section with full overlay -->
 <div class="lg:hidden relative h-[300px] flex items-center justify-center pt-4">
  <div class="absolute inset-x-0 top-0 h-[500px] -z-10 overflow-hidden">
   <?php if($cover): ?>
   <img src="<?= hd($cover) ?>" alt="" aria-hidden="true" class="w-full h-full object-cover blur-[4px] scale-105 pointer-events-none select-none" draggable="false">
   <?php endif; ?>
   <div class="absolute inset-0 bg-black/50"></div>
   <div class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent via-40% to-[#0D0B0E]"></div>
  </div>
  <div class="relative w-[180px] aspect-[2/3] rounded-lg overflow-hidden shadow-2xl cursor-pointer" style="aspect-ratio:2/3" id="mobile-cover-container">
   <?php if($cover): ?>
   <img src="<?= hd($cover) ?>" alt="<?= hd($title) ?>" class="w-full h-full object-cover" id="mobile-cover-img">
   <?php endif; ?>
  </div>
 </div>

 <!-- Content -->
 <div class="mx-auto w-full md:w-[95%] max-w-[1285px] px-3 lg:px-0 relative z-10 pt-0 lg:pt-8 mb-5 md:mb-7">
  <div class="lg:flex lg:gap-9">

   <!-- ── LEFT COLUMN ── -->
   <div class="lg:max-w-[400px] w-full flex flex-col gap-3 relative">

    <!-- Cover Image (Desktop only) -->
    <div class="hidden lg:block relative aspect-[2/3] rounded-lg overflow-hidden w-full group cursor-pointer" style="aspect-ratio:2/3" id="desktop-cover-container">
     <?php if($cover): ?>
     <img src="<?= hd($cover) ?>" alt="<?= hd($title) ?>" class="w-full h-full object-cover">
     <?php endif; ?>
     <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-[1]"></div>
     <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-[2]">
      <div class="w-[60px] h-[60px] rounded-full bg-white/20 flex items-center justify-center transition-transform duration-200 hover:scale-125">
       <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9m11.25-5.25v4.5m0-4.5h-4.5m4.5 0L15 9m-11.25 11.25v-4.5m0 4.5h4.5m-4.5 0L9 15m11.25 5.25v-4.5m0 4.5h-4.5m4.5 0L15 15"></path></svg>
      </div>
     </div>
    </div>

    <!-- Stats Card (Desktop) -->
    <div class="hidden lg:block">
     <div class="bg-white/5 rounded-lg p-4">
      <!-- Rating / Chapters / Bookmarks -->
      <div class="flex justify-around text-center mb-4">
       <!-- Rating -->
       <div>
        <div class="flex items-center justify-center gap-1.5">
         <svg class="w-5 h-5" viewBox="0 0 13 14" fill="none">
          <defs><linearGradient id="star-gradient" x1="0%" y1="0%" x2="100%" y2="0%"><stop offset="0%" stop-color="#FFDA6E"/><stop offset="100%" stop-color="#FFC414"/></linearGradient></defs>
          <path d="M5.67513 1.20359C6.07233 0.624015 6.92766 0.624014 7.32487 1.20359L8.94401 3.5661C9.07404 3.75583 9.2655 3.89493 9.48612 3.95997L12.2333 4.7698C12.9073 4.96847 13.1716 5.78194 12.7431 6.3388L10.9966 8.60874C10.8563 8.79104 10.7832 9.01612 10.7895 9.24604L10.8683 12.1091C10.8876 12.8114 10.1956 13.3142 9.5336 13.0788L6.83505 12.1191C6.61833 12.0421 6.38167 12.0421 6.16495 12.1191L3.4664 13.0788C2.80439 13.3142 2.11241 12.8114 2.13173 12.1091L2.21047 9.24604C2.21679 9.01612 2.14366 8.79104 2.0034 8.60874L0.256856 6.3388C-0.171606 5.78194 0.0927045 4.96847 0.766654 4.7698L3.51388 3.95997C3.7345 3.89493 3.92596 3.75583 4.05599 3.5661L5.67513 1.20359Z" fill="url(#star-gradient)"/>
         </svg>
         <span class="text-xl font-bold bg-gradient-to-r from-[#FFDA6E] to-[#FFC414] bg-clip-text text-transparent"><?= $rating ? number_format($rating, 1) : '—' ?></span>
        </div>
        <div class="text-xs text-white/60 mt-1">Rating</div>
       </div>
       <div class="w-px bg-white/10"></div>
       <!-- Chapters -->
       <div>
        <div class="flex items-center justify-center gap-1.5">
         <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
          <defs><linearGradient id="chapter-gradient" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" stop-color="#48C855"/><stop offset="100%" stop-color="#C6FFAB"/></linearGradient></defs>
          <path d="M19 2H6c-1.206 0-3 .799-3 3v14c0 2.201 1.794 3 3 3h15v-2H6.012C5.55 19.988 5 19.806 5 19s.55-.988 1.012-1H21V4c0-1.103-.897-2-2-2z" fill="url(#chapter-gradient)"/>
         </svg>
         <span class="text-xl font-bold bg-gradient-to-b from-[#48C855] to-[#C6FFAB] bg-clip-text text-transparent"><?= $chTotal ?></span>
        </div>
        <div class="text-xs text-white/60 mt-1">Chapters</div>
       </div>
       <div class="w-px bg-white/10"></div>
       <!-- Bookmarks -->
       <div>
        <div class="flex items-center justify-center gap-1.5">
         <svg class="w-5 h-5" viewBox="0 0 17 22" fill="none">
          <defs><linearGradient id="bookmark-gradient" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" stop-color="#4857FF"/><stop offset="100%" stop-color="#707CFE"/></linearGradient></defs>
          <path d="M14.9526 0.615234C16.0258 0.615234 16.9039 1.40517 16.9039 2.37065V18.9066C16.9039 20.0301 16.5332 20.8727 15.8892 21.2062C15.2258 21.5573 14.172 21.3818 13.0012 20.7498L10.4254 18.2092C9.16354 16.7691 8.05777 16.7691 6.79589 18.2092L4.2201 20.7498C3.04928 21.3818 1.99555 21.5398 1.33209 21.2062C0.688142 20.8727 0.317383 20.0301 0.317383 18.9066V2.37065C0.317383 1.40517 1.19549 0.615234 2.26874 0.615234H14.9526Z" fill="url(#bookmark-gradient)"/>
         </svg>
         <span class="text-xl font-bold bg-gradient-to-b from-[#4857FF] to-[#707CFE] bg-clip-text text-transparent"><?= $bookmarks ? fmtNum($bookmarks) : '—' ?></span>
        </div>
        <div class="text-xs text-white/60 mt-1">Bookmarks</div>
       </div>
      </div>
      <!-- Status + Type -->
      <div class="flex gap-3 pt-4 border-t border-white/10">
       <div class="flex-1 bg-[#1C1924] rounded px-4 py-3">
        <div class="text-xs text-white/50 mb-1">Status</div>
        <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-[#A78BFA]"></span><span class="text-base font-bold text-[#A78BFA] capitalize"><?= hd($statusLow) ?></span></div>
       </div>
       <div class="flex-1 bg-[#1C1924] rounded px-4 py-3">
        <div class="text-xs text-white/50 mb-1">Type</div>
        <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-[#913FE2]"></span><span class="text-base font-bold text-[#913FE2] uppercase"><?= hd($typeLow) ?></span></div>
       </div>
      </div>
      <!-- Author / Artist -->
      <div class="flex flex-col gap-2 pt-4 border-t border-white/10 mt-4">
       <?php if($author): ?>
       <div class="flex items-center justify-between bg-[#1C1924] rounded px-4 py-2.5">
        <div class="flex items-center gap-2">
         <svg class="w-4 h-4 text-white/70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
         <span class="text-xs text-white/50">Author</span>
        </div>
        <span class="text-sm font-medium"><?= hd($author) ?></span>
       </div>
       <?php endif; ?>
       <?php if($illust): ?>
       <div class="flex items-center justify-between bg-[#1C1924] rounded px-4 py-2.5">
        <div class="flex items-center gap-2">
         <svg class="w-4 h-4 text-white/70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
         <span class="text-xs text-white/50">Artist</span>
        </div>
        <span class="text-sm font-medium"><?= hd($illust) ?></span>
       </div>
       <?php endif; ?>
      </div>
     </div>
    </div>

    <!-- Genres (Desktop) -->
    <?php if($genres): ?>
    <div class="hidden lg:flex max-w-full gap-2 flex-wrap">
     <?php foreach($genres as $g): ?>
     <a href="/browse?genres=<?= urlencode(strtolower($g)) ?>" class="inline-flex flex-shrink-0 text-xs font-medium px-3 py-1.5 bg-white/5 rounded-lg border border-white/10 hover:border-[#913FE2] hover:text-[#913FE2] transition-all"><?= hd($g) ?></a>
     <?php endforeach; ?>
    </div>
    <?php endif; ?>

   </div><!-- /left column -->

   <!-- ── RIGHT COLUMN ── -->
   <div class="w-full mt-3 lg:mt-0">

    <!-- Main Info Card -->
    <article class="bg-[#1C1924] rounded-lg px-3 py-4 lg:p-8">

     <h1 class="text-xl lg:text-[32px] font-semibold leading-tight"><?= hd($title) ?></h1>

     <!-- Alternative Titles -->
     <?php if($altName): ?>
     <div id="alt-container" class="flex items-start gap-3 mt-3 text-xs">
      <p id="alt-titles" class="text-white/50 leading-relaxed line-clamp-1"><?= hd($altName) ?></p>
     </div>
     <?php endif; ?>

     <!-- Description -->
     <?php if($desc): ?>
     <div class="mt-3 relative">
      <div id="description-text" class="text-sm lg:text-base font-light text-white/80 leading-relaxed prose prose-invert max-w-full line-clamp-3 lg:cursor-pointer">
       <p><?= nl2br(hd(strip_tags($desc))) ?></p>
      </div>
      <!-- Desktop expand button -->
      <div class="hidden lg:flex justify-end mt-2">
       <button id="expand-description" class="flex items-center gap-1 text-[#913FE2] text-xs font-medium px-2 py-1 rounded hover:bg-white/5 cursor-pointer transition-colors">
        <span id="expand-text">Show more</span>
        <svg id="expand-icon" class="w-3 h-3 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
       </button>
      </div>
      <!-- Mobile expand button -->
      <div class="flex justify-end mt-2 lg:hidden relative z-10">
       <button onclick="var d=this.closest('.relative').querySelector('#description-text');d.classList.toggle('line-clamp-3');this.querySelector('span').textContent=d.classList.contains('line-clamp-3')?'Show more':'Show less';" class="flex items-center gap-1 text-[#913FE2] text-xs font-medium px-2 py-1 rounded hover:bg-white/5 cursor-pointer transition-colors">
        <span>Show more</span><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
       </button>
      </div>
     </div>
     <?php endif; ?>

     <!-- Mobile Stats -->
     <div class="lg:hidden mt-4 bg-white/5 rounded-lg p-3">
      <div class="flex justify-around text-center">
       <!-- Rating -->
       <div>
        <div class="flex items-center justify-center gap-1">
         <svg class="w-4 h-4" viewBox="0 0 13 14" fill="none">
          <defs><linearGradient id="star-gradient-m" x1="0%" y1="0%" x2="100%" y2="0%"><stop offset="0%" stop-color="#FFDA6E"/><stop offset="100%" stop-color="#FFC414"/></linearGradient></defs>
          <path d="M5.67513 1.20359C6.07233 0.624015 6.92766 0.624014 7.32487 1.20359L8.94401 3.5661C9.07404 3.75583 9.2655 3.89493 9.48612 3.95997L12.2333 4.7698C12.9073 4.96847 13.1716 5.78194 12.7431 6.3388L10.9966 8.60874C10.8563 8.79104 10.7832 9.01612 10.7895 9.24604L10.8683 12.1091C10.8876 12.8114 10.1956 13.3142 9.5336 13.0788L6.83505 12.1191C6.61833 12.0421 6.38167 12.0421 6.16495 12.1191L3.4664 13.0788C2.80439 13.3142 2.11241 12.8114 2.13173 12.1091L2.21047 9.24604C2.21679 9.01612 2.14366 8.79104 2.0034 8.60874L0.256856 6.3388C-0.171606 5.78194 0.0927045 4.96847 0.766654 4.7698L3.51388 3.95997C3.7345 3.89493 3.92596 3.75583 4.05599 3.5661L5.67513 1.20359Z" fill="url(#star-gradient-m)"/>
         </svg>
         <span class="font-bold bg-gradient-to-r from-[#FFDA6E] to-[#FFC414] bg-clip-text text-transparent"><?= $rating ? number_format($rating,1) : '—' ?></span>
        </div>
        <div class="text-[10px] text-white/60 mt-0.5">Rating</div>
       </div>
       <div class="w-px bg-white/10"></div>
       <!-- Chapters -->
       <div>
        <div class="flex items-center justify-center gap-1">
         <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
          <defs><linearGradient id="chapter-gradient-m" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" stop-color="#48C855"/><stop offset="100%" stop-color="#C6FFAB"/></linearGradient></defs>
          <path d="M19 2H6c-1.206 0-3 .799-3 3v14c0 2.201 1.794 3 3 3h15v-2H6.012C5.55 19.988 5 19.806 5 19s.55-.988 1.012-1H21V4c0-1.103-.897-2-2-2z" fill="url(#chapter-gradient-m)"/>
         </svg>
         <span class="font-bold bg-gradient-to-b from-[#48C855] to-[#C6FFAB] bg-clip-text text-transparent"><?= $chTotal ?></span>
        </div>
        <div class="text-[10px] text-white/60 mt-0.5">Chapters</div>
       </div>
       <div class="w-px bg-white/10"></div>
       <!-- Bookmarks -->
       <div>
        <div class="flex items-center justify-center gap-1">
         <svg class="w-4 h-4" viewBox="0 0 17 22" fill="none">
          <defs><linearGradient id="bookmark-gradient-m" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" stop-color="#4857FF"/><stop offset="100%" stop-color="#707CFE"/></linearGradient></defs>
          <path d="M14.9526 0.615234C16.0258 0.615234 16.9039 1.40517 16.9039 2.37065V18.9066C16.9039 20.0301 16.5332 20.8727 15.8892 21.2062C15.2258 21.5573 14.172 21.3818 13.0012 20.7498L10.4254 18.2092C9.16354 16.7691 8.05777 16.7691 6.79589 18.2092L4.2201 20.7498C3.04928 21.3818 1.99555 21.5398 1.33209 21.2062C0.688142 20.8727 0.317383 20.0301 0.317383 18.9066V2.37065C0.317383 1.40517 1.19549 0.615234 2.26874 0.615234H14.9526Z" fill="url(#bookmark-gradient-m)"/>
         </svg>
         <span class="font-bold bg-gradient-to-b from-[#4857FF] to-[#707CFE] bg-clip-text text-transparent"><?= $bookmarks ? fmtNum($bookmarks) : '—' ?></span>
        </div>
        <div class="text-[10px] text-white/60 mt-0.5">Bookmarks</div>
       </div>
      </div>
     </div>

    </article><!-- /article -->

    <!-- Action Buttons (Mobile) -->
    <div class="mt-3 lg:hidden">
     <div class="mb-2">
      <button type="button" class="flex h-full w-full items-center justify-center gap-2 rounded-md px-4 py-3 text-sm font-medium transition-all duration-200 bg-gradient-to-r from-[#913FE2] to-[#7c35c2] text-white shadow-lg shadow-[#913FE2]/20 hover:shadow-[#913FE2]/40 hover:scale-[1.02] cursor-pointer">
       <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
       <span>Bookmark</span>
      </button>
     </div>
     <div class="grid grid-cols-2 gap-2 text-sm font-semibold">
      <?php if($firstCh): ?>
      <a href="<?= hd($firstUrl) ?>" class="py-3 rounded-md bg-[#E8E8E8] hover:bg-[#D4D4D4] text-black flex items-center justify-center gap-2 shadow-[0px_4px_16px_0px_rgba(145,63,226,0.15)] transition-colors">
       <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
       <span>First Chapter</span>
      </a>
      <?php endif; ?>
      <?php if($latestCh): ?>
      <a href="<?= hd($latestUrl) ?>" class="py-3 rounded-md bg-[#2b2931] hover:bg-[#3a3842] text-white border border-white/20 flex items-center justify-center gap-2 transition-colors">
       <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
       <span>Latest Chapter</span>
      </a>
      <?php endif; ?>
     </div>
    </div>

    <!-- Action Buttons (Desktop) -->
    <div class="hidden lg:grid grid-cols-3 gap-3 mt-4 h-12 font-semibold">
     <button type="button" class="flex h-full w-full items-center justify-center gap-2 rounded-md px-4 py-3 text-sm font-medium transition-all duration-200 bg-gradient-to-r from-[#913FE2] to-[#7c35c2] text-white shadow-lg shadow-[#913FE2]/20 hover:shadow-[#913FE2]/40 hover:scale-[1.02] cursor-pointer">
      <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
      <span>Bookmark</span>
     </button>
     <?php if($firstCh): ?>
     <a href="<?= hd($firstUrl) ?>" class="h-full w-full rounded-md bg-[#E8E8E8] hover:bg-[#D4D4D4] text-black flex items-center justify-center gap-2 shadow-[0px_4px_16px_0px_rgba(145,63,226,0.15)] transition-colors">
      <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
      <span>First Chapter</span>
     </a>
     <?php endif; ?>
     <?php if($latestCh): ?>
     <a href="<?= hd($latestUrl) ?>" class="h-full w-full rounded-md bg-[#2b2931] hover:bg-[#3a3842] text-white border border-white/20 flex items-center justify-center gap-2 transition-colors">
      <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
      <span>Latest Chapter</span>
     </a>
     <?php endif; ?>
    </div>

    <!-- Chapter List -->
    <div class="mt-4">
     <div class="bg-[#1C1924] rounded-lg overflow-hidden">
      <!-- Header -->
      <div class="p-4 border-b border-white/10">
       <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-bold"><?= $chTotal ?> Chapters</h2>
       </div>
       <!-- Search -->
       <div class="relative">
        <input type="text" id="ch-search" placeholder="Search chapters..." class="w-full bg-[#13111A] text-white placeholder-white/40 rounded-lg px-4 py-2.5 pr-10 outline-none focus:ring-2 focus:ring-[#913FE2]/50 text-sm ring-1 ring-white/20">
        <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-white/40" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
       </div>
      </div>
      <!-- Chapter rows -->
      <div class="max-h-[500px] overflow-y-auto" id="ch-list">
       <?php if(empty($chapters)): ?>
       <div class="px-6 py-10 text-center text-white/50">No chapters available yet.</div>
       <?php else: ?>
       <div class="divide-y divide-white/5" id="ch-rows">
        <?php foreach($chapters as $ch): ?>
        <a href="<?= hd(chUrl($slug, (int)$ch['number'])) ?>" class="group flex items-center justify-between px-4 py-4 transition-colors hover:bg-white/5" data-ch="<?= (int)$ch['number'] ?>">
         <div class="flex items-center gap-3 min-w-0 flex-1">
          <div class="min-w-0 flex-1">
           <div class="flex items-center gap-2">
            <span class="font-medium transition-colors text-white group-hover:text-[#913FE2]">Chapter <?= (int)$ch['number'] ?></span>
           </div>
           <?php if(!empty($ch['title'])): ?>
           <span class="block truncate text-white/50 text-sm mt-0.5"><?= hd($ch['title']) ?></span>
           <?php endif; ?>
          </div>
         </div>
         <div class="flex-shrink-0 ml-3 text-right">
          <span class="text-sm text-white/40"><?= hd($ch['date'] ?? '') ?></span>
         </div>
        </a>
        <?php endforeach; ?>
       </div>
       <?php endif; ?>
      </div>
     </div>
    </div><!-- /chapter list -->

    <!-- Reactions & Comments -->
    <div class="mt-4 rounded-xl">
     <section id="mu-reactions" class="bg-zinc-800/40 px-4 sm:px-6 py-6 rounded-t-xl" data-slug="<?= hd($slug) ?>">
      <div class="text-center mb-4">
       <h2 class="text-sm sm:text-base font-medium text-zinc-300">Bu seriyani qanday baholaysiz?</h2>
       <p id="mu-reactions-total" class="text-sm font-semibold text-zinc-400 mt-1">0 ta reaksiya</p>
      </div>
      <div class="grid grid-cols-3 gap-2 sm:flex sm:justify-center sm:gap-8">
       <?php foreach([['👍','Yoqdi'],['😂','Kulgili'],['❤️','Sevaman'],['😮','Ajablanish'],['😠','G\'azab'],['😢','Qayg\'u']] as [$emoji,$label]): ?>
       <button type="button" data-reaction="<?= hd($label) ?>" class="mu-reaction-btn flex flex-col items-center p-2 sm:p-3 transition-all duration-200 hover:scale-105 cursor-pointer rounded-lg">
        <div class="relative mb-2"><span class="text-3xl sm:text-4xl filter drop-shadow-md"><?= $emoji ?></span></div>
        <span class="mu-reaction-count text-base font-medium mb-0.5 text-zinc-200">0</span>
        <span class="text-xs capitalize text-zinc-400"><?= $label ?></span>
       </button>
       <?php endforeach; ?>
      </div>
     </section>
     <!-- Comments -->
     <div id="mu-comments-anchor" class="bg-zinc-900/50 p-4 md:p-6">
      <div class="flex items-center justify-between mb-4">
       <span class="text-sm font-medium text-zinc-400">0 Comments</span>
       <div class="flex items-center bg-zinc-800 rounded-lg p-1 text-xs">
        <button class="px-3.5 py-1.5 rounded-md capitalize transition-colors font-semibold leading-none flex items-center justify-center cursor-pointer bg-[#913FE2] text-white">Best</button>
        <button class="px-3.5 py-1.5 rounded-md capitalize transition-colors font-semibold leading-none flex items-center justify-center cursor-pointer text-zinc-400 hover:text-zinc-200">Newest</button>
        <button class="px-3.5 py-1.5 rounded-md capitalize transition-colors font-semibold leading-none flex items-center justify-center cursor-pointer text-zinc-400 hover:text-zinc-200">Oldest</button>
       </div>
      </div>
      <!-- Comment box -->
      <div class="border border-zinc-700 rounded-lg overflow-hidden">
       <div class="px-4 py-3 min-h-[80px] text-zinc-500 text-sm bg-zinc-800/30">Sign up to join the discussion...</div>
       <div class="flex items-center justify-between px-3 py-2 border-t border-zinc-700 bg-zinc-800/50">
        <div class="flex items-center gap-3 text-zinc-500">
         <button class="hover:text-zinc-300 transition-colors font-bold text-sm cursor-pointer">B</button>
         <button class="hover:text-zinc-300 transition-colors italic text-sm cursor-pointer">I</button>
         <button class="hover:text-zinc-300 transition-colors text-sm cursor-pointer line-through">S</button>
         <button class="hover:text-zinc-300 transition-colors text-sm cursor-pointer">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
         </button>
        </div>
        <a href="/login" class="flex items-center gap-2 rounded-lg bg-[#913FE2] hover:bg-[#7c35c2] text-white text-sm font-semibold px-4 py-2 transition-colors cursor-pointer">
         <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
         Sign Up to Comment
        </a>
       </div>
      </div>
     </div>
    </div><!-- /reactions+comments -->

   </div><!-- /right column -->
  </div><!-- /flex -->

  <!-- Recommended Series -->
  <?php
  // Pick up to 6 random comics (not current)
  $allComics = array_values(array_filter($comics, fn($c) => $c['slug'] !== $slug && !empty($c['cover'])));
  shuffle($allComics);
  $recommended = array_slice($allComics, 0, 6);
  // Also add HTTrack mirror comics if we need more
  if(count($recommended) < 6) {
    $htComics = [];
    foreach(glob($ROOT.'/comics/*-????????.html') as $f) {
      preg_match('#/([a-z0-9-]+)-[a-f0-9]{8}\.html$#', str_replace('\\','/',$f), $mm);
      if(!empty($mm[1]) && $mm[1] !== $slug) {
        $htComics[] = ['slug'=>$mm[1],'title'=>ucwords(str_replace('-',' ',$mm[1])),'cover'=>'','rating'=>0,'type'=>'Manhwa','chapters'=>[],'episodeCount'=>0];
      }
    }
    shuffle($htComics);
    $recommended = array_merge($recommended, array_slice($htComics, 0, 6 - count($recommended)));
  }
  ?>
  <?php if($recommended): ?>
  <div class="mt-6 bg-[#1C1924] rounded-lg p-4 lg:p-6">
   <h2 class="text-xl font-bold text-white mb-4">Recommended Series</h2>
   <div class="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
    <?php foreach($recommended as $rc):
      $rcSlug  = $rc['slug'];
      $rcTitle = $rc['title'] ?? ucwords(str_replace('-',' ',$rcSlug));
      $rcCover = $rc['cover'] ?? '';
      $rcType  = strtolower($rc['type'] ?? 'manhwa');
      $rcRat   = (float)($rc['rating'] ?? 0);
      $rcEp    = (int)($rc['episodeCount'] ?? count($rc['chapters'] ?? []));
      $starPct = $rcRat > 0 ? min(100, ($rcRat / 10) * 100) : 0;
    ?>
    <a href="/comics/<?= hd($rcSlug) ?>" class="block group">
     <div class="relative aspect-[2/3] overflow-hidden rounded-md" style="aspect-ratio:2/3">
      <?php if($rcCover): ?>
      <img src="<?= hd($rcCover) ?>" alt="<?= hd($rcTitle) ?>" class="w-full h-full object-cover object-top group-hover:opacity-60 transition-opacity" loading="lazy">
      <?php else: ?>
      <div class="w-full h-full bg-[#2b2931] flex items-center justify-center text-white/30 text-xs text-center px-2"><?= hd($rcTitle) ?></div>
      <?php endif; ?>
      <div class="absolute bottom-0 left-1.5 mb-1.5 rounded-[3px] bg-[#913FE2] flex items-center">
       <span class="text-[9px] font-bold px-[6px] py-[2px] uppercase"><?= hd($rcType) ?></span>
      </div>
     </div>
     <div class="mt-2">
      <span class="block text-[13px] font-bold text-white truncate group-hover:text-[#913FE2] transition-colors"><?= hd($rcTitle) ?></span>
      <?php if($rcEp > 0): ?>
      <span class="block text-sm text-[#999] font-medium">Chapter <?= $rcEp ?></span>
      <?php endif; ?>
      <?php if($rcRat > 0): ?>
      <div class="flex items-center gap-1.5 mt-1">
       <div class="relative flex">
        <div class="flex gap-0.5">
         <?php for($s=0;$s<5;$s++): ?><svg class="w-3.5 h-3.5 text-[#4a4a4a]" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg><?php endfor; ?>
        </div>
        <div class="absolute top-0 left-0 flex gap-0.5 overflow-hidden" style="width:<?= $starPct ?>%">
         <?php for($s=0;$s<5;$s++): ?><svg class="w-3.5 h-3.5 text-[#ffc700] flex-shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg><?php endfor; ?>
        </div>
       </div>
       <span class="text-xs font-bold text-[#999]"><?= number_format($rcRat,1) ?></span>
      </div>
      <?php endif; ?>
     </div>
    </a>
    <?php endforeach; ?>
   </div>
  </div>
  <?php endif; ?>

 </div><!-- /content -->
</div><!-- /relative -->
</main>

<?= $footHtml ?>

<script type="module">
// Description expand/collapse
const btn=document.getElementById('expand-description'),
      txt=document.getElementById('description-text'),
      lbl=document.getElementById('expand-text'),
      ico=document.getElementById('expand-icon');
let exp=false;
if(btn&&txt){btn.addEventListener('click',()=>{exp=!exp;txt.classList.toggle('line-clamp-3',!exp);if(lbl)lbl.textContent=exp?'Show less':'Show more';if(ico)ico.style.transform=exp?'rotate(180deg)':'';})}

// Chapter search
const si=document.getElementById('ch-search'),
      rows=document.querySelectorAll('#ch-rows a');
if(si&&rows.length){
  si.addEventListener('input',()=>{
    const q=si.value.toLowerCase().trim();
    rows.forEach(r=>{
      const n='chapter '+r.dataset.ch;
      r.style.display=(!q||n.includes(q))?'':'none';
    });
  });
}
</script>
</body></html>
