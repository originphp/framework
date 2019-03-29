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

namespace Origin\View;

use Origin\Exception\Exception;
use Origin\Core\Inflector;

/**
 * Templater
 * This has now been rewritten and works differently.
 */


class Templater
{
    /**
     * Wether to allow empty data.
     *
     * @var bool
     */
    protected $templates = [];

    public function __construct(array $templates = [])
    {
        $this->templates = $templates;
    }

    /**
     * This will fetch template and merge the data into it, if there is no key then the value
     * is set to null.
     *
     * @param string $name
     * @param array $data
     * @return void
     */
    public function format(string $name, array $data)
    {
        if (!isset($this->templates[$name])) {
            throw new Exception("Template '{$name}' does not exist");
        }
        
        $template = $this->templates[$name];
        
        if (preg_match_all('/\{([a-z0-9_]+)\}/i', $template, $matches)) {
            foreach ($matches[1] as $placeholder) {
                $value = isset($data[$placeholder])?$data[$placeholder]:null;
                $template = str_replace("{{$placeholder}}", $value, $template);
            }
        }
        return $template;
    }


    /**
     * Loads templates from a file in the config folder
     *
     * $templater->load('templates-pagination');
     *
     *  return [
     *      'link' => '<a href="{url}">{text}</a>'
     *   ];
     *
     * You can also use dot notation which will load from the plugin folder.
     * $templater->load('MyPlugin.templates-pagination');
     * @param string $name
     * @return void
     */
    public function load(string $name)
    {
        $filename = CONFIG . DS . $name . '.php';
        list($plugin, $name) = pluginSplit($name);
        if ($plugin) {
            $filename = PLUGINS . DS . Inflector::underscore($plugin) . DS . 'config' . DS . $name . '.php';
        }

        if (file_exists($filename)) {
            $return = include $filename;
            if (is_array($return)) {
                foreach ($return as $key => $value) {
                    $this->templates[$key] = $value;
                }
                return true;
            }
        }
        pr($filename);
        throw new Exception("'config/{$name}.php' does not exist or does not return an array.");
    }

    /**
    * Sets templates
    *
    * @param array $templates
    * @return void
    */
    public function set(array $templates)
    {
        foreach ($templates as $name => $template) {
            $this->templates[$name] =  $template;
        }
    }

    /**
    * Gets template
    *
    * @param string|null $template
    * @return array|string|null
    */
    public function get($template = null)
    {
        if ($template === null) {
            return $this->templates;
        }
        if (isset($this->templates[$template])) {
            return $this->templates[$template];
        }
        return null;
    }
}
