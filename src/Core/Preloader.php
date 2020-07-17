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

use ReflectionClass;

/**
 * Preloading Class
 *
 * In PHP.ini set
 *
 *  opcache.preload=/var/www/config/preload.php
 *
 * Create the the preload.php file.
 *
 *  $preloader = new Preloader();
 *  $preloader->addDirectory('/var/www/vendor/originphp/framework/src/Http');
 *  $preloader->addClass(Controller::class);
 *  $preloader->ignoreClass(AuthComponent::class);
 *  $preloader->load();
 *
 *
 */
class Preloader
{
    /**
     * @var array
     */
    private $files = [];

    /**
     * @var array
     */
    private $ignore = [];

    /**
     * Default name for ignore file
     *
     * @param string $config
     */
    private $ignoreFile = null;

    public function __construct(array $config = [])
    {
        $this->ignoreFile = $config['ignoreFile'] ?? '.preloadignore';
    }

    /**
     * Adds a directory for preloading
     *
     * @param string $path
     * @param boolean $recursive
     * @return void
     */
    public function addDirectory(string $path, bool $recursive = true): void
    {
        $this->loadIgnoreFile($path);

        $contents = array_diff(scandir($path), ['..', '.']);
       
        foreach ($contents as $item) {
            $file = $path . '/' . $item;
        
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $this->files[] = $file;
                continue;
            }
            if (is_dir($file) && $recursive) {
                $this->addDirectory($file, $recursive);
            }
        }
    }

    /**
     * Adds multiple directories
     *
     * @param array $directories
     * @param boolean $recursive
     * @return void
     */
    public function addDirectories(array $directories, bool $recursive = true): void
    {
        foreach ($directories as $directory) {
            $this->addDirectory($directory, $recursive);
        }
    }

    /**
     * Processes the ignore file in the directory if it exists
     *
     * @param string $path
     * @return void
     */
    private function loadIgnoreFile(string $path): void
    {
        if (! file_exists($path . '/.preloadignore')) {
            return;
        }

        $ignore = file($path . '/.preloadignore', FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);

        foreach ($ignore as $file) {
            $this->ignoreFile($path . '/' . $file);
        }
    }

    /**
     * Ignores a file
     *
     * @param string $file
     * @return void
     */
    public function ignoreFile(string $file): void
    {
        $this->ignore[] = $file;
    }

    /**
     * Ignores multiple files
     *
     * @param array $files
     * @return void
     */
    public function ignoreFiles(array $files): void
    {
        foreach ($files as $file) {
            $this->ignoreFile($file);
        }
    }

    /**
     * Ignores a class
     *
     * @param string $class Controller::class
     * @return void
     */
    public function ignoreClass(string $class): void
    {
        $this->ignore[] = (new ReflectionClass($class))->getFileName();
    }

    /**
     * Ignores classes
     *
     * @param array $classes [Controller::class]
     * @return void
     */
    public function ignoreClasses(array $classes): void
    {
        foreach ($classes as $class) {
            $this->ignoreClass($class);
        }
    }

    /**
     * Adds a class to be autoloaded
     *
     * @param string $class e.g. Controller::class
     * @return void
     */
    public function addClass(string $class): void
    {
        $this->files[] = (new ReflectionClass($class))->getFileName();
    }

    /**
     * Adds multiple classes
     *
     * @param array $classes [Controller::class]
     * @return void
     */
    public function addClasses(array $classes): void
    {
        foreach ($classes as $class) {
            $this->addClass($class);
        }
    }

    /**
     * Adds a file for preloading, note that any dependencies must all be loaded
     * or you will get an error on the server startup.
     *
     * @param string $file
     * @return void
     */
    public function addFile(string $file): void
    {
        if (file_exists($file)) {
            $this->files[] = $file;
        }
    }

    /**
     * Adds files to be preloaded
     *
     * @param array $files
     * @return void
     */
    public function addFiles(array $files): void
    {
        foreach ($files as $file) {
            $this->addFile($file);
        }
    }

    /**
     * Loads the classes for preloading, by using require once it will load all
     * dependencies
     *
     * @return array
     */
    public function load(): array
    {
        $processed = [
            'loaded' => [],
            'memory' => null
        ];

        foreach ($this->files as $file) {
            if (! in_array($file, $this->ignore)) {
                require_once($file);
                $processed['loaded'][] = $file;
            }
        }

        $processed['memory'] = (memory_get_usage() / 1024 / 1024); // MB

        return $processed;
    }
}
