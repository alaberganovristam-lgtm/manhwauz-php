<?php
/**
 * Manhwa UZ Admin Panel — Konfiguratsiya
 *
 * Standart struktura:
 *   WEB/      ← asosiy sayt
 *   WEBAD/    ← admin panel (siz hozir shu yerdasiz)
 *   DATA/     ← ma'lumotlar (comics.json, covers)
 *
 * Admin panel data/ papkasi (admins.json, sessions) — WEBAD ichida saqlanadi.
 * Komiks/uploads ma'lumotlari esa AURA_PATH (DATA papkasi) dan o'qiladi.
 */

if (!defined('AURA_PATH')) {
    $envPath = getenv('AURA_PATH') ?: ($_SERVER['AURA_PATH'] ?? '');
    if ($envPath && is_dir($envPath)) {
        define('AURA_PATH', rtrim($envPath, '/\\'));
    } elseif (is_dir(dirname(__DIR__) . '/DATA')) {
        define('AURA_PATH', dirname(__DIR__) . '/DATA');
    } else {
        // Fallback: admin uses its own data dir
        define('AURA_PATH', __DIR__);
    }
}

define('SITE_URL',  getenv('SITE_URL')  ?: 'https://manhwauz.com');
define('ADMIN_URL', getenv('ADMIN_URL') ?: 'https://admin.manhwauz.com');
define('DEBUG_MODE', (getenv('DEBUG_MODE') === '1'));

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

date_default_timezone_set(getenv('TZ') ?: 'Asia/Tashkent');
