<?php
class App {

    // Mixpanel
    private $mp;
    private $mailer;

    private $redis;
    private $eTags = array();
    private $mixpanel_token = '';
    private $api_root = 'https://api.github.com';

    public $access_token;

	function __construct($token = null){
        $this->mp = null;
        $this->mailer = null;

        $this->redis = new Redis();
        $this->redis->pconnect('127.0.0.1');
        $this->redis->auth('YbHfwSaldpEILOKmsZaGp8PAtVYMVYHsie');//*/

        $this->eTags = $_SESSION['eTags'];
        if ($token)
            $this->access_token = $token;

        $this->template_replace = array(
                        '|#name|',
                        '|#email|',
                        '|#gravatar|',
                    );
        $this->template_pattern = array(
                        $_SESSION['gh']['name'],
                        $_SESSION['gh']['email'],
                        $this->gravatar($_SESSION['gh']['email'], 120)
                    );

        // Db
        global $mysqli;
        $this->db = &$mysqli;
    }

    function __destruct() {
        // Save to session
        $_SESSION['eTags'] = $this->eTags;
    }

	private function initMxPanel() {
		if ($this->mp)
			return;

		require_once dirname(__FILE__).'/lib/mixpanel/Mixpanel.php';
		$this->mp = Mixpanel::getInstance($this->mixpanel_token, array(
            'use_ssl' => false
        ));
	}

    // Auth
    function checkLogged() {
        if (!$_SESSION['gh']['username']) {
            header('location:login');
            exit;
        }
    }

    function resetAccessToken(){
        $this->access_token = null;
    }

    function getAccessToken($code = null) {
        if ($this->access_token)
            return $this->access_token;

        $data = array(
            'client_id' => CLIENT_ID,
            'client_secret' => CLIENT_SECRET,
            'code' => $code
        );
        $response = $this->http(ACCESS_TOKEN_URL, $data, 'POST');
        $json = json_decode($response[1]);

        if (!$json->access_token)
            return false;

        $this->access_token = $json->access_token;
        return $json->access_token;
    }

    // Posts

