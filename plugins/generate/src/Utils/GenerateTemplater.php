<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Generate\Utils;

class GenerateTemplater
{

    /**
     * Builds blocks using data['blocks'] and array of data
     *
     * @param string $template
     * @return void
     */
    protected function buildBlocks(string $template, array $data = [])
    {
        if (preg_match_all('/<RECORDBLOCK>\n(.*?)<\/RECORDBLOCK>/s', $template, $matches)) {
            foreach ($matches[1] as $index => $block) {
                $recordBlock = '';
                foreach ($data['blocks'] as $field => $blockData) {
                    $recordBlock .= $this->format($block, $blockData);
                }
                $template = str_replace($matches[0][$index], $recordBlock, $template);
            }
        }
        return $template;
    }
    public function generate(string $name, array $data)
    {
        $template = $this->loadTemplate($name);
        $template =  $this->buildBlocks($template, $data);

        return $this->format($template, $data);
    }
    protected function format(string $template, array $data=[])
    {
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $template = str_replace('%' . $key . '%', $value, $template);
            }
        }
        return $template;
    }
    public function loadTemplate(string $name)
    {
        return file_get_contents(PLUGINS . DS . 'generate' . DS . 'src' . DS  .'Template'. DS . $name . '.tpl');
    }
}
