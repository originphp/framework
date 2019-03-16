<?php
namespace App\Controller;

use Origin\Controller\Controller;
use Origin\Core\I18n;

class AppController extends Controller
{
    /**
     * This is called immediately after construct, so you don't have
     * to overload it. Load and configure components, helpers etc.
     */
    public function initialize()
    {
        parent::initialize();

        /**
         * Core components to be loaded by default
         */
        $this->loadComponent('Session');
        $this->loadComponent('Cookie');
        $this->loadComponent('Flash');

        $this->loadComponent('Auth'); // For Bookmarks

        /**
         * Load Core Helpers (These are lazy loaded)
         */
        $this->loadHelper('Session');
        $this->loadHelper('Cookie');
        $this->loadHelper('Flash');
        $this->loadHelper('Html');
        $this->loadHelper('Form');
        $this->loadHelper('Number');
        $this->loadHelper('Date');

        /*
         * I18n (Numbers,Dates, Translation etc)
         *
         * Autodetect locale and language from browser:
         *
         * I18n::initialize();
         *
         * To manually set:
         *
         * I18n::initialize(['locale' => 'en_GB','language'=>'en','timezone'=>'Europe/London']);
         *
         * Set for a logged in user
         *
         * if($this->Auth->isLoggedIn()){
         *   I18n::initialize(
         *      'locale' => $this->Auth->user('locale'),
         *      'language' => $this->Auth->user('language'),
         *      'timezone' => $this->Auth->user('timezone'),
         *      );
         * }
         */
        I18n::initialize();
    }

    /**
     * This is called before the controller action is executed but after initialize.
     */
    public function beforeFilter()
    {
    }

    /**
     * This is called after the after the action has been run before the view has
     * been rendered but beore the after filter.
     */
    public function beforeRender()
    {
    }

    /**
     * This is called after the controller action is executed, and view has been rendered
     * but before it has been sent to the client.
     */
    public function afterFilter()
    {
    }

    /**
    * Callback just prior to redirecting
    */
    public function beforeRedirect()
    {
    }
}
