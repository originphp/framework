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

declare(strict_types=1);
namespace Origin\Http\Middleware;

use Origin\Cache\Cache;
use Origin\Core\PhpFile;
use Origin\Http\Exception\ForbiddenException;
use Origin\Http\Request;
use Origin\Http\Middleware\Middleware;
use Origin\Http\Exception\ServiceUnavailableException;

class ThrottleMiddleware extends Middleware
{
    /**
     * Default config
     *
     * The average of 1 request per second is expected, allowing for occasional burts by
     * using a multiple, for example 10/10.
     *
     * @var array
     */
    protected $defaultConfig = [
        'limit' => 10,
        'period' => 10,
        'ban' => '+15 minutes',
        'blacklist' => TMP . '/blacklist.php'
    ];

    /**
     * IP addresses blacklisted
     * example
     * ['127.0.0.1' => 1592342342]
     *
     * @var array
     */
    protected $blacklist = [];

    /**
     * Intialize the Throttle Middleware
     *
     * @return void
     */
    protected function initialize(): void
    {
        // create copy of framework config and adjust duration
        $config = Cache::config('origin');
        $config['duration'] = '+' . $this->config['period'] . ' seconds';
        Cache::config('throttle', $config);
    }

    /**
     * This HANDLES the request. Use this to make changes to the request.
     *
     * @param \Origin\Http\Request $request
     * @return void
     */
    public function handle(Request $request): void
    {
        $ipAddress = $request->ip();
        if (!$ipAddress) {
            throw new ForbiddenException('Invalid IP address');
        }

        $this->checkBlackist($ipAddress);

        $this->throttle($ipAddress);

        $this->updateBlacklist();
    }

    /**
     * Checks if an IP address is blacklisted
     *
     * @param string $ipAddress
     * @return void
     * @throws \Origin\Http\Exception\ServiceUnavailableException this particular error is handy with some bots
     */
    private function checkBlackist(string $ipAddress): void
    {
        if (file_exists($this->config['blacklist'])) {
            $this->blacklist = (new PhpFile())->read($this->config['blacklist']);
        }

        if (isset($this->blacklist[$ipAddress])) {
            if ($this->blacklist[$ipAddress] > time()) {
                throw new ServiceUnavailableException('Service Unavailable');
            }
        }
    }

    /**
     * Throttles the request for an IP address
     *
     * @param string $ipAddress
     * @return void
     */
    private function throttle(string $ipAddress): void
    {
        $cache = Cache::store('throttle');
        $requestId = md5('throttle-' . $ipAddress);
        $requests = $cache->read($requestId) ?: 0;
        if ($requests >= $this->config['limit']) {
            $this->ban($ipAddress);
        }
        $cache->increment($requestId, 1);
    }

    /**
     * Bans an IP address
     *
     * @param string $ipAddress
     * @return void
     */
    private function ban(string $ipAddress): void
    {
        if (!isset($this->blacklist[$ipAddress])) {
            $this->blacklist[$ipAddress] = strtotime($this->config['ban']);
        }
    }

    /**
     * Rewrites the blacklist removing expired bans
     *
     * @return void
     */
    private function updateBlacklist(): void
    {
        $out = [];
        $now = time();

        foreach ($this->blacklist as $ipAddress => $time) {
            if ($time > $now) {
                $out[$ipAddress] = $time;
            }
        }

        (new PhpFile())->write($this->config['blacklist'], $out);

        // Free memory
        $this->blacklist = [];
    }
}
