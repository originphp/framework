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

/**
 * Profiler middleware keeps track of the memory usage and time it takes for each request. This is handy
 * for new application being deployed to locate memory leaks and long running requests.
 *
 * This will produce something like this in the /var/www/logs/profile.log
 *
 * [2019-11-03 13:49:11] GET http://localhost:3000/bookmarks/add 0.0644s 934.87kb
 */
class ProfilerMiddleware extends Middleware
{
    /**
     * Default config
     *
     * @var array
     */
    protected $defaultConfig = [
        'log' => LOGS . '/profile.log'
    ];

    /**
     * @var int
     */
    private $startTime;

    /**
     * @var \Origin\Http\Request
     */
    private $request;

    protected function initialize(): void
    {
        $this->startTime = microtime(true);
    }

    /**
     * This PROCESSES the response. Use this to make changes to the response.
     *
     * @param \Origin\Http\Request $request
     * @param \Origin\Http\Response $response
     * @return void
     */
    public function process(Request $request, Response $response): void
    {
        $this->request = $request;
    }

    /**
     * Using destruct to make sure that logging happens after all middlewares run
     * and processed. Always check request object exists first.
     */
    public function __destruct()
    {
        if ($this->request) {
            $profile = $this->logRequest($this->request);
            file_put_contents($this->config['log'], $profile . "\n", FILE_APPEND);
        }
    }

    private function logRequest(Request $request): string
    {
        return sprintf(
            '[%s] %s %s %s %s',
            date('Y-m-d H:i:s'),
            $request->method(),
            $request->url(),
            number_format(microtime(true) - $this->startTime, 4) . 's',
            $this->humanReadable(memory_get_peak_usage())
        );
    }

    /**
     * Gets the human readable
     *
     * @param integer $bytes
     * @return string
     */
    private function humanReadable(int $bytes): string
    {
        $size = ['b', 'kb', 'mb', 'gb'];
        $factor = floor((strlen((string) $bytes) - 1) / 3);

        return sprintf('%.2f', $bytes / pow(1024, $factor)) . $size[$factor];
    }
}
