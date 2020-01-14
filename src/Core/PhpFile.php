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
declare(strict_types = 1);
namespace Origin\Core;

use Origin\Core\Exception\Exception;
use Origin\Core\Exception\InvalidArgumentException;

/**
 * A class for reading and writing arrays to file
 */
class PhpFile
{
    /**
     * Reads a PHPFile (array)
     *
     * @param string $filename /var/www/config/data.php
     * @return array
     */
    public function read(string $filename) : array
    {
        if (! file_exists($filename)) {
            throw new InvalidArgumentException(sprintf('File `%` does not exist', $filename));
        }
        $out = include $filename;
        if (is_array($out)) {
            return $out;
        }
        throw new Exception(sprintf('File `%s` did not return an array', $filename));
    }

    /**
     * Writes the array to disk
     *
     * @param string $filename
     * @param array $data
     * @param array $options The following options key are supported
     *  - lock: default false. Wether to lock the file write.
     * @return boolean
     */
    public function write(string $filename, array $data, array $options = []) : bool
    {
        $options += ['lock' => false];
        $out = '<?php' . "\n" . 'return ' . var_export($data, true) . ';';

        return (bool) file_put_contents($filename, $out, $options['lock'] ? LOCK_EX : 0);
    }
}
