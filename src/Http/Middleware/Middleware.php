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
/**
 * You can use startup/shutdown or handle/process but not both.
 */
namespace Origin\Http\Middleware;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Core\HookTrait;
use Origin\Core\ConfigTrait;

class Middleware
{
    use ConfigTrait, HookTrait;
    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        $this->config($config);
        $this->executeHook('initialize', [$config]);
    }

    /**
     * This HANDLES the request.
     *
     * @param \Origin\Http\Request $request
     * @return void
     */
    public function handle(Request $request) : void
    {
    }
    /**
     * This PROCESSES the response after all middleware requests have
     * been handled
     *
     * @param \Origin\Http\Request $request
     * @param \Origin\Http\Response $response
     * @return void
     */
    public function process(Request $request, Response $response) : void
    {
    }

    /**
     * This is the magic method.
     *
     * @param \Origin\Http\Request $request
     * @param \Origin\Http\Response $response
     * @param callable $next
     * @return \Origin\Http\Response $response
     */
    public function __invoke(Request $request, Response $response, callable $next = null) : Response
    {
        $this->executeHook('startup');
        $this->handle($request);
        if ($next) {
            $response = $next($request, $response);
        }
        $this->process($request, $response);
        $this->executeHook('shutdown');

        return $response;
    }
}
