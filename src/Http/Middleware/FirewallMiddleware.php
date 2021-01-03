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

use Origin\Core\PhpFile;
use Origin\Http\Request;
use Origin\Http\Exception\ForbiddenException;

/**
 * FirewallMiddleware is a for blacklisting IPs or restricting access to your web to only certain IPs
 *
 * Create config/blacklist.php (or whitelist.php)
 *
 * <?php
 *
 * return [
 *     '192.168.176.4'
 * ];
 */
class FirewallMiddleware extends Middleware
{
    /**
     * Array of IPs that are blacklisted
     *
     * @var array
     */
    protected $blacklist = [];

    /**
     * Array of IPs that are allowed. If this has any values only these IP addresses
     * will be allowed.
     *
     * @var array
     */
    protected $whitelist = [];
   
    /**
     * This HANDLES the request. Use this to make changes to the request.
     *
     * @param \Origin\Http\Request $request
     * @return void
     */
    public function handle(Request $request): void
    {
        $ipAddress = $request->ip();
        if (! $ipAddress) {
            throw new ForbiddenException('Invalid IP address');
        }

        if (file_exists(CONFIG . '/blacklist.php')) {
            $this->blacklist = (new PhpFile())->read(CONFIG . '/blacklist.php');
        }

        if (file_exists(CONFIG . '/whitelist.php')) {
            $this->whitelist = (new PhpFile())->read(CONFIG . '/whitelist.php');
        }

        $this->checkLists($ipAddress);

        // Free mem
        $this->blacklist = $this->whitelist = null;
    }

    /**
     * Checks an IP address against blacklist and whitelists
     *
     * @param string $ip
     * @return void
     */
    protected function checkLists(string $ip): void
    {
        if ($this->whitelist) {
            if (! in_array($ip, $this->whitelist)) {
                throw new ForbiddenException('IP address is not allowed');
            }
        } elseif (in_array($ip, $this->blacklist)) {
            throw new ForbiddenException('IP address is blacklisted');
        }
    }
}
