<?php
$base = preg_replace('|'.basename(dirname(__FILE__)).'$|', '', dirname(__FILE__));

include_once $base.'lib/config.php';
include_once $base.'App.php';

$app = new App();
$app->sendDue();
?> 