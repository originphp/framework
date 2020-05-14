<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types=1);
namespace Origin\Http\Middleware;

use Origin\Http\Request;
use Origin\Http\Response;

/**
 * Creates an access log using the Apache Common LOG format, with one main difference, the ability to detect users
 * logged in.
 *
 * @see https://httpd.apache.org/docs/2.4/logs.html#accesslog.
 */
class AccessLogMiddleware extends Middleware
{
    /**
     * Default config
     *
     * @var array
     */
    protected $defaultConfig = [
        'file' => LOGS . '/access.log'
    ];

    /**
     * This PROCESSES the response. Use this to make changes to the response.
     *
     * @param \Origin\Http\Request $request
     * @param \Origin\Http\Response $response
     * @return void
     */
    public function process(Request $request, Response $response) : void
    {
        file_put_contents($this->config['file'], $this->commonFormat($request, $response) . "\n", FILE_APPEND);
    }

    /**
     * Creates an access log using the Apache Common LOG format
     * @see https://httpd.apache.org/docs/2.4/logs.html#accesslog.
     *
     * @param \Origin\Http\Request $request
     * @param \Origin\Http\Response $response
     * @return string
     */
    private function commonFormat(Request $request, Response $response) : string
    {
        return sprintf(
            '%s %s [%s] "%s %s %s" %d %d',
            $request->ip(),
            $request->session()->read('Auth.User.id') ?: '-',
            date('d/M/Y:H:i:s O'),
            $request->method(),
            $request->env('REQUEST_URI'),
            $request->env('SERVER_PROTOCOL'),
            $response->statusCode(),
            mb_strlen($response->body() ?? '')
        );
    }
}
