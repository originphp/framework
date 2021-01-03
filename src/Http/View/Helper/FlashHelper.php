<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types = 1);
namespace Origin\Http\View\Helper;

use Origin\Http\View\TemplateTrait;

/**
 * @property \Origin\Http\View\Helper\SessionHelper $Session
 */
class FlashHelper extends Helper
{
    use TemplateTrait;
    
    protected $defaultConfig = [
        'templates' => [
            'error' => '<div class="alert alert-danger" role="alert">%s</div>',
            'success' => '<div class="alert alert-success" role="alert">%s</div>',
            'warning' => '<div class="alert alert-warning" role="alert">%s</div>',
            'info' => '<div class="alert alert-info" role="alert">%s</div>',
        ],
    ];

    public function initialize(array $config): void
    {
        $this->loadHelper('Session');
    }

    public function messages()
    {
        if (! $this->Session->exists('Flash')) {
            return null;
        }
        $output = '';

        foreach ($this->Session->read('Flash') as $message) {
            $template = $message['template'];
            $message = $message['message'];
            if (isset($this->config['templates'][$template])) {
                $output .= sprintf($this->config['templates'][$template], $message);
            }
        }
        $this->Session->delete('Flash');

        return $output;
    }
}
