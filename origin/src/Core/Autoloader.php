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
 *  namespace Origin\Core;
 *  require ORIGIN . DS . 'src' . DS .'Core' .DS .'Autoloader.php';
 *  $Autoloader = new Autoloader($projectDirectory);
 *  or
 *  $Autoloader = Autoloader::init();
 *  $Autoloader->setFolder(ROOT);
 *
 * Tell the Autoloader where to find files for namespaces that you will use.
 *
 *  $Autoloader->addNamespaces(array(
 *  	'App' => 'src',
 *  	'Origin' => 'origin/src/'
 *  ));
 *
 * $Autoloader->register();
 */

namespace Origin\Core;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Autoloader
{
    protected static $instance;
    /**
     * Map of prefixes.
     *
     * @var array ('Psr\Log\'=>'/var/www/..')
     */
    protected $prefixes = array();

    /**
     * Returns a single instance of the object
     *
     * @return void
     */
    public static function init()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Project diretory
     *
     * @var string
     */
    protected $directory = null;

    public function __construct(string $directory = null)
    {
        if ($directory) {
            $this->directory = $directory;
        }
    }

    /**
     * Sets the folder
     *
     * @param string $directory
     * @return void
     */
    public function setFolder(string $directory)
    {
        $this->directory = $directory;
    }

    /**
     * Register loader with SPL autoloader stack.
     */
    public function register()
    {
        return spl_autoload_register(array($this, 'load'));
    }

    /**
     * Add a base directory for namespace prefix.
     *
     * $Autoloader->addNamespace('Origin\Framework','origin/src/');
     *
     * @param string $prefix        the namespace prefix
     * @param string $baseDirectory a base directory where classes are for namespace
     */
    public function addNamespace(string $prefix, string $baseDirectory)
    {
        $prefix = trim($prefix, '\\').'\\';

        $baseDirectory = rtrim($baseDirectory, DS).'/';

        $this->prefixes[$prefix] = $this->directory.DS.$baseDirectory;
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
     */
    public function addNamespaces(array $namespaces)
    {
        foreach ($namespaces as $namespace => $baseDirectory) {
            $this->addNamespace($namespace, $baseDirectory);
        }
    }

    /**
     * Loads the class for the autoloader.
     *
     * @param string $class
     *
     * @return bool
     */
    public function load(string $class)
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
     
        return false;
    }

    /**
     * Loads the required file.
     * @todo add caching to reduce io
     *
     * @param string $filename
     * @return bool
     */
    protected function requireFile(string $filename)
    {
        if (file_exists($filename)) {
            require $filename;

            return true;
        }

        return false;
    }
}
