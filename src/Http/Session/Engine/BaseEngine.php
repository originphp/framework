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
namespace Origin\Http\Session\Engine;

use Origin\Core\Config;
use Origin\Security\Security;
use Origin\Http\Session\SessionEngineInterface;

abstract class BaseEngine implements SessionEngineInterface
{

     /**
     * Session ID
     *
     * @var string|null
     */
    protected $id = null;

    /**
     * Session started flag
     *
     * @var boolean
     */
    protected $started = false;

    /**
     * Session name
     *
     * @var string
     */
    protected $name = 'id';

    /**
     * Session ID length
     * @see https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html
     * @var integer
     */
    protected $idLength = 32;

    /**
     * @var int
     */
    protected $timeout = 3600;

    public function __construct()
    {
        $this->loadConfiguration();
        $this->initialize();
    }

    /**
     * Place your startup logic here
     *
     * @return void
     */
    abstract protected function initialize(): void;

    /**
       * Sets or gets the session id
       *
       * "The session ID must be long enough to prevent brute force attacks, where an attacker can go through the
       * whole range of ID values and verify the existence of valid sessions. The session ID length must be at
       * least 128 bits (16 bytes). " owasp
       *
       * @param string|null $id
       * @return string|void
       */
    public function id(string $id = null)
    {
        if ($id === null) {
            return $this->id;
        }

        $this->id = $id;
    }

    /**
     * Sets or gets the name
     *
     * @param string $name
     * @return string|void
     */
    public function name(string $name = null)
    {
        if ($name === null) {
            return $this->name;
        }

        $this->name = $name;
    }

    /**
     * Checks if session timedout
     *
     * @return boolean
     */
    protected function timedOut(): bool
    {
        $lastActivity = $this->read('Session.lastActivity');
        $this->write('Session.lastActivity', time());

        return $lastActivity ? (time() - $lastActivity > $this->timeout)  : false;
    }

    /**
     * A temporary function for backwards comptability which is used by the various engine
     *
     * @deprecated This is to handle the deprecation
     * @return integer
     */
    protected function checkTimeout(): ?int
    {
        if (Config::exists('App.sessionTimeout')) {
            /**
             * 1. Add the following to config/bootstrap.php in the relevant section
             *
             * Config::load('session');
             *
             * 2. Create config/session.php
             *
             * use Origin\Http\Session\Engine\PhpEngine;
             * return [
             *    'className' => PhpEngine::class,
             *    'timeout' => 3600
             * ];
             */
            deprecationWarning('Config setting sessionTimeout deprecated use Session.timeout instead. Create session.php and load in bootstrap.');

            return Config::read('App.sessionTimeout');
        }

        return null;
    }

    /**
     * Generates a session ID
     *
     * @see https://owasp.org/www-community/vulnerabilities/Insufficient_Session-ID_Length
     *
     * @return string
     */
    protected function generateId(): string
    {
        return Security::hex($this->idLength);
    }
    
    /**
     * Validate a session ID
     *
     * @param string $id
     * @return boolean
     */
    protected function isValid(string $id): bool
    {
        return (bool) preg_match('/^[0-9a-f]{' . $this->idLength .'}+$/', $id);
    }

    /**
     * Loads session settings from the config and sets up the object
     *
     * @return void
     */
    protected function loadConfiguration(): void
    {
        $config = Config::read('Session') ?: [];

        $this->name = $config['name'] ?? 'id';
        $this->timeout = $config['timeout'] ?? 900;
        $this->idLength = $config['idLength'] ?? 32;
       
        // backwards compatible
        $this->timeout = $this->checkTimeout() ?: $this->timeout;
    }
}
