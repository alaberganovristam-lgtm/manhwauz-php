<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /logs'); exit; }
verifyCsrf();

file_put_contents(DATA_DIR . '/activity.log', '');
logActivity('log.clear', 'Faollik jurnali tozalandi');
$_SESSION['flash'] = ['type' => 'success', 'msg' => 'Faollik jurnali tozalandi'];
header('Location: /logs');
exit;
