<?php
/**
 * Application
 * Configure middleware here if not dont touch
 */
namespace App;

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
    public function initialize()
    {
    }
}
