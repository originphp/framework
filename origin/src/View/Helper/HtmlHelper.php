<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright     Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\View\Helper;

use Origin\Core\Router;
use Origin\View\TemplateTrait;
use Origin\Core\Plugin;
use Origin\Core\Inflector;
use Origin\Exception;
use Origin\Exception\NotFoundException;

class HtmlHelper extends Helper
{
    use TemplateTrait;
    
    protected $defaultConfig = [
        'templates' => [
            'a' => '<a href="{url}"{attributes}>{text}</a>',
            'css' => '<link rel="stylesheet" type="text/css" href="{url}" />',
            'js' => '<script type="text/javascript" src="{url}"></script>',
            'img' => '<img src="{src}"{attributes}>'
        ]
    ];

    /**
     * Generates a link
     *
     * @param string $text
     * @param array|string $url
     * @param array $attributes
     * @return void
     */
    public function link($text, $url, array $attributes = [])
    {
        $options = [
            'text' => $text,
            'url' => Router::url($url),
            'attributes' => $this->attributesToString($attributes),
            ];

        return $this->templater()->format('a', $options);
    }

    /**
     * Generates Stylesheet link or styles block for plugin css
     * $html->css('form'); // /css/form.css
     * $html->css('/assets/css/form.css');
     * html->css('Myplugin.form.css'); // remember to include extension
     * @param string $path
     * @return string
     */
    public function css(string $path)
    {
        return $this->asset($path, ['ext'=>'css']);
    }

    /**
     * Image tag, must provide extension, if it does not start with / it will
     * assume /img/ folder
     *
     * @param string $image
     * @param array $attributes
     * @return void
     */
    public function img(string $image, array $attributes=[])
    {
        if ($image[0] !== '/') {
            $image .= '/img/' . $image;
        }
        $options = [
            'src' => $image,
            'attributes' => $this->attributesToString($attributes),
            ];

        return $this->templater()->format('img', $options);
    }

    /**
        * Generates script link or block for plugin js
        * $html->js('form'); // /js/form.js
        * $html->js('/assets/js/form.js');
        * html->js('Myplugin.form.js'); // remember to include extension
        * @param string $path
        * @return string
        */
    public function js(string $path)
    {
        return $this->asset($path, ['ext'=>'js']);
    }

    private function asset($path, $options)
    {
        // without path $html->css('https://example.com/something.css');
        if (strpos($path, '://') !== false) {
            return $this->templater()->format($options['ext'], ['url'=>$path]);
        }
        $plugin = null;
        list($a, $b) = pluginSplit($path);
        if (Plugin::loaded($a) === true) {
            $plugin = $a;
            $path = $b;
        }
        $length = strlen('.' . $options['ext']);
        if (substr($path, -$length) !== '.' . $options['ext']) {
            $path .= '.' . $options['ext'];
        }
        // without path $html->css('form');
        if (!$plugin and $path[0] !== '/') {
            $path = '/' .$options['ext'] . '/' . $path;
        }

        if ($plugin) {
            $filename = PLUGINS . DS . Inflector::underscore($plugin) . DS . 'public' . DS . $options['ext'] . DS . $path;
            if ($options['ext']==='js') {
                return '<script>' .$this->loadFile($filename) . '</script>';
            }
            return '<style>' .$this->loadFile($filename) . '</styles>';
        }
        return $this->templater()->format($options['ext'], ['url'=>$path]);
    }

    protected function loadFile(string $filename)
    {
        if (!file_exists($filename)) {
            throw new NotFoundException($filename . ' not found.');
        }
        return file_get_contents($filename);
    }
}
