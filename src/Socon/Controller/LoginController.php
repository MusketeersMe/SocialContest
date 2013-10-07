<?php
namespace Socon\Controller;

use Phlyty\App;
use Socon\Controller;

/**
 * Class LoginController
 *
 * Allow a user to login and logout.
 *
 * @package Socon\Controller
 */
class LoginController extends Controller
{
    /**
     * loginAction
     *
     * Display login form, validate password.
     *
     * @param App $app
     */
    public function loginAction(App $app) 
    {
        if ($password = $app->request()->getPost('password')) {            
            if (password_verify($password, $this->config->admin_password)) {
                session_start();
                $_SESSION['auth'] = 1;
                session_commit();
                $app->redirect('/admin');
            }
        }
        // output HTML
        $this->view->page = $this->config->page;
        $app->render('login.phtml', $this->view);
    }

    /**
     * loguotAction
     *
     * Stop the user's session.
     *
     * @param App $app
     */
    public function logoutAction(App $app)
    {
        if (isset($_SESSION)) {
            session_destroy();
        }
        // delete the cookie
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 7200, $params['path'], $params['domain'],
            $params['secure'], isset($params['httponly']));

        // go home
        $app->redirect('/');
    }

}