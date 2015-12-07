<?php
$_ttl = 'Create and easily manage your blog on Github';
include_once 'inc/header_home.php';
?>
			<div class="prop">
				<h2>Create and easily manage your blog on Github</h2>
                <p style="padding:5px 50px">Tinypress gives you an interface to create and manage your blog on Github pages without worrying about the many technical details and setups.</p>
                <?= $_SESSION['error'] ? '<p class="error">'.$_SESSION['error'].'</p>':''; ?>
				<p><a href="login" class="button">Login with Github</a></p>
            </div>
            <div class="features-wrapper">
                <h2>Why Github?</h2>
                <ul class="features">
                    <li>
                        <i class="fa fa-github fa-2x"></i><h3>Powerful free hosting</h3>
                        <p>Your blog is freely hosted on the powerful servers of Github via <a href="http://pages.github.com/">Github pages</a>. Plus you get a free <span class="highlight">.github.io</span> subdomain.</p>
                    </li>
                    <li>
                    <i class="fa fa-share-alt fa-2x"></i><h3>Opensource/Collaboration</h3>
                        <p>Welcome to Opensource. From error correction to design edits, anyone can contribute to your blog.</p>
                    </li>
                    <li>
                        <i class="fa fa-files-o fa-2x"></i><h3>Versioning</h3>
                        <p>Your pages are versioned. This means you can easily switch between different versions of every files of your site.</p>
                    </li>
                    <li>
                        <i class="fa fa-git fa-2x"></i><h3>Your content, your files</h3>
                        <p>Your files and content are hosted on your repo as static files. Download, sync and edit them across your devices.</p>
                    </li>
                </ul>
                <h2>Why Tinypress?</h2>
                <ul class="features">
                    <li>
                        <i class="fa fa-desktop fa-2x"></i><h3>Simple Interface</h3>
                        <p>You are provided with a simple clutter-free interface to keep you focused on one thing - writing.</p>
                    </li>
                    <li>
                        <i class="fa fa-file-text fa-2x"></i><h3>Themes</h3>
                        <p>Pick from a growing list of simple, beautiful and customizable themes to get started.</p>
                    </li>
                    <li>
                        <i class="fa fa-users fa-2x"></i><h3>Organization support</h3>
                        <p>You can create and manage not just your blog but also that of Github organizations you belong to.</p>
                    </li>
                    <li>
                        <i class="fa fa-clock-o fa-2x"></i><h3>Scheduled post</h3>
                        <p>Create your post and set it to be sent at later. Go on with your normal day and let Tinypress take care of the rest.</p>
                    </li>
                    <li>
                        <i class="fa fa-paragraph fa-2x"></i><h3>Markdown/HTML editor</h3>
                        <p>Preview your Markdown or HTML post. Tinypress supports Markdown parsers like Kramdown, Redcapet and Maruku.</p>
                    </li>
                    <li>
                        <i class="fa fa-save fa-2x"></i><h3>Autosave</h3>
                        <p>Don't worry about losing a post or completing it. Your posts are automatically saved so you can always come back to continue.</p>
                    </li>
                    <li>
                        <i class="fa fa-send fa-2x"></i><h3>Email to post</h3>
                        <p>Want to quickly blog something on the go? Tinypress gives you a unique email you can email your posts to.</p>
                    </li>
                    <li>
                        <i class="fa fa-android fa-2x"></i><h3>Android app</h3>
                        <p>Tinypress is <a href="https://play.google.com/store/apps/details?id=co.tinypress.android">available on Android</a>. More mobile clients coming too.</p><p><a href="https://play.google.com/store/apps/details?id=co.tinypress.android" target="_blank"><img src="images/en_generic_rgb_wo_45.png" title="Get it on Google play"></a></p>
                    </li>
                    <li>
                        <i class="fa fa-plus fa-2x"></i><h3>More...</h3>
                        <p>Tinypress is continuously developed. Expect more great features from time to time. Interested in a feature? Tweet <a href="http://twitter.com/tinypressco">@tinypressco</a> or mail <a href="mailto:love@tinypress.co">love@tinypress.co</a></p>
                    </li>
                </ul>
            </div>
            <div class="prop">
				<p><a href="login" class="button">Login with Github</a></p>
				<!--p class="fade small">$9.99/year. 30 days free trial.</p-->
<?php
include_once 'inc/footer.php';
?>