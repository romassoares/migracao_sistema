<?php
date_default_timezone_set('America/Sao_Paulo');


require_once './routes/route.php';

$route = new Route();
$route->index();
