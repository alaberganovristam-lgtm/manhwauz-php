<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/auth.php';
doLogout();
header('Location: /login');
exit;
