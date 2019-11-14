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
declare(strict_types = 1);
namespace Origin\Mailbox;

use Exception;
use Origin\Model\Entity;
use Origin\Mailer\Mailer;
use Origin\Core\HookTrait;
use Origin\Model\ModelRegistry;
use Origin\Configurable\StaticConfigurable as Configurable;
use Origin\Core\Resolver;

class Mailbox
{
    use HookTrait, Configurable;
    /**
     * Holds the mailbox routes
     *
     * example ['/^support@/i' => 'Support']
     *
     * @var array
     */
    protected static $routes = [];

    /**
     * Inbound email id (not email message id)
     *
     * @var int
     */
    protected $id;

    /**
     * Mail object created when mailbox is dispatched.
     *
     * Similar to how a request is placed in controller
     *
     * @var \Origin\Mailbox\Mail
     */
    protected $mail;

    /**
     * Inbound Email Model
     *
     * @var \Origin\Mailbox\InboundEmail
     */
    protected $InboundEmail;

    public function __construct()
    {
        $this->InboundEmail = ModelRegistry::get('InboundEmail', ['className' => InboundEmail::class]);
        $this->executeHook('initialize');
    }

    /**
     * Bounces a message using a mailer, the \Origin\Mailbox\Mail object will be passed
     * to the mailer.
     *
     * @param \Origin\Mailer\Mailer $mailer
     * @return void
     */
    public function bounce(Mailer $mailer) : void
    {
        $this->InboundEmail->setStatus($this->id, 'bounced');
        $mailer->dispatchLater($this->mail);
    }

    /**
     * Dispatches the message to the mailbox
     *
     * @param \Origin\Model\Entity $message
     * @return void
     */
    public function dispatch(Entity $message) : bool
    {
        $result = false;

        $this->id = $message->id;
        $this->InboundEmail->setStatus($this->id, 'processing');

        $this->mail = new Mail($message->message);
        
        try {
            $this->executeHook('startup');
            $this->process();
            $this->InboundEmail->setStatus($this->id, 'delivered');
            $result = true;
        } catch (Exception $exception) {
            $this->InboundEmail->setStatus($this->id, 'failed');
        }
        
        $this->executeHook('shutdown');

        return $result;
    }

    /**
     * Registers a route for the mailboxes
     *
     * Examples :
     * - /^support@/i
     * - /@replies\./i
     * - /reply-(.+)@reply.example.com
     *
     * @param string $regex /^support@/i , /@replies\./i
     * @param string $mailbox name of the mailbox in studly caps without Mailbox prefix
     * @return void
     */
    public static function route(string $regex, string $mailbox) : void
    {
        static::$routes[$regex] = $mailbox;
    }

    /**
     * Gets the routes for the Mailboxes
     *
     * @return array
     */
    public static function routes(string $regex = null) : ?array
    {
        if ($regex === null) {
            return static::$routes;
        }

        return static::$routes[$regex] ?? null;
    }

    /**
    * Detects a route for a message
    *
    * @param string $message
    * @return string|null
    */
    public static function detect(string $message) : ?string
    {
        $mail = new Mail($message);
        foreach (static::routes() as $route => $mailbox) {
            foreach ((array) $mail->to as $address) {
                if (preg_match($route, $address)) {
                    return Resolver::className($mailbox .'Mailbox', 'Mailbox');
                }
            }
        }

        return null;
    }
}
