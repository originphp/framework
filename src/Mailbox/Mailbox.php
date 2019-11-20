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

use Origin\Log\Log;
use Origin\Model\Entity;

use Origin\Core\Resolver;
use Origin\Core\HookTrait;
use Origin\Model\ModelTrait;
use Origin\Model\ModelRegistry;
use Origin\Core\Exception\Exception;
use Origin\Mailbox\Model\InboundEmail;
use Origin\Core\CallbackRegistrationTrait;
use Origin\Configurable\StaticConfigurable as Configurable;

class Mailbox
{
    use HookTrait, Configurable, ModelTrait, CallbackRegistrationTrait;
  
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
     * Bounced falg
     *
     * @var bool
     */
    private $bounced = false;

    /**
     * Constructor
     *
     * @param \Origin\Model\Entity $inboundEmai
     */
    public function __construct(Entity $inboundEmail)
    {
        $this->id = $inboundEmail->id;
        $this->mail = new Mail($inboundEmail->message);

        $this->InboundEmail = ModelRegistry::get('InboundEmail', ['className' => InboundEmail::class]);
        $this->executeHook('initialize', [$inboundEmail]);
    }

    /**
     * Registers a callback before an email is processed
     *
     * example
     *
     *    protected function initialize(): void
     *    {
     *        $this->loadModel('User');
     *        $this->beforeProcess('checkIsUser');
     *    }
     *
     *    protected function checkIsUser(): void
     *    {
     *        if (!$this->User->findBy(['email'=>$this->mail->from])) {
     *            $this->bounceWith(UnkownUserMailer::class);
     *        }
     *    }
     *
     * @param string $method
     * @return void
     */
    protected function beforeProcess(string $method) : void
    {
        $this->registerCallback('beforeProcess', $method);
    }

    /**
     * Registers a callback after an email is processed
     *
     * @param string $method
     * @return void
     */
    protected function afterProcess(string $method) : void
    {
        $this->registerCallback('afterProcess', $method);
    }

    /**
     * Dispatches the message to the mailbox
     *
     * @param \Origin\Model\Entity $message
     * @return bool
     */
    public function dispatch() : bool
    {
        $this->executeHook('startup');
        $result = $this->performProcessing();
        $this->executeHook('shutdown');

        return $result;
    }
    
    /**
     * Sets
     *
     * @return boolean
     */
    private function performProcessing() : bool
    {
        $this->setStatus('processing');
        try {
            $this->dispatchCallbacks('beforeProcess');

            if ($this->bounced === false) {
                $this->process();
            }

            if ($this->bounced === false) {
                $this->dispatchCallbacks('afterProcess');
            }

            if ($this->bounced === false) {
                $this->setStatus('delivered');
            }

            return true;
        } catch (\Exception $exception) {
            $this->setStatus('failed');
            Log::error($exception->getMessage());
            $this->executeHook('onError', [$exception]);
        }
        
        return false;
    }
    /**
     * Dispatches the callbacks for the Mailbox
     *
     * @param string $callback
     * @return void
     */
    private function dispatchCallbacks(string $callback) : void
    {
        foreach ($this->registeredCallbacks($callback) as $method => $options) {
            if (method_exists($this, $method)) {
                if ($this->$method() === false or $this->bounced) {
                    break;
                }
            }
        }
    }

    /**
     * Bounces a message with a mailer, the \Origin\Mailbox\Mail object will be passed
     * to the mailer. This will also halt futher processing
     *
     * @param \Origin\Mailer\Mailer $mailer UnkownUserMailer::class or 'UnkownUser' or 'App\Mailer\UnownUserMailer'
     * @return bool result of dispatchLater
     */
    protected function bounceWith(string $mailerClass) : bool
    {
        $this->setStatus('bounced');
       
        $this->bounced = true;

        $className = Resolver::className($mailerClass, 'Mailer');

        if ($className) {
            return (new $className())->dispatchLater($this->mail);
        }
        throw new Exception('Missing class ' . $mailerClass);
    }

    /**
     * Adds a route for a mailbox using a regex pattern
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
     * @return mixed
     */
    public static function routes(string $regex = null)
    {
        if ($regex === null) {
            return static::$routes;
        }

        return static::$routes[$regex] ?? null;
    }

    /**
    * Finds the mailbox by matching recipient email address to route
    *
    * @param array $recipients
    * @return string|null
    */
    public static function mailbox(array $recipients) : ?string
    {
        foreach (static::routes() as $route => $mailbox) {
            foreach ($recipients as $address) {
                if (preg_match($route, $address)) {
                    return Resolver::className($mailbox, 'Mailbox', 'Mailbox');
                }
            }
        }

        return null;
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
     * Gets the mail object
     *
     * @return \Origin\Mailbox\Mail
     */
    public function mail() : Mail
    {
        return $this->mail;
    }
}
