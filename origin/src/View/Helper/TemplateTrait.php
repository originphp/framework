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

namespace Origin\View\Helper;

/**
 * This is the new Template Trait. Whilst building apps, despite using bootstrap, I wanted to add
 * a class to paginator, and found it was messesy, and i did not want to set html template in the controller.
 * However this requires remodifying system and how it works.
 */

trait TemplateTrait
{
    /**
     * Sets templates or gets a template or all templates
     *
     *  $allTemplates = $this->templates();
     *  $inputTemplate = $this->templates('input');
     *  $this->templates(['input'=>'<input class="form-control">']);
     *
     * @param string|array|null $template
     * @return bool|string|array
     */
    public function templates($template = null)
    {
        if (is_array($template)) {
            return $this->setTemplates($template);
        }
        return $this->getTemplate($template);
    }
    /**
     * Sets templates
     *
     * @param array $templates
     * @return void
     */
    public function setTemplates(array $templates)
    {
        foreach ($template as $name => $string) {
            $this->setTemplate($name, $template);
        }
        return true;
    }

    public function setTemplate(string $name, string $template)
    {
        $this->config['templates'][$name] =  $template;
    }
    /**
     * Gets template
     *
     * @param [type] $template
     * @return void
     */
    public function getTemplate($template = null)
    {
        if ($template === null) {
            return $this->config['templates'];
        }
        if (isset($this->config['templates'][$template])) {
            return $this->config['templates'][$template];
        }
        return false;
    }

    /*
    * Gets a templater instance.
    *
    * @return Templater
    */
    public function templater()
    {
        if (!isset($this->templater)) {
            $this->templater = new Templater();
        }

        return $this->templater;
    }
}
