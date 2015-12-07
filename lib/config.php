<?php
session_start();

if ($_SERVER['HTTP_HOST'] == 'localhost') {
    // Github keys
    define('CLIENT_ID', '1a4f958eea6766af49db');
    define('CLIENT_SECRET', '');
    
    // Stripe
    define("STRIPE_PK", "pk_test_ohn4vNKQhGnRtyis050BcABp");
    define("STRIPE_SK", "");
}
else if ($_SERVER['HTTP_HOST'] == 'beta.tinypress.co') {
    define('CLIENT_ID', 'b11b9baf2a705b4b8467');
    define('CLIENT_SECRET', '');
    
    // Stripe
    define("STRIPE_PK", "pk_live_wI5uOcb45JtquWeAyYRGFI7m");
    define("STRIPE_SK", "");
}
else{
    // Github keys
    define('CLIENT_ID', 'cfb8f314063e9b20f40e');
    define('CLIENT_SECRET', '');
    
    // Stripe
    define("STRIPE_PK", "pk_live_wI5uOcb45JtquWeAyYRGFI7m");
    define("STRIPE_SK", "");
}
define('AUTH_URL', 'https://github.com/login/oauth/authorize');
define('ACCESS_TOKEN_URL', 'https://github.com/login/oauth/access_token');

define("DBNAME", "tinypress");
define("DBPASS", "");

$mysqli = ($_SERVER['HTTP_HOST'] == 'localhost') ? 
            new mysqli("localhost", "root", "", DBNAME) : 
            new mysqli("localhost", "root", DBPASS, DBNAME);

// Not the end of the world
if (mysqli_connect_errno()) {
    exit;
}
?>