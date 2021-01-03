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
namespace Origin\Http\Controller\Component;

/**
 * @property \Origin\Http\Controller\Component\SessionComponent $Session
 */
class FlashComponent extends Component
{
    public function initialize(array $config): void
    {
        $this->loadComponent('Session');
    }
    
    public function error(string $message): void
    {
        $this->addMessage('error', $message);
    }

    public function success(string $message): void
    {
        $this->addMessage('success', $message);
    }

    public function warning(string $message): void
    {
        $this->addMessage('warning', $message);
    }

    public function info(string $message): void
    {
        $this->addMessage('info', $message);
    }

    public function addMessage(string $type, string $message): void
    {
        $messages = [];

        if ($this->Session->exists('Flash')) {
            $messages = $this->Session->read('Flash');
        }
        $messages[] = [
            'template' => $type,
            'message' => $message
        ];
        $this->Session->write('Flash', $messages);
    }
}
