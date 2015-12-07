<?php
$_ttl = 'Change theme';
$_css = 'nprogress.css';
include_once 'inc/header.php';
?>
                <div class="prop" style="margin:0">
                    <h2>Change your blog's theme</h2>
                    <div style="text-align:left">
                        <h3>Note:</h3>
                        <ol style="margin-left:30px">
                            <li>This will overwrite any custom modification you have made to your blog.</li>
                            <li>This is a reversible action. But be sure you know how to roll back should you need to.</li>
                        </ol>
                    </div>
                    <h2 class="color" style="margin-top:20px">1. Select template</h2>
                </div>
            </div><!-- body -->
        </div><!-- wrapper -->

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
                    <button class="button primary" type="submit"><i class="fa fa-check"></i> Update theme</button>
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