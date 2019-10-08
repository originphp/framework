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
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Http\Middleware;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\Dispatcher;

class DispatcherMiddleware extends Middleware
{
    /**
       * This dispatch process is being done through middleware since this will
       * create and process a response object. E.g. setting cookies in the controller, will
       * modify the response object, and that should be available to other middlewares.
       *
       * @param \Origin\Http\Request $request
       */
    public function process(Request $request, Response $response) : void
    {
        $dispatcher = Dispatcher::instance();
        $dispatcher->dispatch($request, $response);
    }
}
