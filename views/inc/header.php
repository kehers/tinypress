<!DOCTYPE html>
<html>
	<head>
		<title><?= $_ttl; ?> &middot; Tinypress</title>
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
		<link rel="stylesheet" href="css/style.css?15-10-14" type="text/css" />
		<link rel="stylesheet" type="text/css" href="css/fontawesome.css" />
        <?php
        if ($_css)
            echo '<link rel="stylesheet" type="text/css" href="css/'.$_css.'" />';
        if ($_js)
            echo '<script src="js/'.$_js.'"></script>';
        ?>
	</head>
	<body>
		<div class="wrapper">
			<header class="clearfix">
				<h2><a href="/"><img src="tinypress_logo.png"/> Tinypress</a></h2>
				<nav>					
                     <a href="https://<?= $_SESSION['gh']['username']; ?>.github.io"><?= $_SESSION['gh']['username']; ?>.github.io</a> &mdash; <a href="/" class="a-post">Posts</a> &middot; <a href="/saves" class="a-drafts">Autosaves</a> &middot; <div><span><img src="<?= $_SESSION['gh']['avatar']; ?>" width="20"> <i class="fa fa-angle-down"></i></span>
                     <ul>
                         <li><a href="select-acc">Switch account</a></li>
                         <li><a href="settings" class="settings">Settings</a></li>
                         <li><a href="/logout">Logout</a></li>
                     </ul>
                     </div>
				</nav>
			</header>
            <div class="clearfix body">