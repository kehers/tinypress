<?php
$_ttl = 'Posts';
$_css = 'nprogress.css';
include_once 'inc/header.php';
// get post here because of cname
$_posts = $app->getPosts();
?>
    <div class="posts-view">
        <div class="top-btn">
            <a href="new" class="button new">New post</a>
        </div>
    	<h1 class="ttl">Posts</h1>
        <ul class="feed" id="posts"></ul>
        <ul class="feed hide" id="drafts"></ul>
        <div id="empty-state">
            <h3>You are yet to create a post. <a href="new" class="new">Create one now</a>.</h3>
        </div>
        <p class="more"><a href="#">Load more</a></p>
    </div>
    <div class="settings-view hide">
    	<h1>Settings</h1>
        <?php
        /*
        <div class="row">
            <div class="one"><strong>Plan:</strong>
                < ?php
                if ($_SESSION['paid']) {
                    if ($will_soon_expire && !$_SESSION['auto_renewal']){
                        $show_form = true;
                    }
                }
                if ($show_form) {
                ? >
                <br><br>
                <p class="small fade stripe-powered" style="line-height:1.3em">Payment securely processed by <a href="http://stripe.com/">Stripe</a>.</p>
                < ?php
                }
                unset($show_form);
                ? >
            </div>
            <div class="two pay-div">
                < ?php
                $paid = 1;
                // Expires in a week?
                $diff = date('Y-m-d') - strtotime($_SESSION['plan_ends']);
                if (abs($diff) < 60*60*24*7)
                    $will_soon_expire = true;
                if ($diff > 0)
                    $date_passed = true;
                if ($_SESSION['trial']) {
                    // Free trial
                    $show_form = true;
                    if ($date_passed)
                        $paid = 0;
                    ? >
                    <p class="upgrade-btn-wrp">Your free trial <?= $date_passed ? 'has expired. Upgrade your account to be able to make posts' : 'ends on '.$_SESSION['plan_ends']; ? >.
                < ?php
                }
                else if ($_SESSION['paid']) {
                    // Paid
                    // Show form if will expire soon and no renewal
                    if ($will_soon_expire && !$_SESSION['auto_renewal'])
                        $show_form = true;
                    ? >
                    <p>Your subscription ends <?= $_SESSION['plan_ends']; ?>. <?= !$_SESSION['auto_renewal'] ? 'Autorenewal has been canceled.':'<br><a href="#" class="button primary cancel-renewal-btn" style="font-size:90%">Cancel auto-renewal</a>';
                    ? >
                < ?php
                }
                else {
                    // Not trial, not paid -> expired subscription
                    $show_form = true;
                    $paid = 0;
                    ?>
                    <p class="upgrade-btn-wrp"><span class="error">Your subscription has expired. Upgrade your account to be able to make posts.</span>
                    < ?php
                }
                if ($show_form) {
                ? ><br><a href="#" class="button primary upgrade-btn" style="font-size:90%">Upgrade ($9.99/yr)</a></p>
                    <form method="post" class="upgrade-form hide">
                        <p><input type="text" name="cc_number" placeholder="Card Number"></p>
                        <p><input type="text" name="cc_mm" placeholder="MM" style="width:60px" maxlength="2"> <input type="text" name="cc_yy" placeholder="YY" maxlength="2" style="width:80px"> <input type="text" placeholder="CCV" name="cc_ccv" style="width:142px;margin-left:10px"></p>
                        <p><button class="button primary" type="submit" style="font-size:90%">Pay Securely</button> <a href="#" style="margin-left:20px" class="cancel-pay">Not now</a></p>
                    </form>
                < ?php } ? >
            </div>
        </div>
        //*/
        ?>
        <div class="row">
            <div class="one"><strong>Post by email:</strong></div>
            <div class="two">
                <p><span class="highlight"><?= $_SESSION['post_email']; ?></span><br>
                <span class="small fade">Email your blog post here. Keep email confidential.</span></p>
            </div>
        </div>
        <div class="row">
            <div class="one"><strong>Email:</strong></div>
            <div class="two">
                <form method="post" id="email-form"><input type="email" name="email" placeholder="Where to send receipts and notifications" value="<?= $_SESSION['email']; ?>" /><button type="submit" class="button primary" style="padding:10px 20px;font-size:90%">Update</button></form>
            </div>
        </div>
        <div class="row">
            <div class="one"><strong>Custom Domain:</strong></div>
            <div class="two">
                <p><span class="small fade">Point your custom domain to your Github page. <a href="https://help.github.com/articles/tips-for-configuring-a-cname-record-with-your-dns-provider" target="_blank">See how to set your DNS.</a></span><br />
                <form method="post" id="cname-form"><input type="text" name="cname" placeholder="example.com or blog.example.com" value="<?= $_SESSION['cname']; ?>" /><button type="submit" class="button primary" style="padding:10px 20px;font-size:90%">Update</button></form></p>
            </div>
        </div>
        <div class="row">
            <div class="one"><strong>Timezone:</strong></div>
            <div class="two">
                <form method="post" id="timezone-form">
                <?php
                include 'inc/tz.php';
                ?>
                <button type="submit" class="button primary" style="padding:10px 20px;font-size:90%">Update</button></form>
            </div>
        </div>
        
        <!--p>&nbsp;</p><h2>Advanced settings</h2>
        <div class="row">
            <div class="one"><strong>Change theme:</strong></div>
            <div class="two">
                <p><a href="change-theme">Change your blog's theme.</a></p>
            </div>
        </div-->
    </div>
