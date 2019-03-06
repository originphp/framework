<?php

namespace App\Controller;

use Origin\Controller\Controller;
use Origin\Core\I18n;
use Origin\View\View;

class AppController extends Controller
{
    /**
     * This is called immediately after construct, so you don't have
     * to overload it. Load and configure components, helpers etc.
     */
    public function initialize()
    {
        parent::initialize();
        $this->loadComponents(['Flash','Cookie']);
        $this->loadHelpers(['Html', 'Form', 'Flash', 'Number', 'Date']);

        /*
         * Start I18n. This will autodetect locale and settings if you do not pass array
         * with settings. e.g ['locale' => 'en_GB','language'=>'en','timezone'=>'Europe/London']
         */
        I18n::initialize();
    }

    /**
     * This is called before the controller action is executed.
     */
    public function startup()
    {
    }

    /**
     * This is called after the controller action is executed.
     */
    public function shutdown()
    {
    }


    /**
     * Overide default viewPath is not affected by namespace changes.
     *
     */
    protected function createView()
    {
        $view = new View($this);
        // $view->viewPath(ORIGIN  . DS . 'tests' . DS . 'TestApp' . DS . 'src' . DS . 'View');
        return $view;
    }
}
