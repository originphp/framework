<?php
declare(strict_types = 1);
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

namespace Origin\Http\Controller\Component;

use ReflectionClass;
use ReflectionMethod;
use Origin\Http\Router;
use Origin\Model\Entity;
use Origin\Http\Response;
use Origin\Security\Security;
use Origin\Exception\Exception;
use Origin\Model\ModelRegistry;
use Origin\Http\Controller\Controller;
use Origin\Http\Exception\ForbiddenException;
use Origin\Model\Exception\MissingModelException;

/**
 * Authenticate, 'Form' and/Or 'Http' .
 * Login Action - Login Screen
 * Scope - Additional fields e.g active = 1.
 */
/**
 * @property \Origin\Http\Controller\Component\SessionComponent $Session
 * @property \Origin\Http\Controller\Component\FlashComponent $Flash
 */
class AuthComponent extends Component
{
    /**
     * Default config.
     *
     * @var array
     */
    protected $defaultConfig = [
        'authenticate' => ['Form'], // Form, Http, and API
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
        'fields' => ['username' => 'email', 'password' => 'password','api_token' => 'api_token'],
        'scope' => [], // Extra conditions for db . e.g User.active=1;
        'unauthorizedRedirect' => true, // If false throw ForbiddenException
        'authError' => 'You are not authorized to access that location.',
    ];

    /**
     * Allowed actions.
     *
     * @var array
     */
    protected $allowedActions = [];

    /**
     * Holds the data from API auth
     *
     * @var \Origin\Model\Entity
     */
    private $user = null;

    public function initialize(array $config) : void
    {
        if (! ModelRegistry::get($this->config['model'])) {
            throw new MissingModelException($this->config['model']);
        }

        $this->loadComponent('Flash');
        $this->loadComponent('Session');
    }

    /**
     * This called after the controller startUp but before the action.
     */
    public function startup()
    {
        $action = $this->request()->params('action');

        if ($this->isPrivateOrProtected($action)) {
            return null;
        }
       
        if ($this->isAllowed($action)) {
            return null;
        }
     
        if ($this->isLoginPage()) {
            return null;
        }

        if ($this->isAuthorized($this->getUser())) {
            return null;
        }

        return $this->unauthorize();
    }

    /**
     * Allow action or actions to not need login
     *
     * @param string|array $actions
     * @return void
     */
    public function allow($actions) : void
    {
        $this->allowedActions = array_merge($this->allowedActions, (array) $actions);
    }

    /**
     * An additional layer
     *
     * @param array $user
     * @return boolean
     */
    protected function isAuthorized(array $user = null) : bool
    {
        if ($user === null) {
            return false;
        }
        if (in_array('Controller', $this->config['authenticate'])) {
            $controller = $this->controller();
            if (! method_exists($controller, 'isAuthorized')) {
                throw new Exception(sprintf('%s does have an isAuthorized() method.', get_class($controller)));
            }

            return $controller->isAuthorized($user);
        }

        return true;
    }

    /**
     * Hash password and use with verify password.
     *
     * @param string $password
     * @return string $hashedPassword
     */
    public function hashPassword(string $password) : string
    {
        return Security::hashPassword($password); // 255 characters is pref for future proofing
    }

    /**
     * Gets the user each from session, from by authenticating
     *
     * @return array|null
     */
    public function getUser() : ?array
    {
        if ($this->user()) {
            return $this->user();
        }
     
        $user = $this->identify();
       
        if ($user) {
            return $user->toArray();
        }

        return null;
    }

    /**
     * This will try to identify the user. Check if there are credentials
     * based upon auth methods (form, http).
     *
     * @return Entity|bool $user false
     */
    public function identify()
    {
        /**
         * If API authentication method is enabled, then must throw exception if it can't log in
         */
        if (in_array('Api', $this->config['authenticate'])) {
            $token = $this->request()->query($this->config['fields']['api_token']);
            if ($token) {
                $model = ModelRegistry::get($this->config['model']);
                
                $conditions = [$this->config['fields']['api_token'] => $token];
                if (! empty($this->config['scope']) and is_array($this->config['scope'])) {
                    $conditions = array_merge($conditions, $this->config['scope']);
                }
          
                $user = $model->find('first', ['conditions' => $conditions]);
                if ($user) {
                    return $this->user = $user;
                }
            }
            $this->request()->type('json'); // Force all api usage as json
            throw new ForbiddenException($this->config['authError']);
        }
        $credentials = $this->getCredentials();
        if ($credentials) {
            return $this->loadUser($credentials['username'], $credentials['password']);
        }

        return false;
    }

    /**
     * This logs the user the in with any data provided it
     * does not check credientials. User data is converted
     * into an array to be stored in the session.
     *
     * @param Entity $user
     * @return void
     */
    public function login(Entity $user) : void
    {
        $this->Session = $this->Session;
        $this->Session->write('Auth.User', $user->toArray());
    }

