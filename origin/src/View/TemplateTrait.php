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

/**
 * This is the new Template Trait. Whilst building apps, despite using bootstrap, I wanted to add
 * a class to paginator, and found it was messesy, and i did not want to set html template in the controller.
 * However this requires remodifying system and how it works.
 * It assumes that Config trait is also used and therefore defaultConfig['templates] is available
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
            return $this->setTemplate($template);
        }
        return $this->getTemplate($template);
    }
    
    /**
     * Sets a template or templates at runtime
     *
     * @param array $array ['input'=>'<input class="form-control">'])
     * @return void
     */
    public function setTemplate($template)
    {
        $this->templater()->set($template);
    }

    /**
     * Gets a template at run time
     *
     * @param string $template
     * @return string|null
     */
    public function getTemplate(string $template= null)
    {
        return $this->templater()->get($template);
    }

    /**
    * Gets the templater object if it exists, or it will create one
    * if the templates key is a string, then it will fetch the default templates since they
    * were overwritten and load templates from file
     *
     * @return Templater
     */
    public function templater()
    {
        if (!isset($this->templater)) {
            $this->templater = new Templater();
            $templates = $this->config('templates');
            if (is_array($templates)) {
                $this->templater->set($templates);
            } else {
                $this->templater->set($this->defaultConfig['templates']);
                $this->templater->load($templates);
            }
        }

        return $this->templater;
    }
}
