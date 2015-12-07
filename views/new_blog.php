<!DOCTYPE html>
<html>
	<head>
		<title>New blog &middot; tinypress</title>
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
		<link rel="stylesheet" href="css/style.css?13-9-14" type="text/css" />
		<link rel="stylesheet" type="text/css" href="css/fontawesome.css" />
	</head>
	<body>
		<div class="wrapper">
			<header class="clearfix">
				<h2><a href="/"><img src="tinypress_logo.png"/> Tinypress</a></h2>
				<nav>
					<img src="<?= $_SESSION['gh']['avatar']; ?>"/> <?= $_SESSION['gh']['username']; ?> &middot; <a href="/logout">logout</a>
				</nav>
			</header>
			<div class="prop" style="margin-bottom:0">
				<h2>Hello stranger, let's set up your blog in two easy steps.</h2>
                <h2 class="color" style="margin-top:20px">1. Select template</h2>
            </div>
        </div>

        <form method="post" id="setup-form">
            <div class="contrast-strip">
                <div class="">
                    <ul class="template" id="template"></ul>
                </div>
            </div>
            <div class="wrapper">
                <div class="prop" style="margin:0">
                    <h2 class="color">2. Configure</h2>
                    <div id="config"></div>
                    <input type="hidden" name="template">
                    <div class="snip">
                        <p>To create your blog, tinypress <span class="highlight">will request private repo</span> access - just this one time only.</p>
                    </div>
                    <button class="button primary" type="submit"><i class="fa fa-check"></i> Create Blog</button>
                </div>
            </div>
        </form>
        
        <ul class="gritter"></ul>
        <script type="text/template" id="template-template">
            <a href="#" class="screenshot-link{{ checked ? ' selected' : '' }}"><img src="{{ screenshot }}" /><i class="fa fa-check-circle-o"></i></a>
            <h2>{{ name }}</h2>
            <p><small>by {{ author }}{{# if (source) { }} &middot; <a href="{{ source }}">source</a> {{# } }}</small></p>
        </script>
        <script type="text/template" id="config-template">
            <p>{{ desc }}</p>
            {{# if (name == 'description') { }}
            <p><textarea name="{{ name }}">{{ def }}</textarea>
            {{# } else { }}
            <p><input type="text" name="{{ name }}" value="{{ def }}"></p>
            {{# } }}
        </script>
                
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/underscore-min.js"></script>
<script type="text/javascript" src="js/backbone-min.js"></script>
<script type="text/javascript" src="js/util.js"></script>
<script type="text/javascript" src="js/template.js"></script>
<script>
$(function(){
    new app.AppView();
    <?php
    $templates = $app->getTemplates();
    if ($_SESSION['error'])
        echo "app.notify('{$_SESSION['error']}');";
    ?>
    window.app.Templates.reset(<?= json_encode($templates); ?>);
});
</script>

<?php
include_once 'inc/footer.php';
?>