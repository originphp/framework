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
declare(strict_types=1);
namespace Origin\Http\Middleware;

use Origin\Http\Request;
use Origin\Http\Response;

class SessionMiddleware extends Middleware
{
    /**
     * @var string|null
     */
    private $id = null;

    /**
     * @var string|null
     */
    private $name = null;

    /**
     * @var \Origin\Session\Session
     */
    private $session;

    public function handle(Request $request): void
    {
        $this->session = $request->session();
        $this->name = $this->session->name();

        // gets the id from the cookie
        $this->id = $this->getSessionId($request);

        $this->session->id($this->id);
        $this->session->start();
    }

    public function process(Request $request, Response $response): void
    {
        if (! $request->cookies($this->name) || $this->id !== $this->session->id()) {
            $this->addCookieToResponse($request, $response);
        }

        $this->session->close();

        /**
         * This removes the cookie header set by PHP session extension, so we can write our own cookie
         * if this becomes problematic or confusing due to PHP.ini settings, then remove
         */
        header_remove('Set-Cookie');
    }

    private function getSessionId(Request $request): ?string
    {
        return $request->cookies($this->name);
    }

    /**
     * PHP Session has its own settings and will add cookie to the header, so anything set here will
     * be ingored in that case. To get it work use header_remove('Set-Cookie') but this means PHP_INI settings
     * will be ignored as well, so I have to think about this.
     *
     * TODO: think if should ignore PHP session settings from PHP.ini
     *
     * @param \Origin\Http\Request $request
     * @param \Origin\Http\Response $response
     * @return void
     */
    private function addCookieToResponse(Request $request, Response $response): void
    {
        $response->cookie($this->name, $this->session->id(), [
            'expires' => 0, // Cookie expires after browser is closed
            'encrypt' => false, // Encryption helps identify system
            'secure' => $request->server('HTTPS') !== null,
            'httpOnly' => true,
            'sameSite' => 'strict' // TODO: test in different senarios
        ]);
    }
}
