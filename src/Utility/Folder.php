<?php
declare(strict_types = 1);
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

class Folder
{
    /**
     * Creates a folder, recursively
     *
     * @param string $directory
     * @return boolean
     */
    /**
     * Undocumented function
     *
     * @param string $directory
     * @param array $options
     *  - recursive: default false. like -p creates all directories in one go
     *  - mode: default 0744. Folder permissions
     * @return boolean
     */
    public static function create(string $directory, array $options = []) : bool
    {
        $options += ['recursive' => false,'mode' => 0744];

        return @mkdir($directory, $options['mode'], $options['recursive']); # use@ No such file or directory
    }

    /**
     * Checks if a directory exists
     *
     * @param string $directory
     * @return boolean
     */
    public static function exists(string $directory) : bool
    {
        return file_exists($directory) and is_dir($directory);
    }

    /**
     * Includes a list of files
     *
     * @param string $directory
     * @param array $options Options keys are
     *  - directories: default false, includes directories
     * @return array
     */
    public static function list(string $directory, array $options = []) : array
    {
        $options += ['directories' => false];
        if (self::exists($directory)) {
            $results = [];
            $files = array_diff(scandir($directory), ['.', '..']);
            foreach ($files as $file) {
                if (! $options['directories'] and ! is_file($directory . DS . $file)) {
                    continue;
                }
                $stats = stat($directory . DS . $file);
                $results[] = [
                    'name' => $file,
                    'timestamp' => $stats['mtime'],
                    'size' => $stats['size'],
                    'type' => is_dir($directory. DS . $file)?'directory':'file',
                ];
            }

            return $results;
        }
       
        throw new NotFoundException(sprintf('%s does not exist', $directory));
    }

    /**
     * Deletes a directory. For saftey reasons recrusive is disabled by default,
     * since this will delete files etc.
     *
     * @param string $directory
     * @param array $options These are the options can you use:
     *    - recursive: If set to true, it will delete all contents and sub folders
     * @return bool
     */
    public static function delete(string $directory, array $options = []) : bool
    {
        $options += ['recursive' => false];
        if (self::exists($directory)) {
            if ($options['recursive']) {
                $files = array_diff(scandir($directory), ['.', '..']);
                foreach ($files as $filename) {
                    if (is_dir($directory . DS . $filename)) {
                        self::delete($directory . DS . $filename, $options);
                        continue;
                    }
                    @unlink($directory . DS . $filename);
                }
            }

            return @rmdir($directory);
        }
        
        throw new NotFoundException(sprintf('%s does not exist', $directory));
    }

    /**
     * Renames a directory
     *
     * @param string $directory full patth e.g. /var/www/tmp/my_project
     * @param string $to  directory name. project_name
     * @return boolean
     */
    public static function rename(string $directory, string $to) : bool
    {
        if (self::exists($directory)) {
            if (strpos($to, DS) === false) {
                $to = pathinfo($directory, PATHINFO_DIRNAME) . DS . $to;
            }

            return @rename($directory, $to);
        }
        throw new NotFoundException(sprintf('%s could not be found', $directory));
    }

    /**
     * Moves a directory
     *
     * @param string $source /var/www/tmp/docs
     * @param string $destination /var/www/tmp/documents
     * @return bool
     */
    public static function move(string $source, string $destination) : bool
    {
        if (self::exists($source)) {
            if (strpos($destination, DS) === false) {
                $destination = pathinfo($source, PATHINFO_DIRNAME) . DS . $destination;
            }

            return @rename($source, $destination);
        }
        throw new NotFoundException(sprintf('%s could not be found', $source));
    }

    /**
     * Copies a directory
     *
     * @param string $source /var/www/tmp/my_project
     * @param string $destination project_name or /var/www/tmp/project_name
     * @return boolean
     */
    public static function copy(string $source, string $destination) : bool
    {
        if (self::exists($source)) {
            if (strpos($destination, DS) === false) {
                $destination = pathinfo($source, PATHINFO_DIRNAME) . DS . $destination;
            }

            @mkdir($destination);

            $files = array_diff(scandir($source), ['.', '..']);
            foreach ($files as $filename) {
                if (is_dir($source . DS . $filename)) {
                    self::copy($source . DS . $filename, $destination . DS . $filename);
                    continue;
                }
                @copy($source . DS . $filename, $destination . DS . $filename);
            }

            return self::exists($destination);
        }
        throw new NotFoundException(sprintf('%s could not be found', $source));
    }

