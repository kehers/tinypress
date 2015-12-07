<?php
switch($pages[0]) {
    case '3rd':
        if ($pages[1] == 'mail-to-post') {            
            $app->mailToPost($_POST);
        }
        break;
	case 'login':    
        // If new blog authentication
        if ($_GET['code'] && $_SESSION['new_blog']) {
            header('location:new-blog?code='.$_GET['code']);
            exit;
        }
        
        // Confirm login status
        if ($_SESSION['gh']['username']) {
            header('location:./');
            exit;
        }
        
        if ($_GET['code']) {
            $token = $app->getAccessToken($_GET['code']);
            if (!$token) {
                $_SESSION['error'] = 'There has been an authentication error. Please try again later.';
                header('location:./');
                exit;
            }
            
            $_SESSION['token'] = $token;
            
            // Get user details here
            $app->getUser();
            
            if ($_SESSION['gh']['orgs'])
                header('location:select-acc');
            else
                header('location:./');
            
            exit;
        }
        else {
            // redirect to authorize url
            header('location:'.AUTH_URL.'?client_id='.CLIENT_ID.'&scope=public_repo');
            exit;
        }
		break;
    case 'api':
        if (!$_SESSION['gh']['id']) exit;
        if ($pages[1] == 'post') {
            if ($app->post($_POST))
                echo json_encode(array('done' => true));
            else
                echo json_encode(array('error' => $_SESSION['error']));
        }
        else if ($pages[1] == 'schedule') {
            $date = $app->post($_POST);
            if ($date)
                echo json_encode(array(
                                    'done' => true,
                                    'schedule' => true,
                                    'date' => $date
                                ));
            else
                echo json_encode(array('error' => $_SESSION['error']));
        }
        else if ($pages[1] == 'drafts') {
            if ($pages[2] == 'delete') {
                $data = json_decode(file_get_contents("php://input"), true);
                $app->deleteDraft($data['id']);
            }
            else {
                $r = $app->saveDraft($_POST);
                if ($r) {
                    $r['done'] = true;
                    $r['date'] = date('M d, Y');
                    
                    echo json_encode($r);
                }
            }
        }
        else if ($pages[1] == 'preview') {
            echo json_encode(array('preview' => true, 'content' => $app->preview($_POST['body'])));
        }
        else if ($pages[1] == 'email') {
            if ($app->updateEmail($_POST))
                echo json_encode(array('done' => true));
            else
                echo json_encode(array('error' => $_SESSION['error']));
        }
        else if ($pages[1] == 'timezone') {
            if ($app->updateTZ($_POST))
                echo json_encode(array('done' => true));
            else
                echo json_encode(array('error' => $_SESSION['error']));
        }
        else if ($pages[1] == 'cname') {
            if ($app->updateCname($_POST))
                echo json_encode(array('done' => true));
            else
                echo json_encode(array('error' => $_SESSION['error']));
        }
        else if ($pages[1] == 'cancel-renewal') {
            $app->cancelRenewal($_POST);
        }
        else if ($pages[1] == 'upgrade') {
            $exp_date = $app->upgrade($_POST);
            if ($exp_date)
                echo json_encode(array('exp_date' => $exp_date));
            else
                echo json_encode(array('error' => $_SESSION['error']));
        }
        else if ($pages[1] == 'get') {
            if ($_GET['key']) {
                $body = $app->getPost($_GET['key'], $_GET['scheduled']);
                if ($body) {
                    echo json_encode($body);
                }
                else {
                    echo json_encode(array('error' => 'Error retrieving post. Try again later.'));
                }
            }
            else {
                $body = $app->getPosts($_GET['page'], $_GET['start']);
                //if ($body) {
                    echo json_encode($body);
                //}
            }

        }
        else if ($pages[1] == 'delete') {
            $data = json_decode(file_get_contents("php://input"), true);
            $app->delete($data['title'], $data['path'], $data['sha'], $data['later']);
        }
        break;
    case 'preview':
        $text = $_POST['markdown'];
        echo $app->preview($text);
        break;
    case 'new':
    case 'post':
    case 'edit':
        header('location:/');
        exit;
        break;
	case 'done':
        if (!$_SESSION['status']) {
            header('location:./');
            exit;
        }
        include_once 'views/done.php';
		break;
    case 'error':
        include_once 'views/error.php';
        exit;
    case 'about':
        include_once 'views/about.php';
        break;
	case 'privacy':
        include_once 'views/privacy.php';
		break;
	case 'terms':
        include_once 'views/terms.php';
		break;
	case 'changelog':
        include_once 'views/changelog.php';
		break;
	case 'logout':
        $app->logout();
        
		break;
	case 'select-acc':
        $app->checkLogged();
        
        // You have no biz here
        if (!$_SESSION['gh']['orgs']) {
            header('location:./');
            exit;
        }
        
        if ($_GET['sel']) {
            $app->setOrg($_GET['sel']);
            header('location:./');
            exit;
        }
        
        include_once 'views/select_org.php';
        break;
	case 'new-blog':
        $app->checkLogged();
        
        if ($_SESSION['gh']['page_repos']) {
            header('location:/');
            exit;
        }
        
        if ($_GET['code'] && $_SESSION['new_blog']) {
            $app->resetAccessToken();
            $token = $app->getAccessToken($_GET['code']);
            if (!$token) {
                $_SESSION['error'] = 'There has been an authentication error. Please try again later.';
            }
            else if ($app->createBlog($_SESSION['new_blog'])) {
                $_SESSION['status'] = 'Your blog is being set up. <br />It will be available within the next few minutes.';
                header('location:/');
                exit;                
            }
        }
        
        if ($_POST['template']) {
            $_SESSION['new_blog'] = $_POST;
            header('location:'.AUTH_URL.'?client_id='.CLIENT_ID.'&scope=repo');
        }
        
        include_once 'views/new_blog.php';
        
		break;
	case 'change-theme':
        $app->checkLogged();
        
        if ($_POST) {
            if ($app->changeTheme($_POST)) {
                $_SESSION['status'] = 'Your theme is being updated. You will be notified once complete.';
                header('location:/');
                exit;                
            }
        }
        
        include_once 'views/change_theme.php';
        
		break;
    default:
        $array = array('.a-drafts' => 'saves',
                        '.a-forms' => 'forms',
                        '.settings' => 'settings'
                    );
        if (empty($pages[0])) {
            // Index
            if (!$_SESSION['gh']['username']){
                include_once 'views/index.php';
                exit;
            }

            if (!$_SESSION['gh']['page_repos']) {
                // Get page repos here
                $app->getPagesRepo();
                
                // if nothing, redirect out            
                if (!$_SESSION['gh']['page_repos']) {
                    header('location:new-blog');
                    exit;
                }
                
                // Set repo here
                $_SESSION['gh']['default_repo'] = $_SESSION['gh']['page_repos'][0];
            }

			include_once 'views/posts.php';
        }
        else if (in_array($pages[0], $array)) {
            $_SESSION['page'] = array_search($pages[0], $array);
            header('location:/');
            exit;
        }
        else {
            header("HTTP/1.0 404 Not Found");
            include_once 'views/404.php';
        }
    break;
}
?>