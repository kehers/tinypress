<?php
include_once 'lib/config.php';
include_once 'App.php';

$app = new App($_SESSION['token']);

$requested = strtolower($_SERVER['REDIRECT_URL']);
$pagex = str_replace('http://', '', $requested);

if ($_SERVER['HTTP_HOST'] == 'localhost') {
    $dir = basename(dirname(__FILE__));
    $pagex = preg_replace("|^.*$dir|i", "", $pagex);
}

# if end with /, redirect back to addy without /

$pagex = preg_replace('/^\//', "", $pagex);
$pagex = preg_replace('/\?.*$/', '', $pagex);
$pages = explode("/", $pagex);

//print_r($_SERVER);

include_once 'routes.php';
	
unset($_SESSION['error']);
unset($_SESSION['page']);
unset($_SESSION['status']);
?>