    /**
     * Gets the Redirect URL using either the config loginRedirect or the
     * Auth.redirect (Default).
     *
     * @return array|string
     */
    public function redirectUrl()
    {
        if ($redirectUrl = $this->Session->read('Auth.redirect')) {
            $this->Session->delete('Auth.redirect');

            return $redirectUrl;
        }

        return $this->config['loginRedirect'];
    }

    /**
     * Logsout user and returns URL to redirect too. Deafaults to loginAction
     * but will return logoutRedirect as well.
     * - Deletes the Auth Info in the Session. Previously used session destroy but this will
     * render flash messages useless.
     *
     * @return string url where to redirect too
     */
    public function logout() : string
    {
        $this->Session->delete('Auth');

        $logoutRedirect = $this->config['loginAction'];
        if ($this->config['logoutRedirect']) {
            $logoutRedirect = $this->config['logoutRedirect'];
        }

        return Router::url($logoutRedirect);
    }

    /**
     * Gets the logged in User info. User info is stored
     * as an array in the Session.
     *
     * @param string $property to get of the logged in user
     * @return mixed
     */
    public function user(string $property = null)
    {
        $user = null;
        # API authentication should not check data in Session
        if (in_array('Form', $this->config['authenticate']) or in_array('Http', $this->config['authenticate'])) {
            $user = $this->Session->read('Auth.User');
        }
        /**
         * Load static user data if its available (stateless). This takes priority and should overwrite
         * from session.
         */
     
        if (in_array('Api', $this->config['authenticate']) and $this->user) {
            $user = $this->user->toArray();
        }
      
        if ($property === null) {
            return $user;
        }
   
        return $user[$property] ?? null;
    }
    
    /**
     * Gets the username and password from request
     *
     * @return bool|array ['username'=>x,'password'=>x];
     */
    protected function getCredentials()
    {
        $username = $password = null;

        if (in_array('Form', $this->config['authenticate'])) {
            $fields = $this->config['fields'];
            $username = (string) $this->request()->data($fields['username']);
            $password = (string) $this->request()->data($fields['password']);
            if ($username and $password) {
                return ['username' => $username, 'password' => $password];
            }
        }

        if (in_array('Http', $this->config['authenticate'])) {
            $username = (string) $this->request()->env('PHP_AUTH_USER');
            $password = (string) $this->request()->env('PHP_AUTH_PW');
            if ($username and $password) {
                return ['username' => $username, 'password' => $password];
            }
        }

        return false;
    }

    /**
     * Checks if the action is allowed
     *
     * @param string $action
     * @return boolean
     */
    protected function isAllowed(string $action) : bool
    {
        return (in_array($action, $this->allowedActions) or in_array('*', $this->allowedActions));
    }

    /**
     * Checks if the current action is the login page
     *
     * @return boolean
     */
    protected function isLoginPage() : bool
    {
        $loginUrl = Router::url($this->config['loginAction']);
        $params = Router::parse($loginUrl);
        if ($params['controller'] != $this->request()->params('controller')) {
            return false;
        }
        if ($params['action'] === $this->request()->params('action')) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the current user is considered logged in
     *
     * @return boolean
     */
    public function isLoggedIn() : bool
    {
        return $this->user() !== null;
    }

    protected function isPrivateOrProtected(string $action) : bool
    {
        $controller = new ReflectionClass(Controller::class);
        if ($controller->hasMethod($action)) {
            return false;
        }

        if (! method_exists($this->controller(), $action)) {
            return false;
        }

        return ! (new ReflectionMethod($this->controller(), $action))->isPublic();
    }

    /**
     * Loads the user from the database using the username and password
     * and config scope.
     *
     * @param string $username
     * @param string $password
     * @return bool|Entity user
     */
    protected function loadUser(string $username, string $password)
    {
        $model = ModelRegistry::get($this->config['model']);
 
        $conditions = [
            $this->config['fields']['username'] => $username,
        ];

        if (! empty($this->config['scope']) and is_array($this->config['scope'])) {
            $conditions = array_merge($conditions, $this->config['scope']);
        }

        $result = $model->find('first', ['conditions' => $conditions]);

        if (empty($result)) {
            return false;
        }
        if (Security::verifyPassword($password, $result->get($this->config['fields']['password']))) {
            return $result;
        }

        return false;
    }

    /**
     * Starts the unauthorize process.
     *
     * @throws \Origin\Http\Exception\ForbiddenException
     * @return \Origin\Http\Response
     */
    protected function unauthorize() : Response
    {
        if ($this->config['unauthorizedRedirect']) {
            $this->Flash->error($this->config['authError']);
            $this->Session->write('Auth.redirect', $this->request()->path(true));

            return $this->controller()->redirect(Router::url($this->config['loginAction']));
        }
       
        throw new ForbiddenException($this->config['authError']);
    }
}
