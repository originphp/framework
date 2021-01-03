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
    public function read(string $filename): array
    {
        if (! file_exists($filename)) {
            throw new InvalidArgumentException(sprintf('File `%s` does not exist', $filename));
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
     *  - short: default false. Export array using short array syntax should be used with caution on selected
     *  data.
     * @return boolean
     */
    public function write(string $filename, array $data, array $options = []): bool
    {
        $options += ['lock' => false, 'short' => false,'before' => null, 'after' => null];
        $out = $options['short'] ? $this->varExport($data) : var_export($data, true);
        $out = '<?php' . "\n" . $options['before'] . "\n" . 'return ' . $out . ';' .  "\n" . $options['after'] ;
       
        return (bool) file_put_contents($filename, $out, $options['lock'] ? LOCK_EX : 0);
    }

    /**
     * Modern version of varExport
     *
     * @param array $data
     * @return string
     */
    private function varExport(array $data): string
    {
        $data = var_export($data, true);
        $data = str_replace(
            ['array (', "),\n", " => \n"],
            ['[', "],\n", ' => '],
            $data
        );
        $data = preg_replace('/=>\s\s+\[/i', '=> [', $data);
        $data = preg_replace("/=> \[\s\s+\]/m", '=> []', $data);

        return substr($data, 0, -1).']';
    }
}
