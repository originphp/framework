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

        $this->loadComponent('Auth');
        $this->loadComponent('Flash');
        $this->loadComponent('Session');

        $this->loadHelper('Html');
        $this->loadHelper('Form');
        $this->loadHelper('Flash');
        $this->loadHelper('Number');
        $this->loadHelper('Date');

        /*
         * Start I18n. This will autodetect locale and settings if you do not pass array
         * with settings. e.g ['locale' => 'en_GB','language'=>'en','timezone'=>'Europe/London']
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
     * This is called after the controller action is executed, and view has been generated
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
