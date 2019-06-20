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
 * You can use startup/shutdown or handle/process but not both.
 */
namespace Origin\Http;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Core\ConfigTrait;

class Middleware
{
    use ConfigTrait;
    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        $this->config($config);
        $this->initialize($config);
    }
    /**
     * Hook called during construct
     *
     * @return void
     */
    public function initialize(array $config)
    {
    }
    /**
     * This HANDLES the request
     *
     * @param Request $request
     * @return void
     */
    public function startup(Request $request)
    {
    }
    /**
     * This PROCESSES the response after all middleware requests have
     * been handled
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function shutdown(Request $request, Response $response)
    {
    }

    /**
     * This HANDLES the request.
     *
     * @param Request $request
     * @return void
     */
    public function handle(Request $request)
    {
        $this->startup($request);
    }
    /**
     * This PROCESSES the response after all middleware requests have
     * been handled
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function process(Request $request, Response $response)
    {
        $this->shutdown($request, $response);
    }

    /**
     * This is the magic method.
     *
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return void
     */
    public function __invoke(Request $request, Response $response, callable $next = null)
    {
        $this->handle($request);
        if ($next) {
            $response = $next($request, $response);
        }
        $this->process($request, $response);
        return $response;
    }
}
