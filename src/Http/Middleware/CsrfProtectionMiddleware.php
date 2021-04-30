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
use Origin\Http\Exception\BadRequestException;
use Origin\Http\Middleware\Exception\InvalidCsrfTokenException;

/**
 * To prevent token errors if session expires, add the following code to your header to refresh the page
 * at the end of the session.
 * IMPORTANT: set to 1 second longer than timeout if not it will keep somebody logged in forever.
 *
 * <meta http-equiv="refresh" content="<?= Config::read('Session.timeout') + 1 ?>">
 *
 * Security
 * - Session cookies must use SameSite attribute
 * - Implement at least one itigation from Defense in Depth Mitigations section
 * - Moved away from cookies to store CSRF token, see ByPassing CSRF Protections by OWSAP
 *
 * @see https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html
 */
class CsrfProtectionMiddleware extends Middleware
{
    protected $defaultConfig = [
        'cookieName' => 'CSRF-Token',
        'tokenLength' => 32,  // CSRF token length 128 bits (16 bytes) is the recommended minimum
        'singleUse' => false  // If enabled token will be regenerated each time it used. Not AJAX friendly
    ];

    /**
     * This handles the request. If the request is a post request, it will validate the CSRF token
     *
     * @param \Origin\Http\Request $request
     * @return void
     */
    public function handle(Request $request): void
    {
        if ($request->params('csrfProtection') === false) {
            return;
        }
     
        // Generate the CSRF token and store in session
        $token = $request->cookies($this->config('cookieName')) ?: $this->generateToken();
  
        // Validate the token
        if ($request->is(['post', 'put', 'patch', 'delete']) || $request->data()) {
            $this->verifyOrigin($request);
            $this->validateToken($request);
          
            // Remove csrfToken field that was posted with form
            $post = $request->data();
            unset($post['csrfToken']);
            $request->data($post);

            // regenerate token
            if ($this->config('singleUse')) {
                $token = $this->generateToken();
            }
        }
       
        /**
         * Set the CSRF Token in the request
         * @internal This is being stored twice now,
         */
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
            return;
        }
       
        // If token has changed write it again
        if ($request->cookies($this->config('cookieName')) !== $request->params('csrfToken')) {
            $defaults = [
                'expires' => 0, // Cookie expires after browser is closed
                'encrypt' => false, // Encryption helps identify system
                'secure' => $request->server('HTTPS') !== null,
                'httpOnly' => true,
                'sameSite' => 'Strict'
            ];

            $response->cookie($this->config('cookieName'), $request->params('csrfToken'), array_merge($defaults, $this->config));
        }
    }

    /**
     * EXPERIMENTAL: If the Origin header is present check that, if not check referer
     *
     * @param \Origin\Http\Request $request
     * @return void
     */
    private function verifyOrigin($request): void
    {
        $uri = $request->headers('Origin') ?: $request->headers('Referer');
        
        if ($uri) {
            $host = $this->parseHost($uri);

            if ($host !== $request->host()) {
                throw new BadRequestException('Request made from different host');
            }
        }
    }

    /**
     * Undocumented function
     *
     * @param string $url
     * @return string
     */
    private function parseHost(string $url): string
    {
        $parsed = parse_url($url);

        return empty($parsed['port']) ? $parsed['host'] : $parsed['host'] .  ':' . $parsed['port'];
    }

    /**
     * For security reasons make sure token submitted is hexidemical and correct length
     *
     * @param string|null $token
     * @return boolean
     */
    private function isValidToken(string $token = null): bool
    {
        return $token && (bool) preg_match('/^[0-9a-f]{' . $this->config('tokenLength') .'}+$/', $token);
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
     * @param \Origin\Http\Request $request
     * @return void
     */
    private function validateToken(Request $request): void
    {
        // Don't run in tests
        if ($this->isTestEnvironment()) {
            return;
        }

        $token = $request->cookies($this->config('cookieName'));
        $formToken = $request->data('csrfToken') ?: $request->headers('X-CSRF-Token');

        // Check token is in the right places
        if (! $token || ! $formToken) {
            throw new InvalidCsrfTokenException('Missing CSRF Token.');
        }

        // Validate token format is correct
        if (! $this->isValidToken($formToken)) {
            throw new InvalidCsrfTokenException('Invalid CSRF Token.');
        }

        // Check that the tokens match up
        if (! Security::compare($token, $formToken)) {
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
        return Security::hex($this->config('tokenLength'));
    }
}
