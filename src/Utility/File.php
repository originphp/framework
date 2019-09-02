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

namespace Origin\Utility;

use Origin\Utility\Exception\NotFoundException;

class File
{
    /**
     * Reads a file
     *
     * @param string $filename
     * @return string $contents
     */
    public static function read(string $filename) : string
    {
        if (is_readable($filename)) {
            return file_get_contents($filename);
        }
        throw new NotFoundException(sprintf('%s could not be found', $filename));
    }

    /**
     * Checks if a file exists
     *
     * @param string $filename
     * @return bool
     */
    public static function exists(string $filename) : bool
    {
        return file_exists($filename) and is_file($filename);
    }

    /**
     * Writes to a file
     *
     * @param string $filename
     * @param string $contents
     * @return boolean
     */
    public static function write(string $filename, string $contents) : bool
    {
        $folder = pathinfo($filename, PATHINFO_DIRNAME);
        if (is_dir($folder) and is_writeable($folder)) {
            return (bool) file_put_contents($filename, $contents, LOCK_EX);
        }

        return false;
    }

    /**
     * Appends contents to a file
     *
     * @param string $filename
     * @param string $contents
     * @return bool
     */
    public static function append(string $filename, string $contents) : bool
    {
        $folder = pathinfo($filename, PATHINFO_DIRNAME);
        if (is_dir($folder) and is_writeable($folder)) {
            return (bool) file_put_contents($filename, $contents, FILE_APPEND | LOCK_EX);
        }

        return false;
    }

    /**
     * Deletes a file
     *
     * @param string $filename
     * @return bool
     */
    public static function delete(string $filename) : bool
    {
        if (file_exists($filename)) {
            return unlink($filename);
        }
        throw new NotFoundException(sprintf('%s could not be found', $filename));
    }

    /**
     * Creates a temporary file. If no data is provided then it just returns the filename
     *
     * @param string $data Data to place in the temporary file
     * @return string $filename A temporary file
     */
    public static function tmp(string $data = null) : string
    {
        $filename = sys_get_temp_dir() . DS . uid();
        if ($data) {
            file_put_contents($filename, $data);
        }

        return $filename;
    }

    /**
     * Renames a file, if the file already exists, then it will be overwritten
     *
     * @param string $from filename with full path
     * @param string $to new filename
     * @return bool
     */
    public static function rename(string $from, string $to) : bool
    {
        if (self::exists($from)) {
            if (strpos($to, DS) === false) {
                $to = pathinfo($from, PATHINFO_DIRNAME) . DS . $to;
            }

            return @rename($from, $to);
        }
        throw new NotFoundException(sprintf('%s could not be found', $from));
    }

    /**
    * Moves a file
    *
    * @param string $source filename with full path
    * @param string $destination filename with full path
    * @return bool
    */
    public static function move(string $source, string $destination) : bool
    {
        if (self::exists($source)) {
            return @rename($source, $destination);
        }
        throw new NotFoundException(sprintf('%s could not be found', $source));
    }

    /**
     * Copies a file
     *
     * @param string $source filename with full path
     * @param string $destination filename with or without full path (same directory)
     * @return bool
     */
    public static function copy(string $source, string $destination) : bool
    {
        if (self::exists($source)) {
            if (strpos($destination, DS) === false) {
                $destination = pathinfo($source, PATHINFO_DIRNAME) . DS . $destination;
            }

            return @copy($source, $destination);
        }
        throw new NotFoundException(sprintf('%s could not be found', $source));
    }

    /**
       * Changes the file permissions. The directory must belong to
       *
       * @param string $filename filename with full path
       * @param int $mode e.g 0755 (remember 0 infront)
       * @return bool
       */
    public static function chmod(string $filename, int $mode) : bool
    {
        if (self::exists($filename)) {
            return @chmod($filename, $mode);
        }
        throw new NotFoundException(sprintf('%s could not be found', $filename));
    }

    /**
    * Changes the owner of the file
    *
    * @param string $filename filename with full path
    * @param string $user  e.g. root, www-data
    * @return bool
    */
    public static function chown(string $filename, string $user) : bool
    {
        if (self::exists($filename)) {
            return @chown($filename, $user);
        }
        throw new NotFoundException(sprintf('%s could not be found', $filename));
    }

    /**
     * Changes the group of the file
     *
     * @param string $filename filename with full path
     * @param string $user  e.g. root, www-data
     * @return bool
     */
    public static function chgrp(string $filename, string $group) : bool
    {
        if (self::exists($filename)) {
            return @chgrp($filename, $group);
        }
        throw new NotFoundException(sprintf('%s could not be found', $filename));
    }
  
    /**
     * Gets the permissions of a file
     *
     * @param string $filename filename with full path
     * @param int $mode e.g 0755 (remember 0 infront)
     * @return string
     */
    public static function mode(string $filename) : string
    {
        if (self::exists($filename)) {
            return (string) substr(sprintf('%o', fileperms($filename)), -4);
        }
        throw new NotFoundException(sprintf('%s could not be found', $filename));
    }
    
    /**
     * Alias for mode. Gets the mode for a file aka permissions
     *
     * @param string $filename
     * @return string
     */
    public static function perms(string $filename) : string
    {
        return static::mode($filename);
    }

    /**
     * Gets the owner of a file
     *
     * @param string $filename filename with full path
     * @return string
     */
    public static function owner(string $filename) : string
    {
        if (self::exists($filename)) {
            return posix_getpwuid(fileowner($filename))['name'];
        }
        throw new NotFoundException(sprintf('%s could not be found', $filename));
    }
    /**
     * Gets the group that the file belongs to
     *
     * @param string $filename filename with full path
     * @return string
     */
    public static function group(string $filename) : string
    {
        if (self::exists($filename)) {
            return posix_getgrgid(filegroup($filename))['name'];
        }
        throw new NotFoundException(sprintf('%s could not be found', $filename));
    }

    /**
     * Returns information about a file
     *
     * @param string $filename
     * @return array
     */
    public static function info(string $filename) : array
    {
        if (self::exists($filename)) {
            $pathinfo = pathinfo($filename);

            return [
                'path' => $pathinfo['dirname'],
                'filename' => $pathinfo['basename'],
                'extension' => $pathinfo['extension'] ?? null,
                'type' => mime_content_type($filename),
                'size' => filesize($filename),
                'timestamp' => filemtime($filename),
            ];
        }
        throw new NotFoundException(sprintf('%s could not be found', $filename));
    }
}