    function getPosts($page = 1, $start = null) {
        $page = (int) $page;
        $count = $page * 5;
        if (!isset($start))
            $start = $count - 5;

        $start = (int) $start;

        $posts = array();
        //return $posts;

        $endpoint = $this->api_root.'/repos/'.$_SESSION['gh']['username'].'/'.$_SESSION['gh']['default_repo'].'/contents/_posts';

        // tmp
        $response[1] = $this->redis->get($endpoint);
        // Force refresh
        if ($start == 0 || !$response[1]) {
            // Get send laters here
            $q = "select id, title, path, send_at_raw, date from schedule where user='{$_SESSION['gh']['id']}' order by date desc";
            //echo $q;
            $r = $this->db->query($q);
            while($row = $r->fetch_array(MYSQLI_ASSOC)) {
                $row['send_at'] = $row['send_at_raw'];
                $row['later'] = true;
                $row['sha'] = '';
                $row['url'] = '';
                $row['draft'] = false;
                $row['date'] = date('M d, Y', strtotime($row['date']));
                //$data = json_decode(base64_decode($row['data']), true);
                unset($row['send_at_raw']);
                $posts[] = $row;
            }
            // tmp
            //return $posts;
            // delete etag
            unset($this->eTags[$endpoint]);
            $response = $this->http($endpoint);
            if ($response[0] == 304) {
                // Get from cache
                $response[1] = $this->redis->get($endpoint);
            }
            else if ($response[0] == 200) {
                // cache (1 day)
                $this->redis->setex($endpoint, 60*60*24, $response[1]);
            }
            else
                return $posts;
        }

        $json = array_reverse(json_decode($response[1], true));

        foreach ($json as $k => $v) {
            if ($k < $start)
                continue;

            // Get post
            if ($k == $count)
                break;

            $post_endpoint = $this->api_root.'/repos/'.$_SESSION['gh']['username'].'/'.$_SESSION['gh']['default_repo'].'/contents/'.$v['path'];
            // Only check github if there is no cached value
            $body[1] = $this->redis->get($post_endpoint);
            if (!$body[1]) {
                $body = $this->http($post_endpoint, null, 'GET', array('Accept' => 'application/vnd.github.VERSION.raw'));

                if ($body[0] == 304) {
                    // Get from cache
                    $body[1] = $this->redis->get($post_endpoint);
                }
                else if ($body[0] == 200) {
                    // cache (1 day)
                    $this->redis->setex($post_endpoint, 60*60*24, $body[1]);
                }
                else // fail
                    continue;
            }

            $formatted_post = $this->format($body[1]);
            list($date, $url) = $this->getTitleAndDate($v['name'], $formatted_post['permalink'], $formatted_post['categories']);
            $posts[] = array(
                            'id' => $v['sha'],
                            'title' => $formatted_post['title'],
                            'date' => $date,
                            'sha' => $v['sha'],
                            'path' => $v['path'],
                            'url' => $url,
                            'draft' => $formatted_post['published'] == 'true' ? false : true
                        );
        }

        // Get CNAME
        if (!$_SESSION['gotten_configs']) {
            $cname = $this->api_root.'/repos/'.$_SESSION['gh']['username'].'/'.$_SESSION['gh']['default_repo'].'/contents/CNAME';
            unset($this->eTags[$cname]);
            $body = $this->http($cname, null, 'GET');
            if ($body[0] == 200) {
                // Has a CNAME, save it
                $json = json_decode($body[1], true);
                $_SESSION['cname'] = base64_decode($json['content']);
                $_SESSION['cname_sha'] = $json['sha'];
            }

            // Configs
            $config = $this->api_root.'/repos/'.$_SESSION['gh']['username'].'/'.$_SESSION['gh']['default_repo'].'/contents/_config.yml';
            unset($this->eTags[$config]);
            $body = $this->http($config, null, 'GET');
            if ($body[0] == 200) {
                $json = json_decode($body[1], true);
                $body = base64_decode($json['content']);
                $_yaml = preg_split('/\n|\r/', $body, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($_yaml as $line) {
                    if (preg_match('/^#/', $line))   continue;
                    list($k, $v) = preg_split('/:\s?/', $line, 2);
                    $k = trim($k);
                    $v = trim($v);
                    if ($k == 'markdown') {
                        $_SESSION['markdown_editor'] = $v;
                    }
                    else if ($k == 'permalink') {
                        // permalink
                        $_SESSION['default_permalink'] = $v;
                    }
                }
            }
            $_SESSION['gotten_configs'] = true;
        }

        return $posts;
    }

    function getPost($path, $scheduled){
        // Scheduled post?
        if ($scheduled) {
            $path = $this->escape($path);
            $q = "select * from schedule where user='{$_SESSION['gh']['id']}' and path='{$path}'";
            //echo $q;
            $r = $this->db->query($q);
            $row = $r->fetch_array(MYSQLI_ASSOC);
            $data = json_decode(base64_decode($row['data']), true);
            //print_r($data);
            $formatted_post = $this->format(base64_decode($data['content']));
            return array(
                            'title' => $row['title'],
                            'date' => $row['date'],
                            'path' => $path,
                            'url' => '',
                            'schedule_id' => $row['id'],
                            'body' => $formatted_post['body'],
                            'tags' => $formatted_post['tags'],
                            'categories' => $formatted_post['categories'],
                            'permalink' => $formatted_post['permalink'],
                            'draft' => false,
                            'later' => true,
                            'send_at' => $row['send_at_raw']
                        );
        }

        // No, not scheduled post
        $post_endpoint = $this->api_root.'/repos/'.$_SESSION['gh']['username'].'/'.$_SESSION['gh']['default_repo'].'/contents/'.$path;
        //$body[1] = $this->redis->get($post_endpoint);
        //if (!$body[1]) {
            $body = $this->http($post_endpoint, null, 'GET', array('Accept' => 'application/vnd.github.VERSION.raw'));

            if ($body[0] == 304) {
                // Get from cache
                $body[1] = $this->redis->get($post_endpoint);
            }
            else if ($body[0] == 200) {
                // cache (1 day)
                $this->redis->setex($post_endpoint, 60*60*24, $body[1]);
            }
            else // fail
                return false;
        //}

        $formatted_post = $this->format($body[1]);
        list($date) = $this->getTitleAndDate($post_endpoint);
        return array(
                        'title' => $formatted_post['title'],
                        'date' => $date,
                        'path' => $path,
                        'url' => preg_replace('|.*/|', '', $path),
                        'body' => $formatted_post['body'],
                        'tags' => $formatted_post['tags'],
                        'categories' => $formatted_post['categories'],
                        'permalink' => $formatted_post['permalink'],
                        'draft' => $formatted_post['published'] == 'true' ? false : true
                    );
    }

    function deleteScheduled($schedule_id, $user_id){
        $schedule_id = (int) $schedule_id;
        $q = "delete from schedule where user='{$user_id}' and id='{$schedule_id}'";
        $this->db->query($q);
        return $this->db->affected_rows;
    }

    function delete($title, $path, $sha, $schedule){
        if ($schedule) {
            $q = "delete from schedule where user='{$_SESSION['gh']['id']}' and path='$path'";
            $this->db->query($q);
            return $this->db->affected_rows;
        }

        $endpoint = $this->api_root.'/repos/'.$_SESSION['gh']['username'].'/'.$_SESSION['gh']['default_repo'].'/contents/'.$path;
        $this->http($endpoint, array(
                                'path' => $path,
                                'sha' => $sha,
                                'message' => 'Post deleted: '.$title,
                            ), 'DELETE');
    }

    function getTitleAndDate($name, $permalink = null, $_categories = null) {
        preg_match('/([0-9]{4})\-([0-9]{2})\-([0-9]{2})\-(.*)/', $name, $match);

        $year = $match[1];
        $month = $match[2];
        $day = $match[3];
        //$title = preg_replace('|\.[^\.]*$|', '.html', $match[4]);
        $title = preg_replace('|\.[^\.]*$|', '', $match[4]);
        $strtotime = strtotime("$year-$month-$day");
        $date = date('M d, Y', $strtotime);

        if ($_categories) {
            $categories = implode('/', explode(', ', $_categories));
        }

        if ($permalink) {
            $url = $permalink;
        }
        else {
            switch ($_SESSION['default_permalink']) {
                case 'none':
                    $url = "$categories/{$title}.html";
                    break;
                case 'pretty':
                    $url = "$categories/$year/$month/$day/$title/";
                    break;
                case 'date':
                default:
                    if (empty($_SESSION['default_permalink'])) {
                        $url = "$categories/$year/$month/$day/{$title}.html";
                    }
                    else {
                        // custom format
                        $pattern = array(
                            '/:month/',
                            '/:i_month/',
                            '/:year/',
                            '/:day/',
                            '/:i_day/',
                            '/:short_year/',
                            '/:title/',
                            '/:categories/'
                        );
                        $replacement = array(
                            $month,
                            (int) $month,
                            $year,
                            $day,
                            (int) $day,
                            date('y', $strtotime),
                            $title,
                            $categories
                        );
                        $url = preg_replace($pattern, $replacement, $_SESSION['default_permalink']);
                    }
                    break;
            }
        }

        $url = ltrim($url, '/');

        return array($date, $url);
    }

    function saveDraft($_data) {
        $title = $this->escape($_data['title']);
        $body = $this->escape($_data['body']);
        $id = (int) $_data['id'];

        if (!$title && !$body)
            return false;

        if ($id) {
            $q = "update drafts set title='$title', body='$body', date=now() where user='{$_SESSION['gh']['username']}' and id='$id'";
            //echo $q;
            $r = $this->db->query($q);

            return array('id' => $id);
        }
        else {
            $q = "insert into drafts (title, body, date, user) values ('$title', '$body', now(), '{$_SESSION['gh']['username']}')";
            $r = $this->db->query($q);

            return array('id' => $this->db->insert_id);
        }
    }

    function getDrafts() {
        $q = "select id, title, body, date from drafts where user='{$_SESSION['gh']['username']}' order by date desc";
        $r = $this->db->query($q);
        $results = array();
        while($row = $r->fetch_array(MYSQLI_ASSOC)) {
            $row['date'] = date('M d, Y', strtotime($row['date']));
            $results[] = $row;
        }

        return $results;
    }

    function deleteDraft($id) {
        $id = (int) $id;
        $q = "delete from drafts where user='{$_SESSION['gh']['username']}' and id='$id'";
        $this->db->query($q);

        return $this->db->affected_rows;
    }

    function getUser() {
        if ($_SESSION['gh']['username'])
            return $_SESSION['gh']['username'];

        $response = $this->http($this->api_root.'/user');
        $json = json_decode($response[1]);

        $this->initMxPanel();

        if (!$json->login) {
            // Track failed login
            $this->mp->track('Failed login');
            return false;
        }

        $_SESSION['gh']['id'] = $_SESSION['gh']['main_id'] = $json->id;
        $_SESSION['gh']['email'] = $_SESSION['gh']['main_email'] = $json->email;
        $_SESSION['gh']['name'] = $_SESSION['gh']['main_name'] = $json->name ? $json->name : $json->login;
        $_SESSION['gh']['username'] = $_SESSION['gh']['main_username'] = $json->login;
        $_SESSION['gh']['avatar'] = $_SESSION['gh']['main_avatar'] = $json->avatar_url;

        // Get subscription plans here
        $this->getSubscription();

        // Get orgs
        $response = $this->http($this->api_root.'/users/'.$_SESSION['gh']['username'].'/orgs');
        if ($response[0] == 200) {
            $orgs = json_decode($response[1], true);
            foreach($orgs as $org) {
                $_SESSION['gh']['orgs'][] = array(
                                            'id' => $org['id'],
                                            'username' => $org['login'],
                                            'avatar' => $org['avatar_url']
                                        );
            }
        }

        // Track login
        $this->mp->people->set($_SESSION['gh']['id'], array(
                'username' => $json->login,
                '$name' => $json->name
            ));
        $this->mp->identify($_SESSION['gh']['id']);
        $this->mp->track('Login');

        return $json->login;
    }

    function getSubscription() {
        $r = $this->db->query("select plan_ends, paid, trial, auto_renewal from profiles where id='{$_SESSION['gh']['main_id']}'");
        if ($r->num_rows > 0) {
            list($plan_ends, $_SESSION['paid'], $_SESSION['trial'], $_SESSION['auto_renewal']) = $r->fetch_array(MYSQLI_NUM);
            $plan_ends = strtotime($plan_ends);
        }
        else {
            $plan_ends = mktime(0, 0, 0, date("m")+1, date("d"), date("Y"));
            $_SESSION['paid'] = 0;
            $_SESSION['trial'] = 1;
            $_SESSION['auto_renewal'] = 1;
        }

        $_SESSION['plan_ends'] = date('M d, Y', $plan_ends);
    }

    function setOrg($sel) {
        unset($_SESSION['gh']['page_repos']);
        unset($_SESSION['gh']['org_repo']);

        // Get configs again
        unset($_SESSION['gotten_configs']);

        // If main account was selected
        if ($sel == $_SESSION['gh']['main_username']) {
            $_SESSION['gh']['id'] = $_SESSION['gh']['main_id'];
            $_SESSION['gh']['email'] = $_SESSION['gh']['main_email'];
            $_SESSION['gh']['name'] = $_SESSION['gh']['main_name'];
            $_SESSION['gh']['username'] = $_SESSION['gh']['main_username'];
            $_SESSION['gh']['avatar'] = $_SESSION['gh']['main_avatar'];

            return;
        }

        // else
        foreach($_SESSION['gh']['orgs'] as $org) {
            if ($org['username'] == $sel) {
                $_SESSION['gh']['id'] = $org['id'];
                $_SESSION['gh']['name'] = $org['username'];
                $_SESSION['gh']['username'] = $org['username'];
                $_SESSION['gh']['avatar'] = $org['avatar'];
                $_SESSION['gh']['org_repo'] = true;

                break;
            }
        }
    }

    function getPagesRepo() {
        if ($_SESSION['gh']['page_repos'])
            return $_SESSION['gh']['page_repos'];

        $response = $this->http($this->api_root.'/search/repositories?q='.$_SESSION['gh']['username'].'.github.+in:name+user:'.$_SESSION['gh']['username']);
        $json = json_decode($response[1], true);

        $this->initMxPanel();

        if (!$json['items']) {
            // Track no repo
            $this->mp->identify($_SESSION['gh']['id']);
            $this->mp->track('No repo');

            return;
        }

        $_SESSION['gh']['page_repos'] = array();
        foreach ($json['items'] as $repos) {
            $_SESSION['gh']['page_repos'][] = $repos['name'];
        }

        $this->updateToken();
    }

    function updateToken() {
        // save token
        $post_email = $this->getPostEmail();
        $this->db->query("insert into profiles (id, username, token, name, post_email, repo, plan_ends) values ('{$_SESSION['gh']['id']}', '{$_SESSION['gh']['username']}', '{$_SESSION['token']}', '{$_SESSION['gh']['name']}', '{$post_email}', '{$_SESSION['gh']['page_repos'][0]}', DATE_ADD(now(), INTERVAL 30 DAY)) on duplicate key update token='{$_SESSION['token']}', repo='{$_SESSION['gh']['page_repos'][0]}'");

        // Update email if any
        if ($_SESSION['gh']['email'])
            $this->db->query("update profiles set email='{$_SESSION['gh']['email']}' where id='{$_SESSION['gh']['id']}' and email_confirmed='0'");

        // Get post email
        $r = $this->db->query("select email, post_email, tz, tz_country from profiles where id='{$_SESSION['gh']['id']}'");
        list($email, $post_email, $tz, $tz_country) = $r->fetch_array(MYSQLI_NUM);
        $_SESSION['post_email'] = $post_email;
        $_SESSION['email'] = $email;
        $_SESSION['tz'] = (int) $tz;
        $_SESSION['tz_country'] = $tz_country;

        $this->getSubscription();
    }

    function upgrade($_data){
        $token = $_data['token'];

        require_once 'lib/Stripe.php';
        Stripe::setApiKey(STRIPE_SK);

        try {
            // Create customer
            $customer = Stripe_Customer::create(array(
              "card" => $token,
              "plan" => 'annual',
              "email" => $_SESSION['email'],
              "description" => $_SESSION['gh']['username']
              )
            );
        }
        catch(Stripe_CardError $e) {
            $body = $e->getJsonBody();
            $_SESSION['error'] = $body['error']['message'];
            return false;
        }
        catch(Stripe_InvalidRequestError $r) {
            $body = $r->getJsonBody();
            $_SESSION['error'] = $body['error']['message'];
            return false;
        }

        //print_r($customer);

        // Update db
        $this->db->query("update profiles set plan_ends=DATE_ADD(now(), INTERVAL 1 YEAR), paid='1', trial='0', auto_renewal=1, stripe_customer_id='{$customer->id}', stripe_plan_id='{$customer->subscriptions->data[0]->id}' where id='{$_SESSION['gh']['main_id']}'");
        $_SESSION['paid'] = 1;
        $_SESSION['trial'] = false;
        $_SESSION['plan_ends'] = date('M d, Y', mktime(0, 0, 0, date("m"), date("d"), date("Y")+1));

        return $_SESSION['plan_ends'];
    }

    // mail to post
    function mailToPost($_params) {
        $to = $this->escape($_params['recipient']);
        $email_from = $this->escape($_params['sender']);
        $from = $this->escape($_params['from']);

        // get token
        $r = $this->db->query("select id, username, token, repo from profiles where post_email='$to'");
        if ($r->num_rows < 1) {
            // 404 mail
            if (!preg_match('|post\.tinypress\.co|i', $email_from)) {
                $txt = 'Your post could not be posted. No user with the email '.$to.' found.'."\r\n\r\n";
                $txt .= 'Love, Tinypress <https://tinypress.co>';
                $this->mail('Email not posted', $email_from, $txt);
            }
            return false;
        }

        list($id, $username, $token, $repo) = $r->fetch_array(MYSQLI_NUM);

        $subject = $this->escape($_params['subject']);
        $text = $this->escape($_params['body-plain']);

        $this->access_token = $token;
        $_SESSION['gh']['id'] = $id;
        $_SESSION['gh']['username'] = $username;
        $_SESSION['gh']['default_repo'] = $repo;

        $args = array(
            'title' => $subject,
            'body' => $text,
        );
        $this->post($args);

        // log mail dump
        $this->db->query("insert into mail_dump (username, frm, subject, data) values ('{$_SESSION['gh']['username']}', '{$from}', '{$subject}', '".$this->db->real_escape_string(serialize($_params))."')");

        // send success email
        if (!preg_match('|post\.tinypress\.co|i', $email_from)) {
            $txt = 'Your post has been sent and should be available on your blog shortly.'."\r\n\r\n";
            $txt .= 'Love, Tinypress <https://tinypress.co>';
            $this->mail('Post successful', $email_from, $txt);
        }

        // track
        $this->mp->identify($_SESSION['gh']['id']);
        $this->mp->people->increment($_SESSION['gh']['id'], 'Posts', 1);
        $this->mp->people->increment($_SESSION['gh']['id'], 'via Mail', 1);
        $this->mp->track('Post');

        $_SESSION = array();
    }

    function updateCname($_post){
        // remove http(s)
        $cname = $this->escape(strtolower($_post['cname']));
        $cname = preg_replace('|^https?://|', '', $cname);
        $_SESSION['cname'] = $cname;

        $data = array(
                    'path' => 'CNAME',
                    'message' => 'Custom domain',
                    'content' => base64_encode($cname),
                );
        if ($_SESSION['cname_sha'])
            $data['sha'] = $_SESSION['cname_sha'];
        $endpoint = $this->api_root.'/repos/'.$_SESSION['gh']['username'].'/'.$_SESSION['gh']['default_repo'].'/contents/CNAME';
        $response = $this->http($endpoint, $data, 'PUT');

        if ($response[0] == 200 || $response[0] == 201) {
            return true;
        }

        $json = json_decode($response[1]);
        $_SESSION['error'] = $json->message;

        return false;
    }

    function updateTZ($_post){
        $tz = (int) $_post['tz'];
        list($country) = explode(',', $_post['country']);

        $this->db->query("update profiles set tz='$tz', tz_country='$country' where id='{$_SESSION['gh']['main_id']}'");
        $_SESSION['tz'] = $tz;
        $_SESSION['tz_country'] = $country;

        return true;
    }

    function updateEmail($_post){
        $email = $this->escape(strtolower($_post['email']));

        if (!$this->validate_email($email)) {
            $_SESSION['error'] = 'Invalid email. Kindly confirm and try again.';
            return false;
        }

        $this->db->query("update profiles set email='$email', email_confirmed=1 where id='{$_SESSION['gh']['main_id']}'");
        $_SESSION['email'] = $email;

        return true;
    }

    function cancelRenewal(){
        $this->db->query("update profiles set auto_renewal=0 where id='{$_SESSION['gh']['main_id']}'");

        $r = $this->db->query("select stripe_customer_id, stripe_plan_id from profiles where id='{$_SESSION['gh']['main_id']}'");
        list($cus_id, $pay_id) = $r->fetch_array(MYSQLI_NUM);

        require_once('lib/Stripe.php');
        Stripe::setApiKey(STRIPE_SK);
        $cu = Stripe_Customer::retrieve($cus_id);
        $cu->subscriptions->retrieve($pay_id)->cancel(array('at_period_end' => true));

        return true;
    }

    function preview($text, $parser = 'kramdown'){
        $data = array(
                    'markdown' => $text
                );
        $parser = strtolower($parser);
        $parsers = array('kramdown','maruku','rdiscount','redcarpet');
        if (!in_array($parser, $parsers))
            $parser = 'kramdown';
        $data['parser'] = $parser;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_URL, "http://tinypress.co:81/");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);
        //$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return $response;
    }

    function onPro(){
        /*$diff = strtotime(date('Y-m-d')) - strtotime($_SESSION['plan_ends']);
        if ($diff > 0)
            return false;*/

        return true;
    }

    // send to github
    function post($args) {
        /*if (!$this->onPro()) {
            $_SESSION['error'] = 'Kindly upgrade your account to be able to post.';
            return false;
        }*/

        // title
        $title = $args['title'];
        if (!$title) {
            $_SESSION['error'] = 'You missed the post title.';
            return false;
        }

        // Send later at?
        if ($args['action'] == 'schedule') {
            // replace unneeded prepositions
            $_rep = array(
                            '| at |i',
                            '| by |i',
                            '| on |i'
                        );
            $args['at'] = preg_replace($_rep, ' ', $args['at']);
            $time = strtotime($args['at']);
            if (!$time) {
                $_SESSION['error'] = 'Schedule time, '.$args['at'].', not understood. Try a different date/time format.';
                return false;
            }
            // Convert time based on timezone
            if ($args['timezone']) {
                // Update tz here
                $this->updateTz($args);
            }
            $send_time = $time - $_SESSION['tz'];
            // Passed?
            if ($send_time <= time()) {
                $_SESSION['error'] = 'Kindly select a time in the future.';
                return false;
            }

            $send_at = date('Y-m-d H:i', $send_time);
            $send_at_raw = date('Y-m-d H:i', $time);
        }

        // url
        $url = $args['url'];
        if (!$url) {
            $url = ($args['action'] == 'schedule') ? $this->urlfy($title, date('Y-m-d', $send_time)) :
                        $this->urlfy($title);
        }

        $yaml['published'] = $args['action'] == 'draft' ? 'false' : 'true';
        $yaml['title'] = $title;
        $yaml['layout'] = 'post';
        // tags
        $tags = $args['tags'];
        if ($tags) {
            $tags = preg_split("/[\s,]+/", $tags);
            $yaml['tags'] = '['.implode(', ', $tags).']';
        }
        // categories
        $categories = $args['categories'];
        if ($categories) {
            $categories = preg_split("/[\s,]+/", $categories);
            $yaml['categories'] = '['.implode(', ', $categories).']';
        }
        // Permalink
        $perma = $args['permalink'];
        if ($perma)
            $yaml['permalink'] = $perma;
        // commit
        $commit = $args['commit'];
        if (!$commit) {
            $commit = $args['sha'] ? 'Post update: ' : 'New post: ';
            $commit .= $title;
        }

        // body
        $body = "---\r\n";
        foreach ($yaml as $k => $v) {
            $body .= "$k: $v\r\n";
        }
        $body .= "---\r\n".$args['body'];

        $data = array(
                    'path' => '_posts/'.$url,
                    'message' => $commit,
                    'content' => base64_encode($body),
                );
        // Updating post
        if ($args['sha'])
            $data['sha'] = $args['sha'];

        $endpoint = $this->api_root.'/repos/'.$_SESSION['gh']['username'].'/'.$_SESSION['gh']['default_repo'].'/contents/_posts/'.$url;

        if ($args['action'] == 'schedule') {
            // Is there a schedule id? Update
            if ($args['schedule_id']) {
                $id = (int) $args['schedule_id'];
                $this->db->query("update schedule set endpoint='$endpoint', path='_posts/{$url}', title='$title', data='".base64_encode(json_encode($data))."', send_at='$send_at', send_at_raw='$send_at_raw', locked=0, date=now() where user='{$_SESSION['gh']['id']}' and id='$id'");

                $_SESSION['status'] = 'Post updated';
            }
            else {
                // Else new
                $this->db->query("insert into schedule (user, endpoint, path, title, data, send_at, send_at_raw, date) values ('{$_SESSION['gh']['id']}', '$endpoint', '_posts/{$url}', '$title', '".base64_encode(json_encode($data))."', '$send_at', '$send_at_raw', now())");

                $_SESSION['status'] = 'Your post has been scheduled for '.$send_at_raw.'.';
            }

            // delete draft
            $this->deleteDraft($args['save_id']);

            return $send_at_raw;
        }
        else {
            $response = $this->http($endpoint, $data, 'PUT');

            $this->initMxPanel();

            if ($response[0] == 200 || $response[0] == 201) {
                // Successful
                $_SESSION['status'] = isset($args['draft']) ? 'Your post has been saved as draft and committed.' : 'Your post has been committed. It should be live on your blog shortly.';

                // delete draft
                $this->deleteDraft($args['save_id']);

                // If schedule, delete
                if ($args['schedule_id'])
                    $this->deleteScheduled($args['schedule_id'], $_SESSION['gh']['id']);

                // Track successful post
                $this->mp->identify($_SESSION['gh']['id']);
                $this->mp->people->increment($_SESSION['gh']['id'], 'Posts', 1);
                $this->mp->track('Post');

                return true;
            }
        }

        // Track failed post

        // raw log the session
        $this->db->query("insert into log (user, log) values ('{$_SESSION['gh']['username']}', '".$this->db->real_escape_string(serialize($_SESSION))."')");

        $this->mp->identify($_SESSION['gh']['id']);
        $this->mp->track('Failed post');

        $json = json_decode($response[1]);
        $_SESSION['error'] = $json->message;
        return false;
    }

    // send due post
    function sendDue() {
        $q = "select s.id, s.user, p.email, p.token, s.endpoint, s.path, s.title, s.data, s.attempts from schedule s, profiles p where s.send_at <= now() and s.locked=0 and s.user=p.id";
        $r = $this->db->query($q);
        while(list($id, $user_id, $email, $token, $endpoint, $path, $title, $data, $attempts) = $r->fetch_array(MYSQLI_NUM)) {
            $this->db->query("update schedule set locked=1 where id='$id'");

            $_data = json_decode(base64_decode($data), true);
            $data = array(
                        'path' => $path,
                        'message' => $_data['message'],
                        'content' => $_data['content']
                    );

            $this->access_token = $token;
            $response = $this->http($endpoint, $data, 'PUT');
            $this->initMxPanel();

            if ($response[0] == 200 || $response[0] == 201) {
                // Successful
                // Mail
                $txt = 'Your scheduled post has been sent and should be available on your blog shortly.'."\r\n\r\n";
                $txt .= 'Love, Tinypress <https://tinypress.co>';
                $this->mail('Scheduled post sent', $email, $txt);

                // If schedule, delete
                $this->deleteScheduled($id, $user_id);

                // Track successful post
                $this->mp->identify($user_id);
                $this->mp->people->increment($user_id, 'Posts', 1);
                $this->mp->track('Post');
            }
            else {
                // If tried trice, give up
                if ($attempts > 1) {
                    $this->mp->identify($user_id);
                    $this->mp->track('Failed post');

                    $json = json_decode($response[1]);

                    $txt = 'Your scheduled post could not be sent at this time. Github sent an additional error:.'."\r\n\r\n";
                    $txt .= $json->message."\r\n\r\n";
                    $txt .= "Kindly check everything is rightly set and try again later.\r\n\r\n";
                    $txt .= 'Love, Tinypress <https://tinypress.co>';
                    $this->mail('Scheduled post failed', $email, $txt);
                }
                else {
                    // Update attempt and schedule + next 5 mins
                    $this->db->query("update schedule set locked=0, attempts=attempts+1, send_at=DATE_ADD(send_at, INTERVAL 5 MINUTE) where id='$id'");
                }
            }
        }
    }

    private function http($url, $data = null, $method = 'GET', $_headers = array()) {
        //return array(304, '');
        //echo $url;
        //print_r($data);

        $ch = curl_init();
        $headers = array(
                        'User-Agent' => 'Tinypress',
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    );
        $_headers = array_merge($headers, $_headers);
        unset($headers);
        //print_r($_headers);
        foreach ($_headers as $key => $value) {
            $headers[] = "$key: $value";
        }

        // Oauth 2 access token
        if ($this->access_token)
            $headers[] = 'Authorization: token '.$this->access_token;
        if ($this->eTags[$url])
            $headers[] = 'If-None-Match: '.$this->eTags[$url];

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        #curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        if ($data)
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        list($header, $body) = explode("\r\n\r\n", $response, 2);

        $headers = explode("\r\n", $header);
        //print_r($headers);
        foreach ($headers as $header) {
            list($key, $value) = preg_split('/:\s/', $header);
            if (strtolower($key) == 'etag') {
                $this->eTags[$url] = $value;
                $_SESSION['eTags'][$url] = $value;
                break;
            }
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        //echo $response;

        curl_close($ch);

        return array($status, $body);
    }

    function format($body) {
        preg_match('/^\-\-\-([^\-\-\-]*)\-\-\-(.*)/s', $body, $match);

        $yaml = $match[1];
        //$yaml = $body; // remove
        $body = $match[2];

        $_yaml = preg_split('/\n|\r/', $yaml, -1, PREG_SPLIT_NO_EMPTY);
        $list = array();

        foreach ($_yaml as $line) {
            // Comments
            if (preg_match('/^#/', $line))   continue;

            if ($listKey) {
                $line = trim($line);
                if (preg_match('/^\-\s*(.*)/', $line, $match)) {
                    $list[] = $match[1];
                }
                else {
                    $format[$listKey] = implode(', ', $list);
                    $list = array();
                    $listKey = false;
                }
            }

            list($k, $v) = preg_split('/:\s?/', $line, 2);
            $format[$k] = trim($v, "'\"");

            // List?
            if (!$v) {
                $listKey = $k;
            }
        }
        $format['body'] = trim($body);

        // Category, categeories
        if ($format['categories'])
            $format['categories'] = trim($format['categories'], '[]');
        else if ($format['category'])
            $format['categories'] = $format['category'];

        $format['tags'] = trim($format['tags'], '[]');

        // link, permalink
        //print_r($format);

        return $format;
    }

    // Theme
    function changeTheme($data) {

        // Validate id
        $id = (int) $data['template'];
        $username = $_SESSION['gh']['username'];

        $r = $this->db->query("select * from templates where id='$id'");
        if ($r->num_rows < 1) {
            $_SESSION['error'] = 'Invalid template selected.';
            return false;
        }
        $row = $r->fetch_array(MYSQLI_ASSOC);

        $workload = json_encode(array(
                        'token' => $this->access_token,
                        'email' => $_SESSION['gh']['email'],
                        'template_id' => $id,
                        'template_path' => $row['path'],
                        'repo' => $username.'.github.io',
                        'user_id' => $_SESSION['gh']['id'],
                        'username' => $username,
                        'post_data' => $data
                    ));

        $client= new GearmanClient();
        $client->addServer();
        $client->doHighBackground("change_theme", $workload);

        return true;
    }

    function themeWorker($data){
        unset($this->eTags);
        //echo '<pre>';
        // 1. Get sha
        $url = $this->api_root.'/repos/'.$data['username'].'/'.$data['repo'].'/commits?per_page=1';
        $body = $this->http($url);
        if ($body[0] != 200) {
            // Error here
            //echo "Last commit failed ".$body[1];return;
            $txt = 'There has been an error changing your theme. Please try again later'.".\r\n\r\n";
            $txt .= 'Love, Tinypress <https://tinypress.co>';
            $this->mail('Theme update error', $data['email'], $txt, null, 'love@tinypress.co');
            return false;
        }
        $json = json_decode($body[1], true);
        $tree_sha = $json[0]['commit']['tree']['sha'];
        $sha = $json[0]['sha']; // <-- add to email for roll back
        //print_r($json);
        //echo $tree_sha;

        // 2. Get tree
        $url = $this->api_root.'/repos/'.$data['username'].'/'.$data['repo'].'/git/trees/'.$tree_sha.'?recursive=1';
        $body = $this->http($url);
        $rtree = array();
        if ($body[0] == 200) {
            // Error here
            // Not the end of the world
            $json = json_decode($body[1], true);
            $rtree = $json['tree'];
        }

        //echo 'RTREE<br>------------<br>';
        //print_r($rtree);

        $tree = array();
        foreach ($rtree as $k => $path) {
            // ignore _site, build and _post
            $path_name = $path['path'];
            if (!preg_match('/^(build|_site|_posts)\//i', $path_name) && $path['type'] != 'tree') {
                $tree[$path_name] = $path;
            }
        }
        unset($rtree); // Free space
        unset($json); // Free space

        //echo 'TREE<br>------------<br>';
        //print_r($tree);

        // 3. Get content of template
        // Get config
        $r = $this->db->query("select name, description, def from template_config where template_id='{$data['template_id']}'");
        $body = "excerpt_separator: \"\"\r\npygments: true\r\nmarkdown: kramdown\r\nurl: http://".$data['repo'];
        while(list($name, $descr, $def) = $r->fetch_array(MYSQLI_NUM)) {
            $def = preg_replace($this->template_replace, $this->template_pattern, $def);
            $value = $data['post_data'][$name] ? $data['post_data'][$name] : $def;
            if ($name == 'description')
                $value = preg_replace('/[\r\n]/', '', nl2br($value));
            $body .= "\r\n{$name}: {$value}";
        }
        $commit = 'Theme change via tinypress.co';
        $files = array();
        $files['_config.yml'] = array(
                    'path' => '_config.yml',
                    'message' => $commit,
                    'content' => base64_encode($body),
                );
        // Get other contents
        $r = $this->db->query("select path from template_files where template_id='{$data['template_id']}'");
        while(list($path) = $r->fetch_array(MYSQLI_NUM)) {
            $source = '../templates/'.$data['template_path'].'/'.$path;
            $body = file_get_contents($source);
            $files[$path] = array(
                        'path' => $path,
                        'message' => $commit,
                        'content' => base64_encode($body),
                    );
        }

        // 4. Find difference and delete
        $diff = array_diff_key($tree, $files);
        foreach($diff as $v) {
            // Delete yml, html, md, xml
            if (preg_match('/\.(html|htm|yml|md|xml)$/i', $v['path'])) {
                //print_r($v);
                $this->http($this->api_root.'/repos/'.$data['username'].'/'.$data['repo'].'/contents/'.$v['path'], array(
                                        'path' => $v['path'],
                                        'sha' => $v['sha'],
                                        'message' => 'File deleted: '.$v['path']
                                    ), 'DELETE');
            }
        }

        // 5. Update files....atlast
        foreach ($files as $file) {
            // If in tree, get sha for update
            if ($tree[$file['path']])
                $file['sha'] = $tree[$file['path']]['sha'];
            // let's hope something bad doesnt happen in this loop
            $this->http($this->api_root.'/repos/'.$data['username'].'/'.$data['repo'].'/contents/'.$file['path'], $file, 'PUT');
            // throttle for .5 seconds
            time_nanosleep(0, 500000000);
        }

        // 6. Send notification email {
        $txt = 'Your blog theme update is complete. Changes to your blog should reflect in few minutes. Should you want to roll back, here is the commit sha before the theme update: '.$sha.".\r\n\r\n";
        $txt .= 'Hoping you like your new theme.'."\r\n\r\n";
        $txt .= 'Love, Tinypress <https://tinypress.co>';
        $this->mail('Theme update complete!', $data['email'], $txt, null, 'love@tinypress.co');

        // 7. Template adjustment hack
        // Create a post and delete it
        // Github may build before update completes
        // so force rebuild
        $yaml['published'] = 'true'; // Will false work?
        $yaml['title'] = 'Temp demo post';
        $yaml['layout'] = 'post';
        $body = "---\r\n";
        foreach ($yaml as $k => $v) {
            $body .= "$k: $v\r\n";
        }
        $body .= "---\r\n";
        $url = $this->urlfy($yaml['title']);
        $data = array(
                    'path' => '_posts/'.$url,
                    'message' => 'Magic :)',
                    'content' => base64_encode($body)
                    );
        $temp_url = $this->api_root.'/repos/'.$data['username'].'/'.$data['repo'].'/contents/'.$url;
        $response = $this->http($temp_url, $file, 'PUT');
        $data['sha'] = $response[1]['content']['sha'];
        unset($data['message']);
        $this->http($temp_url, $data, 'DELETE');


        $this->initMxPanel();
        $this->mp->identify($data['user_id']);
        $this->mp->track('Theme change');
    }

    // Blog
    function createBlog($data) {
        set_time_limit(0);

        // Validate id
        $id = (int) $data['template'];
        $username = $_SESSION['gh']['username'];

        $r = $this->db->query("select * from templates where id='$id'");
        if ($r->num_rows < 1) {
            $_SESSION['error'] = 'Invalid template selected.';
            return false;
        }
        $row = $r->fetch_array(MYSQLI_ASSOC);
        $template_path = $row['path'];

        // Create repo
        $repo = array(
            'name' => $username.'.github.io',
            'homepage' => 'http://'.$username.'.github.io'
        );
        $endpoint = $_SESSION['gh']['org_repo'] ? $this->api_root.'/orgs/'.$username.'/repos' : $this->api_root.'/user/repos';
        $response = $this->http($endpoint, $repo, 'POST');
        //print_r($response);

        if ($response[0] != 201) {
            $_SESSION['error'] = 'There has been an error creating your page repo. Please try again later.';
            return false;
        }//*/

        $_SESSION['gh']['default_repo'] = $username.'.github.io';

        // Get config
        $r = $this->db->query("select name, description, def from template_config where template_id='{$id}'");
        $body = "excerpt_separator: \"\"\r\npygments: true\r\nmarkdown: kramdown\r\nurl: http://".$_SESSION['gh']['default_repo'];
        while(list($name, $descr, $def) = $r->fetch_array(MYSQLI_NUM)) {
            $def = preg_replace($this->template_replace, $this->template_pattern, $def);
            $value = $data[$name] ? $data[$name] : $def;
            if ($name == 'description')
                $value = preg_replace('/[\r\n]/', '', nl2br($value));
            $body .= "\r\n{$name}: {$value}";
        }

        $commit = 'Blog init via tinypress.co';
        $files = array();

        $files[] = array(
                    'path' => '_config.yml',
                    'message' => $commit,
                    'content' => base64_encode($body),
                );

        // Get other contents
        $r = $this->db->query("select path from template_files where template_id='{$id}'");
        while(list($path) = $r->fetch_array(MYSQLI_NUM)) {
            $source = 'templates/'.$template_path.'/'.$path;
            $body = file_get_contents($source);
            $files[] = array(
                        'path' => $path,
                        'message' => $commit,
                        'content' => base64_encode($body),
                    );
        }

        // New post
        $body = "---\r\n";
        $body .= "published: true
title: Hello world
layout: post\r\n";
        $body .= "---\r\n";
        $body .= "This is a test post for my new blog. The blog is hosted on [Github Pages](http://pages.github.com/) which means the source is available at [github.com/{$username}/{$username}.github.io](http://github.com/{$username}/{$username}.github.io). Be nice. Give credit. Share, don't steal :)\r\n\r\nBy the way, this blog is powered by [tinypress.co](https://tinypress.co).";
        $files[] = array(
                    'path' => '_posts/'.date('Y-m-d').'-hello-world.markdown',
                    'message' => $commit,
                    'content' => base64_encode($body),
                );

        foreach ($files as $file) {
            $endpoint = $this->api_root.'/repos/'.$_SESSION['gh']['username'].'/'.$_SESSION['gh']['default_repo'].'/contents/'.$file['path'];
            // let's hope something bad doesnt happen in this loop
            $this->http($endpoint, $file, 'PUT');
            // throttle for .5 seconds http://stackoverflow.com/questions/19576601/github-api-issue-with-file-upload
            time_nanosleep(0, 500000000);
        }

        unset($_SESSION['new_blog']);
        //$_SESSION['token'] = $_SESSION['token_backup']; // <-- suspected cause of fail post
        $_SESSION['token'] = $this->access_token;
        $_SESSION['gh']['page_repos'] = array($_SESSION['gh']['default_repo']);

        $this->updateToken();

        $this->initMxPanel();
        $this->mp->identify($_SESSION['gh']['id']);
        $this->mp->track('New blog');

        return true;
    }

    // Templates

    function getTemplates() {
        $r = $this->db->query("select * from templates order by rand()");
        $templates = array();
        while($row = $r->fetch_array(MYSQLI_ASSOC)) {

            // Get config
            $r2 = $this->db->query("select name, description, def from template_config where template_id='{$row['id']}'");
            $config = array();
            while(list($name, $descr, $def) = $r2->fetch_array(MYSQLI_NUM)) {

                // Replace special words.
                $def = preg_replace($this->template_replace, $this->template_pattern, $def);

                $config[] = array('name' => $name, 'desc' => $descr, 'def' => htmlentities($def));
            }

            $row['config'] = $config;
            $templates[] = $row;
        }

        return $templates;
    }

    function logout() {

        // clear cache
        foreach ($_SESSION['eTags'] as $k => $v) {
            $this->redis->delete($k);
        }

        // destroy session
        $_SESSION = array();
        session_destroy();
        header('location:./');
        exit;
    }

	function urlfy($string, $date = null) {
		$string = strtolower(trim($string));
		// Kill non words
		$string = preg_replace('|[^a-z0-9\.\_]|', '-', $string);
		// Remove double occurence of -
		$string = preg_replace('|\-+|', '-', $string);
		// 20 chars max
		// $string = substr($string, 0, 20);
		// Kill before
		$string = preg_replace('|^\-|', '', $string);
		// Kill after
		$string = preg_replace('|\-$|', '', $string);

		return $date ? $date.'-'.$string.'.markdown' :
                    date('Y-m-d').'-'.$string.'.markdown';
	}

	function validate_email($email) {
		require 'lib/is_email.php';
		return is_email($email);
	}

    function getPostEmail() {
        $str = '123456789abcdefghijkmnpqrstuvwxyz';
        $str = str_shuffle($str);
        return substr($str, rand(0, 25), 8).'@post.tinypress.co';
    }

	function escape($input, $allowHTML = false) {
		$input = trim($input);

        if (get_magic_quotes_gpc ())
            $input = stripslashes($input);

        // Normalize newlines
        $input = str_replace("\r\n", "\n", $input);
        $input = preg_replace("/\n\n+/", "\n\n", $input);

        if ($allowHTML) {
            // Escape HTML
            $input = htmlentities($input, ENT_QUOTES, 'UTF-8');
            $input = nl2br($input);
            # p
            $input = preg_replace('!&lt;p&gt;(.*?)&lt;/p&gt;(<br */*>)?!im', '<p>$1</p>', $input);

            # em
            $input = preg_replace('!&lt;em&gt;(.*?)&lt;/em&gt;!im', '<em>$1</em>', $input);

            # s
            $input = preg_replace('!&lt;s&gt;(.*?)&lt;/s&gt;!im', '<s>$1</s>', $input);

            # i
            $input = preg_replace('!&lt;i&gt;(.*?)&lt;/i&gt;!im', '<i>$1</i>', $input);

            # b
            $input = preg_replace('!&lt;b&gt;(.*?)&lt;/b&gt;!im', '<b>$1</b>', $input);

            # u
            $input = preg_replace('!&lt;u&gt;(.*?)&lt;/u&gt;!im', '<u>$1</u>', $input);

            # a
            $input = preg_replace("!&lt;a +href=&quot;((?:ht|f)tps?://.*?)&quot;(?: +title=&quot;(.*?)&quot;)?(?: +rel=&quot;(.*?)&quot;)? *&gt;(.*?)&lt;/a&gt;!im", '<a href="$1">$4</a>', $input);
            $input = preg_replace("'(?<!=\")(http|ftp)://([\w\+\-\@\=\?\.\%\/\:\&\;~\|]+)(\.)?'im", "<a href=\"\\1://\\2\">\\1://\\2</a>", $input);
        }

        return $this->db->real_escape_string($input);
    }

    function gravatar( $email, $s = 80, $d = 'mm', $r = 'g', $img = false, $atts = array() ) {
        $url = 'http://www.gravatar.com/avatar/';
        $url .= md5( strtolower( trim( $email ) ) );
        $url .= "?s=$s&d=$d&r=$r";
        if ( $img ) {
            $url = '<img src="' . $url . '"';
            foreach ( $atts as $key => $val )
                $url .= ' ' . $key . '="' . $val . '"';
            $url .= ' />';
        }
        return $url;
    }

    private function initMail() {
		if ($this->mailer)
			return;

		include_once dirname(__FILE__).'/lib/swift/swift_required.php';

		// Create the Transport
		$transport = Swift_SmtpTransport::newInstance('smtp.mailgun.org', 587, 'tls')
		  ->setUsername('postmaster@post.tinypress.co')
		  ->setPassword('')
		  ;

		// Create the Mailer using your created Transport
		$this->mailer = Swift_Mailer::newInstance($transport);
	}

	function mail($subject, $to, $txt, $html = null, $sender_email = 'mail-to-post@post.tinypress.co') {
		$this->initMail();

		// Create a message
		$message = Swift_Message::newInstance($subject)
		  ->setFrom(array($sender_email => 'Tinypress'))
		  ->setTo($to)
		  ->setBody($txt, 'text/plain')
		  ;

		 if ($html)
			$message->addPart($html, 'text/html');

		// Send the message
		$result = $this->mailer->send($message);
	}

}
?>