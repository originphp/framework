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

class HtmlHelper extends Helper
{
    protected $templates = [
    'a' => '<a href="{url}"{attributes}>{text}</a>',
  ];

    public function link($text, $url, array $attributes = [])
    {
        $options = [
            'text' => $text,
            'url' => Router::url($url),
            'attributes' => $this->attributesToString($attributes),
            ];

        return $this->templater()->format($this->templates['a'], $options);
    }
}
