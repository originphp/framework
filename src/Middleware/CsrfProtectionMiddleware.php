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

class CsrfProtectionMiddleware extends Middleware
{
    /**
     * Cookie name for generated CSRF token
     *
     * @var string
     */
    protected $cookieName = 'CSRF-Token';

    /**
     * The expiry time, defaults to 60 minutes
     * like sessions.
     *
     * @var string
     */
    protected $expires = '+60 minutes';

    /**
     * If the request is a post request, it will validate the csrf token then create a new one.
     * If the request is a get request, it just generates the csrf token.
     *
     * @param \Origin\Http\Request $request
     * @param \Origin\Http\Response $response
     * @return \Origin\Http\Response
     */
    public function process(Request $request, Response $response) : Response
    {
        if ($request->is(['post','put','patch','delete']) or $request->data()) {
            if ($request->params('csrfProtection') !== false) {
                $this->validateToken($request);
            }
            // unset csrfToken field
            $post = $request->data();
            unset($post['csrfToken']);
            $request->data($post);
        }
        $this->createToken($request, $response);
        return $response;
    }

    /**
     * Creates a secure long csrf token, and its to params to make it available to app and sets a cookie
     * @see https://github.com/OWASP/CheatSheetSeries/blob/master/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.md
     *
     * @param Request $request
     * @return void
     */
    protected function createToken(Request $request, Response $response)
    {
        $token = hash('sha512', random_bytes(64));
        $request->params('csrfToken', $token);
        $response->cookie($this->cookieName, $token, $this->expires);
    }

    /**
     * Validates the CSRF token using either the cookie or the header
     *
     * @param Request $request
     * @return void
     */
    protected function validateToken(Request $request)
    {
        $cookie = $request->cookies($this->cookieName);
        
        if (!$cookie) {
            throw new InvalidCsrfTokenException('Missing CSRF Token Cookie.');
        }

        if ($cookie !== $request->data('csrfToken') and $cookie !== $request->header('X-CSRF-Token')) {
            throw new InvalidCsrfTokenException('CSRF Token Mismatch.');
        }
    }
}
