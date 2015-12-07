<?php
$base = preg_replace('|'.basename(dirname(__FILE__)).'$|', '', dirname(__FILE__));

include_once $base.'lib/config.php';
include_once $base.'App.php';

$worker= new GearmanWorker();
$worker->addServer();
$worker->addFunction("change_theme", "change_theme");

while ($worker->work());

function change_theme($job) {
    
    $json = $job->workload();
    $data = json_decode($json, true);
    
    $app = new App($data['token']);
    $app->themeWorker($data);
}
?> 