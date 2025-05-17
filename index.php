<?php
date_default_timezone_set('America/Sao_Paulo');
$version = '1.0';

$isAxios = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if ($isAxios || strpos($contentType, 'application/json') !== false) {
    require_once './routes/routeApi.php';
} else {
    require_once './routes/route.php';
}
