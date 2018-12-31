<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
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
 *  require ORIGIN . DS . 'src' . DS .'Lib' .DS .'Autoloader.php';
 *  $Autoloader = new Autoloader($projectDirectory);.
 *
 * For non namespace
 * $Autoloader->addDirectories(array(
 *   'src' . DS . 'Model',
 *   'src' . DS . 'View',
 *   'src' . DS . 'Controller',
 *  ));
 *
 * Tell the Autoloader where to find files for namespaces that you will use.
 *
 *  $Autoloader->addNamespaces(array(
 *  	'App' => 'src',
 *  	'Framework' => 'origin/src/'
 *  ));
 *
 * $Autoloader->register();
 */

namespace Origin\Core;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Autoloader
{
    /**
     * Map of prefixes.
     *
     * @var array ('Psr\Log\'=>'/var/www/..')
     */
    protected $prefixes = array();

    /**
     * Map of non namespaced files.
     *
     * @var array
     */
    protected $files = array();

    protected $directory = null;

    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    /**
     * Register loader with SPL autoloader stack.
     */
    public function register()
    {
        spl_autoload_register(array($this, 'load'));
    }

    /**
     * Searches recrusively a folder for PHP class files, they must start with a capital letter.
     *
     * @param string $directory
     *
     * @return array a list of class files
     */
    public function scanDirectory(string $directory)
    {
        if (!file_exists($directory)) {
            return false;
        }
        $rdi = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
        $rit = new RecursiveIteratorIterator($rdi, RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($rit as $file) {
            $filename = $file->getFilename();
            if (preg_match('/^[A-Z](.*)\.php/', $filename)) {
                $class = basename($filename, '.php');
                $this->files[$class] = $file->getPathname();
            }
        }
    }

    /**
     * Recursively search an array of base directories for classes.
     *
     *  $Autoloader->addDirectories('src' . DS . 'Traits'));
     *
     * @param string $baseDirectory 'src/Traits'
     */
    public function addDirectory(string $baseDirectory)
    {
        $this->scanDirectory($this->directory.DS.$baseDirectory);
    }

    /**
     * Recursively search an array of base directories for classes.
     *
     *  $Autoloader->addDirectories(array(
     *     	 'src' . DS . 'Console',
     *     	 'src' . DS . 'Controller',
     *     	 'src' . DS . 'Model',
     *     	 'src' . DS . 'View',
     *   	   'src' . DS . 'Lib'
     *     ));
     *
     * @param array $baseDirectories
     */
    public function addDirectories(array $baseDirectories)
    {
        foreach ($baseDirectories as $baseDirectory) {
            $this->addDirectory($baseDirectory);
        }
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
     *     	'Origin\Framework' => 'origin/src/'
     *      'Origin\Framework\Tests' => 'origin/tests/'
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
     * Checks if a class name belongs to a namespace prefix.
     *
     * @param string $class e.g Autoloader or Origin/Framework/Autoloader
     *
     * @return bool
     */
    protected function isNamespace(string $class)
    {
        return strrpos($class, '\\') == true;
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

        // Deal with None Namespaces
        if (!$this->isNamespace($class)) {
            if (isset($this->files[$class])) {
                return $this->requireFile($this->files[$class]);
            }

            return false;
        }

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
     * Loads the required file. PSR standards say no error should be thrown but
     * it is silly to check if a file exists on every single page log on a production
     * server.
     *
     * @param string $filename
     *
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
