<!DOCTYPE html>
<html>
	<head>
		<title>Error &middot; tinypress</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="/css/style.css" type="text/css" />
		<link rel="stylesheet" type="text/css" href="/css/fontawesome.css" />
	</head>
	<body>
		<div class="wrapper">
			<header class="clearfix">
				<h2><a href="/"><img src="/tinypress_logo.png"/> tinypress</a></h2>
			</header>
			<div class="prop">
				<h2>:/</h2>
                <p class="error"><?= $_SESSION['error']; ?></p>
                <p><i class="fa fa-github"></i> <?= $_SESSION['gh']['username']; ?> &middot; <a href="logout">logout</a></p>
<?php
include_once 'inc/footer.php';
?>