    /**
    * Gets or sets the permissions (mode) for a directory
    *
    * @param string $directory filename with full path
    * @return string
    */
    public static function mode(string $directory) : string
    {
        if (self::exists($directory)) {
            return  (string) substr(sprintf('%o', fileperms($directory)), -4);
        }
        throw new NotFoundException(sprintf('%s could not be found', $directory));
    }

    /**
    * Alias for mode. Gets the mode for a file aka permissions
    *
    * @param string $directory
    * @return string|null
    */
    public static function perms(string $directory) : ?string
    {
        return static::mode($directory);
    }

    /**
      * Gets the owner of directory
      *
      * @param string $directory filename with full path
      * @return string|null
      */
    public static function owner(string $directory) : ?string
    {
        if (self::exists($directory)) {
            return posix_getpwuid(fileowner($directory))['name'];
        }
        throw new NotFoundException(sprintf('%s could not be found', $directory));
    }

    /**
     * Gets the group that the directory belongs to.
     *
     * @param string $directory filename with full path
     * @return string|null
     */
    public static function group(string $directory) : ?string
    {
        if (self::exists($directory)) {
            return posix_getgrgid(filegroup($directory))['name'];
        }
        throw new NotFoundException(sprintf('%s could not be found', $directory));
    }

    /**
    * Changes the permissions of directory
    *
    * @param string $directory filename with full path
    * @param int $mode e.g 0755 (remember 0 infront)
     * @param array $options (recursive default false)
     *  - recursive: If set to true, it will delete all contents and sub folders
    * @return bool
    */
    public static function chmod(string $directory, int $mode, array $options = []) : bool
    {
        $options += ['recursive' => false];

        if (self::exists($directory)) {
            if ($options['recursive']) {
                $files = array_diff(scandir($directory), ['.', '..']);
                foreach ($files as $filename) {
                    if (is_dir($directory . DS . $filename)) {
                        self::chmod($directory . DS . $filename, $mode, $options);
                        continue;
                    }
                    @chmod($directory . DS . $filename, $mode);
                }
            }

            return @chmod($directory, $mode);
        }
        throw new NotFoundException(sprintf('%s could not be found', $directory));
    }

    /**
     * Changes the owner of the directory
     *
     * @param string $directory filename with full path
     * @param string $user  e.g. root, www-data
     * @param array $options Support options keys are
     *  - recursive: default false. If set to true, it will delete all contents and sub folders
     * @return bool
     */
    public static function chown(string $directory, string $user, array $options = []) : bool
    {
        $options += ['recursive' => false];
        if (self::exists($directory)) {
            if ($options['recursive']) {
                $files = array_diff(scandir($directory), ['.', '..']);
                foreach ($files as $filename) {
                    if (is_dir($directory . DS . $filename)) {
                        self::chown($directory . DS . $filename, $user, $options);
                        continue;
                    }
                    @chown($directory . DS . $filename, $user);
                }
            }

            return @chown($directory, $user);
        }
        throw new NotFoundException(sprintf('%s could not be found', $directory));
    }

    /**
    * Changes the group that the directory belongs to
    *
    * @param string $directory filename with full path
    * @param string $group  e.g. root, www-data
    * @param array $options (recursive default false)
    *  - recursive: If set to true, it will delete all contents and sub folders
    * @return bool
    */
    public static function chgrp(string $directory, string $group = null, array $options = []) : bool
    {
        $options += ['recursive' => false];
        if (self::exists($directory)) {
            if ($options['recursive']) {
                $files = array_diff(scandir($directory), ['.', '..']);
                foreach ($files as $filename) {
                    if (is_dir($directory . DS . $filename)) {
                        self::chgrp($directory . DS . $filename, $group, $options);
                        continue;
                    }
                    @chgrp($directory . DS . $filename, $group);
                }
            }

            return @chgrp($directory, $group);
        }
        throw new NotFoundException(sprintf('%s could not be found', $directory));
    }
}
