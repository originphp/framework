<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Simple debugger whilst developing the framework.
 */

namespace Debug;

use Origin\Model\ConnectionManager;
use Origin\Core\Configure;

class DebugBar
{
    /**
     * Renders the DebugBar.
     *
     * @return string output;
     */
    public function render()
    {
        if (!Configure::read('debug')) {
            return null;
        }

        /**
         * Don't Load in CLI (e.g. unit tests)
         * @todo think how this should work
         */
        if (php_sapi_name() === 'cli') {
            return;
        }

        global $Dispatcher;

       
        $controller = $Dispatcher->controller();
        $request = $controller->request;

        $log = [];
        if (ConnectionManager::has('default')) {
            $connection = ConnectionManager::get('default');
            $log = $connection->log();
        }

        $debugVars = array(
      'debug_sql' => $log,
      'debug_request' => array(
        'params' => $request->params(),
        'query' => $request->query(),
        'data' => $request->data(),
        'cookie' => $_COOKIE,
      ),
      'debug_vars' => array(
        'variables' => $controller->viewVars,
        'memory' => mbkb(memory_get_usage(false)),
        'took' => (microtime(true) - START_TIME).' ms',
      ),
      'debug_session' => $_SESSION,
      );

        extract($debugVars);
        include 'view.ctp';
    }
}
/**
 * Temporary until other libs done.
 */
function mbkb($bytes)
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2).' mb';
    }

    return number_format($bytes / 1024, 2).' kb';
}
