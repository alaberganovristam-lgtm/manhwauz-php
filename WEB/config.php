<?php
/**
 * ╔══════════════════════════════════════════════════════════╗
 *  Manhwa UZ — Deploy Konfiguratsiyasi
 *  Deploy qilishdan oldin BU FAYLNI TAHRIRLANG!
 * ╚══════════════════════════════════════════════════════════╝
 *
 * Standart struktura (manhwauz.com/):
 *   WEB/      ← asosiy sayt (siz hozir shu yerdasiz)
 *   WEBAD/    ← admin panel
 *   DATA/     ← ma'lumotlar (comics.json, kover rasmlar, uploads)
 *
 * Hosting'da: WEB → public_html (yoki webroot)
 *             DATA → public_html dan TASHQARIDA (xavfsizlik uchun)
 */

// ── 1. Ma'lumotlar papkasi yo'li ───────────────────────────────────
// Standart: WEB bilan bir darajada DATA papkasi
// Hosting'da boshqacha bo'lsa, to'g'irlang:
//   define('AURA_PATH', '/home/USER/manhwauz_data');
if (!defined('AURA_PATH')) {
    $envPath = getenv('AURA_PATH') ?: ($_SERVER['AURA_PATH'] ?? '');
    if ($envPath && is_dir($envPath)) {
        define('AURA_PATH', rtrim($envPath, '/\\'));
    } elseif (is_dir(dirname(__DIR__) . '/DATA')) {
        define('AURA_PATH', dirname(__DIR__) . '/DATA');
    } elseif (is_dir(__DIR__ . '/data')) {
        define('AURA_PATH', __DIR__);
    } else {
        @mkdir(__DIR__ . '/data', 0755, true);
        define('AURA_PATH', __DIR__);
    }
}

// ── 2. Sayt URL ──────────────────────────────────────────────────
define('SITE_URL', getenv('SITE_URL') ?: 'https://manhwauz.com');
define('ADMIN_URL', getenv('ADMIN_URL') ?: 'https://admin.manhwauz.com');

// ── 3. Debug rejimi ──────────────────────────────────────────────
define('DEBUG_MODE', (getenv('DEBUG_MODE') === '1'));

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// ── 4. Timezone ──────────────────────────────────────────────────
date_default_timezone_set(getenv('TZ') ?: 'Asia/Tashkent');
