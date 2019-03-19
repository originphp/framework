<?php
namespace App\Controller;

use Origin\Controller\Controller;

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
         * Load Core Helpers (Helpers will be lazy loaded)
         */
        $this->loadHelper('Session');
        $this->loadHelper('Cookie');
        $this->loadHelper('Flash');
        $this->loadHelper('Html');
        $this->loadHelper('Form');
        $this->loadHelper('Number');
        $this->loadHelper('Date');
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
