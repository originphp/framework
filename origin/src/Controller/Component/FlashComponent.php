<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Controller\Component;

use Origin\Core\Session;

class FlashComponent extends Component
{
    public function error(string $message)
    {
        $this->addMessage('error', $message);
    }

    public function success(string $message)
    {
        $this->addMessage('success', $message);
    }

    public function warning(string $message)
    {
        $this->addMessage('warning', $message);
    }

    public function info(string $message)
    {
        $this->addMessage('info', $message);
    }

    public function addMessage(string $type, string $message)
    {
        $messages = [];

        if (Session::check("Message.{$type}")) {
            $messages = Session::read("Message.{$type}");
        }
        $messages[] = $message;
        Session::write("Message.{$type}", $messages);
    }
}
