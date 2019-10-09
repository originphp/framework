<?php
namespace App\Http\Controller;

use Origin\Http\Controller\Controller;

class ApplicationController extends Controller
{
    /**
     * This is called immediately after construct, so you don't have
     * to overload it. Load and configure components, helpers etc.
     */
    protected function initialize() : void
    {
     
    }

    /**
     * This is called before the controller action is executed.
     */
    protected function startup() : void
    {
    }

    /**
     * This is called after the controller action is executed.
     */
    protected function shutdown() : void
    {
    }
}
