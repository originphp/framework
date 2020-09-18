<?php
namespace Origin\Http\View\Helper;

use RuntimeException;
use Origin\Cache\Cache;
use Origin\Core\Config;
use Origin\Core\PhpFile;

/**
 * Bundle Helper for bundling JS and CSS files from the public folder to reduce the number of requests
 * on the server and for faster loading. Note. This is not designed to work with plugin files.
 *
 * Create a config/bundles.php, paths will be prefixed with the public folder e.g. /var/www/public/ and js or css if the
 * file does not start with a /
 *
 * return [
 *  'bundle.js' => [
 *      '/js/chart.js',
 *      '/js/application.js',
 *      '/js/popper.min.js',
 *      '/theme/toolkit.min.js'
 *  ],
 *  'bundle.css' => [
 *      '/theme/toolkit-inverse.css',
 *      '/css/dark.css',
 *      '/css/application.css',
 *      '/css/jquery-ui.css',
 *      '/css/fonts.css'
 *  ]
 * ];

 *
 * To configure the helper when loading the helper pass any of the options:
 *
 *  - minify: default:true. Minfifies the JS and CSS bundles be generated on each request.
 *  - js_path: default:cache_js path is releative to public
 *  - css_path: default:cache_css path is releative to public
 *
 * @internal
 *  - using name format bundle-xxxx.js creates lots of zombie files and then requires cleaning up. better to use ?version
 *  - using base_convert($timestamp, 10, 36), is squential hex and it can be confusing sometimes if it has changed
 */
class BundleHelper extends Helper
{
    protected $defaultConfig = [
        'minify' => true,
        'cache' => true,
        'js_path' => 'cache_js',
        'css_path' => 'cache_css',
    ];

    /**
     * Holds the bundle configurations
     */
    protected $bundles = [];

    /**
     * Initialization
     *
     * @return void
     */
    protected function initialize(): void
    {
        $configFile = config_path('bundle.php');
        if (file_exists($configFile)) {
            $this->bundles = (new PhpFile())->read($configFile);
        }
       
        $this->loadHelper('Html');
    }

    /**
     * Creates the STYLE tag for the bundle, in debug mode it will show actual tags of what
     * is actually being bundled.
     *
     * @param string $name
     * @return string
     */
    public function css(string $name): string
    {
        return $this->processBundle($name, 'css');
    }

    /**
     * Creates the SCRIPT tag for the bundle, in debug mode it will show actual tags of what
     * is actually being bundled.
     *
     * @param string $name
     * @return string
     */
    public function js(string $name): string
    {
        return $this->processBundle($name, 'js');
    }

    /**
     * Processes the bundle
     *
     * @param string $name
     * @param string $extension
     * @return string
     */
    private function processBundle(string $name, string $extension): string
    {
        $filename = $this->addExtension($name, $extension);
        if (! isset($this->bundles[$filename])) {
            throw new RuntimeException("Unkown bundle {$filename}");
        }

        $out = '';
        $files = $this->bundles[$filename];

        if ($this->config('raw')) {
            foreach ($files as $file) {
                $out .= $this->Html->$extension($file) . PHP_EOL;
            }

            return $out;
        }

        $files = $this->standardize($files, $extension);

        $cache = Cache::store('origin');
        $key = 'bundle-' . md5($filename);
        
        $cachedFile = WEBROOT . $this->bundledFile($filename, $extension);
   
        // get the version number
        $version = $this->config('cache') ? $cache->read($key) : false;

        // create the bundle if not exists or cache expired
        if (! file_exists($cachedFile) || ! $version) {
            $this->createBundle($files, $cachedFile, $extension);
            $version = substr(sha1(time()), 0, 7); // cache buster
            $cache->write($key, $version);
        }

        return $this->Html->$extension(
             $this->bundledFile($filename, $extension) . '?version=' . $version
        );
    }

    /**
     * Gets the budled file with path e.g. /cached_js/bundle.js
     *
     * @param string $filename
     * @param string $extension
     * @return string
     */
    private function bundledFile(string $filename, string $extension): string
    {
        $path = $this->config($extension .'_path');

        return ($path ? '/' . $path .'/' : '/') . $filename;
    }

    /**
     * Concates the files into the bundle
     *
     * @param array $files
     * @param string $cachedFile
     * @param string $extension
     * @return void
     */
    private function createBundle(array $files, string $cachedFile, string $extension): void
    {
        $folder = dirname($cachedFile);
        if (! is_dir($folder)) {
            @mkdir($folder, 0775, true);
        }

        $tmpfile = tmp_path(uniqid().'.tmp');
        
        $contents = '';
        foreach ($files as $file) {
            $contents .= $this->loadFile(WEBROOT . $file) . PHP_EOL;
        }

        file_put_contents($tmpfile, $contents, LOCK_EX);
        rename($tmpfile, $cachedFile);
    }

    /**
     * Loads the file and minifys contents if set in options
     *
     * @param string $filename
     * @return string
     */
    private function loadFile(string $filename): string
    {
        if ($this->config('minify')) {
            return $this->minify(file_get_contents($filename));
        }

        return file_get_contents($filename);
    }

    /**
     * Adds the directory prefix
     *
     * @param array $files
     * @param string $extension
     * @return array
     */
    private function standardize(array $files, string $extension): array
    {
        foreach ($files as &$file) {
            if ($file[0] !== '/') {
                $file = '/' . $extension . '/' . $file;
            }
        }

        return $files;
    }

    /**
     * Adds the extension to the bundle name
     *
     * @param string $name
     * @param string $extension
     * @return string
     */
    private function addExtension(string $name, string $extension): string
    {
        if (substr($name, - (strlen($extension) + 1)) === '.' . $extension) {
            return $name;
        }

        return $name . '.' . $extension;
    }

    /**
     * Mini minifier
     *
     * Complex javascript code can be broken by filtering comments e.g. jquery so
     * the comment remover below is a simple, not intented to catch every use case.
     *
     * Single line comments must include a space after the //
     *
     * @param string $string
     * @return string
     */
    private function minify(string $string): string
    {
        return preg_replace(
            [
                '/\/\*[\s\S]*?\*\//', // remove multiline comments  #/\*.*?\*/#sm
                '/\/\/ .*/', // remove single line comment (safe)
                '/[^\S ]+/s', // remove all whitespaces except spaces
                '/(\s)+/s' // remove repeated spaces
            ],
            [
                '',
                '',
                '',
                '\\1'
            ],
        $string);
    }
}
