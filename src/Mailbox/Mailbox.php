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
     * Inbound email id (not email message id)
     *
     * @var int
     */
    protected $id;

    /**
     * Mail object created when mailbox is dispatched.
     *
     * @internal Similar to how a request is placed in controller
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

    /**
     * Holds the mailbox routes
     *
     * example ['/^support@/i' => 'Support']
     *
     * @var array
     */
    private static $routes = [];

    /**
     * Constructor
     *
     * @param \Origin\Model\Entity $inboundEmai
     */
    public function __construct(Entity $inboundEmail)
    {
        if ($inboundEmail) {
            $this->id = $inboundEmail->id;
            $this->mail = new Mail($inboundEmail->message);
        }
       
        $this->InboundEmail = ModelRegistry::get('InboundEmail', ['className' => InboundEmail::class]);
        $this->executeHook('initialize', [$inboundEmail]);
    }

    /**
     * Dispatches the message to the mailbox
     *
     * @param \Origin\Model\Entity $message
     * @return void
     */
    public function dispatch() : bool
    {
        try {
            $this->setStatus('processing');
            $this->executeHook('startup');
            $this->process();
            $this->setStatus('delivered');
            $this->executeHook('shutdown');
            return true;
        } catch (Exception $exception) {
            $this->setStatus('failed');
        }
        
        return false;
    }

  
    /**
     * Bounces a message using a mailer, the \Origin\Mailbox\Mail object will be passed
     * to the mailer.
     *
     * @param \Origin\Mailer\Mailer $mailer
     * @return bool result of dispatchLater
     */
    public function bounce(Mailer $mailer) : bool
    {
        $this->setStatus('bounced');
        return $mailer->dispatchLater($this->mail);
    }

    /**
       * Sets the status for the inbound email
       *
       * @param string $status processing/delivered/failed/bounced
       * @return void
       */
    private function setStatus(string $status) : void
    {
        $this->InboundEmail->setStatus($this->id, $status);
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
    * Detects which mailbox for the message
    *
    * @param string $message
    * @return string|null
    */
    public static function detect(string $message) : ?string
    {
        $mail = new Mail($message);
        foreach (static::routes() as $route => $mailbox) {
            foreach ((array) $mail->recipients() as $address) {
                if (preg_match($route, $address)) {
                    return Resolver::className($mailbox .'Mailbox', 'Mailbox');
                }
            }
        }

        return null;
    }
}
