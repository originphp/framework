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

use Origin\Middleware\Exception\InvalidCsrfTokenException;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\Middleware;
use Origin\Utility\Security;

class CsrfProtectionMiddleware extends Middleware
{
    /**
     * A secure long CSRF token, and its to params to make it available to app and sets a cookie
     * @see https://github.com/OWASP/CheatSheetSeries/blob/master/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.md
     */
    protected $token = null;

    protected $defaultConfig = [
        'cookieName' => 'CSRF-Token', // Cookie name for generated CSRF token
        'expires' => '+60 minutes' // Expiry time defaults to 60 minutes like sessions
    ];

    public function initialize(array $config)
    {
        $this->token = Security::hash(random_bytes(64), ['type'=>'sha512']);
    }

    /**
     * This handles the request
     *
     * If the request is a post request, it will validate the csrf token then create a new one.
     * If the request is a get request, it just generates the csrf token.
     *
     * @param \Origin\Http\Request $request
     * @param \Origin\Http\Response $response
     * @return \Origin\Http\Response
     */
    public function startup(Request $request)
    {
        if ($request->is(['post', 'put', 'patch', 'delete']) or $request->data()) {
            if ($request->params('csrfProtection') !== false) {
                $this->validateToken($request);
            }
            // unset csrfToken field
            $post = $request->data();
            unset($post['csrfToken']);
            $request->data($post);
        }

        $request->params('csrfToken', $this->token);
    }

    /**
     * Processes the response
     *
     * @param \Origin\Http\Request $request
     * @param \Origin\Http\Response $response
     * @return void
     */
    public function shutdown(Request $request, Response $response)
    {
        $response->cookie($this->config('cookieName'), $this->token, $this->config('expires'));
    }

    /**
     * Checks if in test environment
     *
     * @return void
     */
    protected function isTestEnvironment()
    {
        return ((PHP_SAPI === 'cli' or PHP_SAPI === 'phpdbg') and env('ORIGIN_ENV') === 'test');
    }


    /**
     * Validates the CSRF token using either the cookie or the header
     *
     * @param Request $request
     * @return void
     */
    protected function validateToken(Request $request)
    {
        /**
         * Disable when runing unit tests
         */
        if ($this->isTestEnvironment()) {
            return;
        }

        $cookie = $request->cookies($this->config('cookieName'));

        if (!$cookie) {
            throw new InvalidCsrfTokenException('Missing CSRF Token Cookie.');
        }
 
        if (!Security::compare($cookie, $request->data('csrfToken')) and !Security::compare($cookie, $request->headers('X-CSRF-Token'))) {
            throw new InvalidCsrfTokenException('CSRF Token Mismatch.');
        }
    }
}
