<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types = 1);
namespace Origin\Http\Middleware;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Security\Security;
use Origin\Http\Middleware\Exception\InvalidCsrfTokenException;

class CsrfProtectionMiddleware extends Middleware
{
    protected $defaultConfig = [
        'cookieName' => 'CSRF-Token', // Cookie name for generated CSRF token
        'expires' => '+60 minutes', // Expiry time defaults to 60 minutes like sessions,
        'httpOnly' => false, // only available to HTTP protocol (not javascript)
        'secure' => false, // cookie only transmitted over HTTPS,
        'encrypt' => true
    ];

    /**
     * This handles the request
     *
     * If the request is a post request, it will validate the CSRF token
     * If the request is a get request and thre is no cookie (or it expired) it will generate a new token
     *
     * @param \Origin\Http\Request $request
     * @return void
     */
    public function handle(Request $request): void
    {
        if ($request->params('csrfProtection') === false) {
            return ;
        }
  
        # Generate the CSRF token
        $token = $request->cookies($this->config('cookieName'));
        if ($request->is(['get']) && $token === null) {
            $token = $this->generateToken();
        }
         
        # Works it
        if ($request->is(['post', 'put', 'patch', 'delete']) || $request->data()) {
            $this->validateToken($request);
            # Remove csrfToken field that posted with form
            $post = $request->data();
            unset($post['csrfToken']);
            $request->data($post);
        }

        # Set the CSRF Token in the request
        $request->params('csrfToken', $token);
    }

    /**
     * Processes the response
     *
     * @param \Origin\Http\Request $request
     * @param \Origin\Http\Response $response
     * @return void
     */
    public function process(Request $request, Response $response): void
    {
        if ($request->params('csrfProtection') === false) {
            return ;
        }
   
        if ($request->method() === 'GET') {
            $response->cookie($this->config('cookieName'), $request->params('csrfToken'), $this->config);
        }
    }

    /**
     * Checks if in test environment
     *
     * @return bool
     */
    protected function isTestEnvironment(): bool
    {
        return (isConsole() && env('ORIGIN_ENV') === 'test');
    }

    /**
     * Validates the CSRF token using either the cookie or the header
     *
     * @param Request $request
     * @return void
     */
    private function validateToken(Request $request): void
    {
        /**
         * Disable when runing unit tests
         */
        if ($this->isTestEnvironment()) {
            return;
        }

        $token = $request->cookies($this->config('cookieName'));

        if (! $token) {
            throw new InvalidCsrfTokenException('Missing CSRF Token Cookie.');
        }
 
        if (! Security::compare($token, $request->data('csrfToken')) && ! Security::compare($token, $request->headers('X-CSRF-Token'))) {
            throw new InvalidCsrfTokenException('CSRF Token Mismatch.');
        }
    }

    /**
    * Generates a token for CSRF protection
    * @see https://github.com/OWASP/CheatSheetSeries/blob/master/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.md
    * @return string
    */
    private function generateToken(): string
    {
        $randomBytes = random_bytes(64);

        return Security::hash($randomBytes, [
            'type' => 'sha512'
        ]);
    }
}
