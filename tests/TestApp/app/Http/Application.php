<?php
/**
 * Application
 * Configure middleware here if not dont touch
 */
namespace App\Http;

use Origin\Http\BaseApplication;

class Application extends BaseApplication
{
    /**
     * Setup middlewares here
     *
     * Example:
     *
     * $this->loadMiddleware('RequestModifier');
     * $this->loadMiddleware('MyPlugin.RequestModifier')
     */
    protected function initialize() : void
    {
    }
}
