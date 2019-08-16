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

/**
 *  use Origin\Core\Autoloader;
 *  require ORIGIN . '/src/Core/Autoloader.php';
 *  $autoloader = new Autoloader(__DIR__);
 *
 *  or get a singleton instance
 *
 *  $autoloader = Autoloader::instance();
 *  $autoloader->directory(ROOT);  // this sets the project folder
 *
 * Tell the Autoloader where to find files for namespaces that you will use.
 *
 *  $autoloader->addNamespaces(array(
 *  	'App' => 'src',
 *  	'Origin' => 'origin/src/'
 *  ));
 *
 * $autoloader->register();
 */

namespace Origin\Core;

class Autoloader
{
    /**
     * Singleton Instance of the Autoloader
     *
     * @var Autoloader
     */
    protected static $instance = null;

    /**
     * Map of prefixes.
     *
     * @var array ('Psr\Log\'=>'/var/www/..')
     */
    protected $prefixes = [];

    /**
     * Project diretory
     *
     * @var string
     */
    protected $directory = null;

    /**
     * Returns a single instance of the object
     *
     * @return \Origin\Core\Autoloader
     */
    public static function instance() : Autoloader
    {
        if (static::$instance === null) {
            static::$instance = new Autoloader();
        }

        return static::$instance;
    }

    /**
     * Constructor
     *
     * @param string $directory
     */
    public function __construct(string $directory = null)
    {
        $this->directory = $directory;
    }

    /**
     * Sets or gets the project directory
     *
     * @param string $directory
     * @return string|void
     */
    public function directory(string $directory = null)
    {
        if ($directory === null) {
            return $this->directory;
        }
        $this->directory = $directory;
    }

    /**
    * Register loader with SPL autoloader stack.
    *
    * @return boolean
    */
    public function register() : bool
    {
        return spl_autoload_register([$this, 'load']);
    }

    /**
     * Add a base directory for namespace prefix.
     *
     * $Autoloader->addNamespace('Origin\Framework','origin/src/');
     *
     * @param string $prefix        the namespace prefix
     * @param string $baseDirectory a base directory where classes are for namespace
     * @return void
     */
    public function addNamespace(string $prefix, string $baseDirectory) : void
    {
        $prefix = trim($prefix, '\\').'\\';
        $path = rtrim($baseDirectory, DS).'/';
        $this->prefixes[$prefix] = $this->directory . DS . $path;
    }

    /**
     * Add base directories for namespace prefixes.
     *
     *  $Autoloader->addNamespaces(array(
     *     	'Origin' => 'origin/src/'
     *      'Origin\\Test' => 'origin/tests/'
     *    ));
     *
     * @param string $namespaces array ((namespacePrefix => baseDirectory))
     * @return void
     */
    public function addNamespaces(array $namespaces) : void
    {
        foreach ($namespaces as $namespace => $baseDirectory) {
            $this->addNamespace($namespace, $baseDirectory);
        }
    }

    /**
     * Loads the class for the autoloader.
     *
     * @param string $class
     * @return string|null
     */
    public function load(string $class) : ?string
    {
        $prefix = $class;
    
        // Deal with Namespaces
        while (false !== $pos = strrpos($prefix, '\\')) {
            $prefix = substr($class, 0, $pos + 1);
     
            $relativeClass = substr($class, $pos + 1);

            if (isset($this->prefixes[$prefix])) {
                $filename = $this->prefixes[$prefix].str_replace('\\', DS, $relativeClass).'.php';
                if ($this->requireFile($filename)) {
                    return $filename;
                }
            }

            $prefix = rtrim($prefix, '\\');
        }

        return null;
    }

    /**
     * Loads the required file.
     * @todo add caching to reduce io
     *
     * @param string $filename
     * @return bool
     */
    protected function requireFile(string $filename) : bool
    {
        if (file_exists($filename)) {
            require $filename;

            return true;
        }

        return false;
    }
}
