<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright     Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Controller\Component;

use Origin\Core\Router;
use Origin\Core\Session;
use Origin\Model\ModelRegistry;
use Origin\Model\Exception\MissingModelException;
use Origin\Model\Entity;

/**
 * Authenticate, 'Form' and/Or 'Http' .
 * Login Action - Login Screen
 * Scope - Additional fields e.g active = 1.
 */
class AuthComponent extends Component
{
    /**
     * Default config.
     *
     * @var array
     */
    public $defaultConfig = [
      'authenticate' => ['Form'], // Form and Http supported
      'loginAction' => [
        'controller' => 'Users',
        'action' => 'login',
        'plugin' => null,
      ],
      'loginRedirect' => [
        'controller' => 'Users',
        'action' => 'index',
        'plugin' => null,
      ],
      'logoutRedirect' => [
        'controller' => 'Users',
        'action' => 'login',
        'plugin' => null,
      ],
      'model' => 'User',
      'fields' => ['username' => 'email', 'password' => 'password'],
      'scope' => [], // Extra conditions for db . e.g User.active=1;
      'unauthorizedRedirect' => true, // If false no redirect just exception e.g cli stuff
      'authError' => 'You are not authorized to access that location.',
    ];

    /**
     * Allowed actions.
     *
     * @var array
     */
    protected $allowedActions = [];

    public function initialize(array $config)
    {
        $this->loadComponent('Flash');
    }

    /**
     * This called after the controller startUp but before the action.
     */
    public function startup()
    {
        $this->checkAuthenticate();
    }

    /**
     * Allow an array of actions.
     *
     * @param array $actions
     */
    public function allow(array $actions)
    {
        $this->allowedActions = array_merge($this->allowedActions, $actions);
    }

    /**
     * Hash password and use with verify password.
     *
     * @param string $password
     *
     * @return string $hashedPassword
     */
    public function hashPassword(string $password)
    {
        return password_hash($password, PASSWORD_DEFAULT); // 255 characters is pref for future proofing
    }

    /**
     * This will try to identify the user. Check if there are credentials
     * based upon auth methods (form, http).
     *
     * @return Entity $user
     */
    public function identify()
    {
        $credentials = $this->getCredentials();

        if ($credentials) {
            return $this->loadUser($credentials['username'], $credentials['password']);
        }

        return null;
    }

    /**
     * This logs the user the in.
     *
     * @param Entity $user
     */
    public function setUser(Entity $user)
    {
        Session::write('Auth.User', $user);
    }

    /**
     * Gets the Redirect URL using either the config loginRedirect or the
     * Auth.redirect (Default).
     *
     * @return string url to redirect too
     */
    public function redirectUrl()
    {
        $redirectUrl = $this->config['loginRedirect'];
        if (Session::check('Auth.redirect')) {
            $redirectUrl = Session::read('Auth.redirect');
            Session::delete('Auth.redirect');
        }

        return $redirectUrl;
    }

    /**
     * Logsout user and returns URL to redirect too. Deafaults to loginAction
     * but will return logoutRedirect as well.
     * - Deletes the Auth Info in the Session. Previously used session destroy but this will
     * render flash messages useless.
     *
     * @return string url where to redirect too
     */
    public function logout()
    {
        Session::delete('Auth');

        $logoutRedirect = $this->config['loginAction'];
        if ($this->config['logoutRedirect']) {
            $logoutRedirect = $this->config['logoutRedirect'];
        }

        return Router::url($logoutRedirect);
    }

    /**
     * Gets the logged in User info.
     *
     * @param string $property to get of the logged in user
     */
    public function user(string $property = null)
    {
        $user = Session::read('Auth.User');
        if ($user === null) {
            return null;
        }
        if ($property === null) {
            return $user;
        }

        return $user->get($property);
    }

    /**
     * Verifies a password against a hash. use with Auth->hashPassword().
     *
     * @param string $password
     * @param string $hash
     *
     * @return bool true or false
     */
    public function verifyPassword(string $password, string $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Runs through checklist for auth.
     */
    protected function checkAuthenticate()
    {
        $action = $this->request->params['action'];

        if ($this->isLoggedIn()) {
            return null;
        }

        if ($this->isAllowed($action)) {
            return null;
        }

        if ($this->isPrivateOrProtected($action)) {
            return null;
        }

        if ($this->isLoginPage()) {
            return null;
        }

        $this->unauthorize();
    }

    /**
     * Gets the username and password from request
     * This can be form or http request such as using curl.
     *
     * @return array ['username'=>x,'password'=>x];
     */
    protected function getCredentials()
    {
        $username = $password = null;

        if (in_array('Form', $this->config['authenticate'])) {
            $fields = $this->config['fields'];
            if (isset($this->request->data[$fields['username']])) {
                $username = $this->request->data[$fields['username']];
            }
            if (isset($this->request->data[$fields['password']])) {
                $password = $this->request->data[$fields['password']];
            }
            if ($username and $password) {
                return ['username' => $username, 'password' => $password];
            }
        }

        if (in_array('Http', $this->config['authenticate'])) {
            $username = $this->request->env('PHP_AUTH_USER');
            $password = $this->request->env('PHP_AUTH_PW');

            if ($username and $password) {
                return ['username' => $username, 'password' => $password];
            }
        }

        return false;
    }

    protected function isAllowed(string $action)
    {
        if (in_array($action, $this->allowedActions) or in_array('*', $this->allowedActions)) {
            return true;
        }

        return false;
    }

    protected function isLoginPage()
    {
        $loginUrl = Router::url($this->config['loginAction']);
        $params = Router::parse($loginUrl);
        if ($params['controller'] != $this->request->params['controller']) {
            return false;
        }
        if ($params['action'] === $this->request->params['action']) {
            return true;
        }

        return false;
    }

    protected function isLoggedIn()
    {
        return Session::check('Auth.User');
    }

    protected function isPrivateOrProtected(string $action)
    {
        return !$this->controller()->isAccessible($action);
    }

    /**
     * Loads the user from the database using the username and password
     * and config scope.
     *
     * @param string $username
     * @param string $password
     *
     * @return Entity user
     */
    protected function loadUser(string $username, string $password)
    {
        $model = ModelRegistry::get($this->config['model']);
        if (!$model) {
            throw new MissingModelException($model);
        }
        $conditions = [
          $this->config['fields']['username'] => $username,
        ];

        if (!empty($this->config['scope']) and is_array($this->scope)) {
            $conditions = array_merge($conditions, $this->scope);
        }

        $result = $model->find('first', ['conditions' => $conditions]);

        if (empty($result)) {
            return false;
        }
        if ($this->verifyPassword($password, $result->get($this->config['fields']['password']))) {
            return $result;
        }

        return false;
    }

    /**
     * Starts the unauthorize process.
     *
     * @throws ForibddenException
     */
    protected function unauthorize()
    {
        $controller = $this->controller();

        if ($this->config['unauthorizedRedirect']) {
            $this->Flash->error($this->config['authError']);
            Session::write('Auth.redirect', $this->request->url);

            return $controller->redirect(Router::url($this->config['loginAction']));
        }
        throw new ForibddenException($this->config['authError']);
    }
}
