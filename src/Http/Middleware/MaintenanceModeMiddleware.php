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
use Origin\Http\Middleware\Exception\MaintainenceModeException;

class MaintenanceModeMiddleware extends Middleware
{
    protected $defaultConfig = [
        'html' => false
    ];

    /**
     * This HANDLES the request. Use this to make changes to the request.
     *
     * @param \Origin\Http\Request $request
     * @return void
     */
    public function handle(Request $request): void
    {
        if ($this->maintenanceMode()) {
            $data = json_decode(file_get_contents(tmp_path('maintenance.json')), true);

            // Check IP to see if its in allowed list
            if ($data['allowed'] && in_array($request->ip(), $data['allowed'])) {
                return;
            }
            // Send headers
            $this->sendHeader('Maintenance-Started: ' . $data['time']);
            $this->sendHeader('Retry-After: ' . ($data['retry'] ? ($data['time'] + $data['retry']) : null));

            if ($this->config('html')) {
                $this->sendHeader('Location: /maintenance.html');
                $this->exit();
            } else {
                throw new MaintainenceModeException(
                    $data['message']
                );
            }
        }
    }

    /**
     * Wrapped for testing
     *
     * @return void
     */
    protected function exit(): void
    {
        exit();
    }

    /**
     * Checks if maintenance is enabled
     *
     * @return boolean
     */
    protected function maintenanceMode(): bool
    {
        return file_exists(tmp_path('maintenance.json'));
    }

    /**
     * Sends a header for the exception
     *
     * @param string $header
     * @return void
     */
    protected function sendHeader(string $header): void
    {
        header($header);
    }
}
