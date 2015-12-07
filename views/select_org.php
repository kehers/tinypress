<!DOCTYPE html>
<html>
	<head>
		<title>Select account &middot; tinypress</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="apple-touch-icon" sizes="57x57" href="/apple-touch-icon-57x57.png">
        <link rel="apple-touch-icon" sizes="114x114" href="/apple-touch-icon-114x114.png">
        <link rel="apple-touch-icon" sizes="72x72" href="/apple-touch-icon-72x72.png">
        <link rel="apple-touch-icon" sizes="144x144" href="/apple-touch-icon-144x144.png">
        <link rel="apple-touch-icon" sizes="60x60" href="/apple-touch-icon-60x60.png">
        <link rel="apple-touch-icon" sizes="120x120" href="/apple-touch-icon-120x120.png">
        <link rel="apple-touch-icon" sizes="76x76" href="/apple-touch-icon-76x76.png">
        <link rel="apple-touch-icon" sizes="152x152" href="/apple-touch-icon-152x152.png">
        <link rel="icon" type="image/png" href="/favicon-196x196.png" sizes="196x196">
        <link rel="icon" type="image/png" href="/favicon-160x160.png" sizes="160x160">
        <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96">
        <link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16">
        <link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32">
        <meta name="msapplication-TileColor" content="#da532c">
        <meta name="msapplication-TileImage" content="/mstile-144x144.png">
		<link rel="stylesheet" href="css/style.css?31-7-14" type="text/css" />
		<link rel="stylesheet" type="text/css" href="css/fontawesome.css" />
	</head>
	<body>
		<div class="wrapper">
			<header class="clearfix">
				<h2><a href="/"><img src="tinypress_logo.png"/> tinypress</a></h2>
				<nav>
					<a href="/logout">logout</a>
				</nav>
			</header>
            <div class="clearfix body">
                <p>&nbsp;</p>
                <h1 class="ttl">Select account</h1>
                <ul class="selection">
                    <li>
                        <p><span class="fade">Default account</span> <img src="<?= $_SESSION['gh']['main_avatar']; ?>"> <a href="?sel=<?= $_SESSION['gh']['main_username']; ?>"><?= $_SESSION['gh']['main_username']; ?></a></p>
                    </li>
                    <?php
                    //print_r($_SESSION['gh']['orgs']);
                    foreach ($_SESSION['gh']['orgs'] as $v) {
                    ?>
                    <li>
                        <p><span class="fade">Organization</span> <img src="<?= $v['avatar']; ?>"> <a href="?sel=<?= $v['username']; ?>"><?= $v['username']; ?></a></p>
                    </li>
                    <?php
                    }
                    ?>
                </ul>
<?php
include_once 'inc/footer.php';
?>