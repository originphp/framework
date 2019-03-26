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

namespace Origin\Middleware;

use Origin\Controller\Request;
use Origin\Controller\Response;

class Middleware
{
    /**
       * Processes the request, this must be implemented
       *
       * @param \Origin\Controller\Request $request
       * @param \Origin\Controller\Response $response
       * @return \Origin\Controller\Response
       */
    public function process(Request $request, Response $response) : Response
    {
        // do something
        return $response;
    }
}
