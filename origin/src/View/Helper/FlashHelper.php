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

use Origin\Core\Session;
use Origin\View\TemplateTrait;

class FlashHelper extends Helper
{
    use TemplateTrait;
    
    public $defaultConfig = array(
    'templates' => array(
      'error' => '<div class="alert alert-danger" role="alert">%s</div>',
      'success' => '<div class="alert alert-success" role="alert">%s</div>',
      'warning' => '<div class="alert alert-warning" role="alert">%s</div>',
      'info' => '<div class="alert alert-info" role="alert">%s</div>',
    ),
  );

    public function messages()
    {
        if (!Session::check('Message')) {
            return null;
        }
        $output = '';

        foreach (Session::read('Message') as $template => $messages) {
            if (isset($this->config['templates'][$template])) {
                foreach ($messages as $message) {
                    $output .= sprintf($this->config['templates'][$template], $message);
                }
            }
        }
        Session::delete('Message');

        return $output;
    }
}
