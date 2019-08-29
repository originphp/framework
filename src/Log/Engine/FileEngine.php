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

namespace Origin\Log\Engine;

class FileEngine extends BaseEngine
{
    /**
     * Default configuration
     *
     * @var array
     */
    protected $defaultConfig = [
        'filename' => null,
        'path' => LOGS,
        'levels' => [],
        'channels' => [],
    ];

    public function initialize(array $config)
    {
        /**
         * @deprecated this was changed, so this is to provide
         * backwards comptability
         */
        if (isset($this->config['file'])) {
            $this->config['filename'] = $this->config['file'];
            deprecationWarning('FileEngine option file deprecated use filename instead');
        }
        if ($this->config('filename') === null) {
            $this->config('filename', 'application.log');
        }
    }

    /**
      * Workhorse for the logging methods
      *
      * @param string $level e.g debug, info, notice, warning, error, critical, alert, emergency.
      * @param string $message 'this is a {what}'
      * @param array $context  ['what'='string']
      * @return bool
      */
    public function log(string $level, string $message, array $context = [])
    {
        $message = $this->format($level, $message, $context) . "\n";
        $file = $this->config('path') . DS . $this->config('filename');

        return (bool) file_put_contents($file, $message, FILE_APPEND | LOCK_EX);
    }
}