</div><!-- /body -->
</div><!-- /wrapper -->
<div class="new-post-view hide">
    <form method="post" class="post-form">
        <p class="wrapper"><input type="text" name="title" class="big" placeholder="Post Title" /></p>
        <p class="wrapper textarea" style="margin-bottom:0">
            <textarea name="body" placeholder="Post content" autofocus></textarea><div class="preview-pane"></div>
        </p>
        <div class="wrapper" style="margin-top:0">
            <div style="padding:5px 20px">
                <p><a href="#" class="adv-options-switch"><i class="fa fa-angle-right"></i> advanced options</a></p>
                <div class="panel hide adv-options" style="margin-bottom:10px">
                    <input type="hidden" name="save_id" />
                    <input type="hidden" name="schedule_id" />
                    <input type="hidden" name="action" />
                    <input type="hidden" name="sha" />
                    <p><input type="text" placeholder="Post path (year-month-day-title.markup)" name="url" /></p>
                    <p><input type="text" placeholder="Tags (Space separated)" name="tags" /></p>
                    <p><input type="text" placeholder="Categories (Space separated)" name="categories" /></p>
                    <p><label></label><input type="text" placeholder="Commit message" name="commit" /></p>
                    <p><input type="text" placeholder="Permalink (Will override post URL)" name="permalink" /></p>
                </div>
                <div style="padding:5px 0">
                <a href="/" class="a-post fright" style="margin-top:10px"><i class="fa fa-angle-left"></i> Back</a>
                    <div class="button-grp">
                        <ul>
                            <li><a href="#" class="btn-grp-switch"><i class="fa fa-angle-down"></i></a><button>Preview</button></li>
                            <li><button>Post</button></li>
                            <li><button>Post later</button></li>
                            <li><button>Save draft on Github</button></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<!-- modal window -->
<div class="overlay tz-overlay">
    <div class="modal">
        <div class="modal-content">
            <form method="post">
                <h3>When</h3>
                <input type="text" name="at" placeholder="A date or/and time e.g tomorrow at 1pm">
                <?php
                if (!isset($_SESSION['tz'])) {
                ?>
                <div class="schedule-tz">
                    <h3>Timezone</h3>
                    <?php
                    include 'inc/tz.php'
                    ?>
                </div>
                <?php
                }
                ?>
                <p><button type="submit" class="button primary" style="margin:10px;font-size:90%;width:270px">Schedule</button></p>
                <p class="center"><a href="#" class="modal-close">or <strong>cancel</strong></a></p>
            </form>
        </div>
    </div>
</div>
<!-- /modal -->
<div class="wrapper">
    <ul class="gritter"></ul>
    <script type="text/template" id="post-template">
        <p><span class="date">{{ date }}</span> <span class="post-ttl">{{ later ? '<a href="edit/'+path+'" class="edit">' : '<a href="http://<?= $_SESSION['gh']['default_repo']; ?>/'+url+'" target="_blank">' }}{{ title }}</a>{{ draft ? '<em title="Drafts saved on Github pages">draft</em>' : later ? '<em title="Scheduled post"><i class="fa fa-clock-o"></i> '+send_at+'</em>' : '' }}</span> <span class="actions"><a href="edit/{{ path }}" class="edit" title="Edit post"><i class="fa fa-pencil"></i></a> <a href="delete/{{ path }}" class="delete" title="Delete post"><i class="fa fa-trash-o"></i></a></span></p>
    </script>
    <script type="text/template" id="draft-template">
        <p><span class="date">{{ date }}</span> <span class="post-ttl"><a href="draft-edit/{{ id }}">{{ title ? title : '<i>[untitled]</i>' }}</a></span> <span class="actions"><a href="draft-delete/{{ id }}" class="delete" title="Delete autosave"><i class="fa fa-trash-o"></i></a></span></p>
    </script>
    <script type="text/javascript" src="js/jquery.min.js"></script>
    <script type="text/javascript" src="js/underscore-min.js"></script>
    <script type="text/javascript" src="js/backbone-min.js"></script>
    <script type="text/javascript" src="js/nprogress.js"></script>
    <script>
    $(function(){
        app.options = {
            date: '<?= date('Y-m-d'); ?>',
            page: 1,
            tz: <?= isset($_SESSION['tz']) ? 1 : 0; ?>,
            /*paid: <?= (int) $paid; ?>,
            stripe_key: '<?= STRIPE_PK; ?>',*/
            more: false,
            path: ''
        };
    });
    </script>
    <script type="text/javascript" src="js/util.js"></script>
    <script type="text/javascript" src="js/app.js?15-10-14"></script>
    <script>
    $(function(){
        new app.AppView();
        <?php
        if ($_SESSION['status'])
            echo "app.notify('{$_SESSION['status']}', true);";
        ?>
        window.app.Posts.reset(<?= json_encode($_posts); ?>);
        window.app.Drafts.reset(<?= json_encode($app->getDrafts()); ?>);
        <?php
        if ($_SESSION['page'])
            echo "$('{$_SESSION['page']}').trigger('click');";
        ?>
    });
    </script>
    <!--script type="text/javascript" src="https://js.stripe.com/v2/"></script-->
<div>
<?php
include_once 'inc/footer.php';
?>