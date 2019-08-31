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
namespace Origin\Mailer;

/**
* To set values in the view set public properties in the execute method
*
* class SendWelcomeEmailMailer extends Mailer
* {
*    public function execute(Entity $user)
*    {
*        $this->user = $user; // this will become visible in the view
*
*        $this->mail([
*            'to' => $user->email,
*           'subject' => 'This is a test email',
*       ]);
*   }
* }

 */

abstract class Mailer
{
    /**
     * You can set the default settings to be used by
     * each mailer (This can be overidden in any Mailer)
     *
     * The following keys can be used:
     *   - from: either ['email'] or ['email'=>'name']
     *   - cc: an array of emails in ['email'] or ['email'=>'name']
     *   - bcc: an array of emails in ['email'] or ['email'=>'name']
     *   - sender: ['email'] or ['email'=>'name']
     *   - replyTo: ['email'] or ['email'=>'name']
     * @var array
     */
    public $defaults = [];

    /**
     * Name of the mailer folder where templates are. Only set this
     * if you want something custom.
     *
     * e.g SendUserWelcomeEmail,MyPlugin.SendWelcomeEmail, Orders/SendUserWelcomeEmail
     *
     * @var string
     */
    public $folder = null;

    /**
     * Email account to use
     *
     * @var string
     */
    public $account = 'default';

    /**
     * The default format to use. Its best to send both html and text.
     *
     * @var string both,text,html
     */
    public $format = 'both';

    /**
     * The layout to use for HTML emails
     *
     * @var boolean
     */
    public $layout = false;

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var array
     */
    protected $attachments = [];

    /**
     *
     * @var array
     */
    protected $options = [];

    /**
     * Holds the Email Utility
     *
     * @var \Origin\Mailer\Email;
     */
    protected $email = null;

    /**
     * These vars and values can be used in the emial
     *
     * @var array
     */
    protected $viewVars = [];

    public function __construct(array $config = [])
    {
        $config += ['account' => $this->account];
        $this->account = $config['account'];

        if ($this->folder === null) {
            list($namespace, $class) = namespaceSplit(get_class($this));
            $this->folder = substr($class, 0, -6);
        }
        $this->initialize($config);
    }
    /**
     * Constructor hook method, use this to avoid having to overwrite the constructor and
     * call the parent.
     *
     * @param array $config
     * @return void
     */
    public function initialize(array $config)
    {
    }

    /**
     * Startup callback
     *
     * @return void
     */
    public function startup()
    {
    }

    /**
     * Shutdown callback
     *
     * @return void
     */
    public function shutdown()
    {
    }

    /**
     * Sends an email
     *
     * @param array $options The options keys are
     *   - to: an array of emails in ['email'] or ['email'=>'name']
     *   - subject: the subject of this message
     *   - from: either ['email'] or ['email'=>'name']
     *   - cc: an array of emails in ['email'] or ['email'=>'name']
     *   - bcc: an array of emails in ['email'] or ['email'=>'name']
     *   - sender: ['email'] or ['email'=>'name']
     *   - replyTo: ['email'] or ['email'=>'name']
     * @return void
     */
    public function mail(array $options = [])
    {
        $defaults = $this->defaults;

        $defaults += [
            'to' => null,
            'subject' => null,
            'from' => null,
            'bcc' => null,
            'cc' => null,
            'sender' => null,
            'replyTo' => null,
        ];
      
        $options += $defaults;
      
        $options['headers'] = $this->headers;
        $options['attachments'] = $this->attachments;
        $options['format'] = $this->format;
        $options['account'] = env('ORIGIN_ENV') === 'test' ? 'test' : $this->account;
        $options['folder'] = $this->folder;
        $options['viewVars'] = $this->viewVars;
        $options['layout'] = $this->layout;

        $this->options = $options;

        return $this->options;
    }

    /**
     * Sets values in the email templates
     *
     * @param string|array $name key name or array
     * @param $value if key is a string set the value for this
     * @return void
     */
    public function set($name, $value = null) : void
    {
        if (is_array($name)) {
            $data = $name;
        } else {
            $data = [$name => $value];
        }

        $this->viewVars = array_merge($this->viewVars, $data);
    }

    /**
    * Dispatches the email
    *
    * @return \Origin\Mailer\Message
    */
    public function dispatch() : Message
    {
        $this->arguments = func_get_args();

        $this->startup();
        $object = $this->buildEmail();
        
        $result = $object->send();

        $this->shutdown();

        return $result;
    }

    /**
      * Dispatches the email to the mailer queue using the default connection
      *
      * @return bool
      */
    public function dispatchLater() : bool
    {
        $params = [
            'mailer' => $this,
            'arguments' => func_get_args(),
        ];

        return (new MailerJob())->dispatch($params);
    }

    /**
     * Previews the message with headers
     *
     * @return \Origin\Mailer\Message
     */
    public function preview() : Message
    {
        $this->arguments = func_get_args();

        $this->startup();
        $object = $this->buildEmail(true);

        $result = $object->send();
        $this->shutdown();

        return $result;
    }

    /**
     *
     *
     * @param boolean $debug
     * @return void
     */
    private function buildEmail(bool $debug = false)
    {
        $properties = array_keys(get_object_vars($this));
     
        $this->execute(...$this->arguments);
        $propertiesNow = array_keys(get_object_vars($this));
        $newProperties = array_diff($propertiesNow, $properties);

        # Add public properties as viewVars
        foreach ($newProperties as $name) {
            $this->viewVars[$name] = $this->$name;
        }
        $this->options['viewVars'] = $this->viewVars;
   
        $message = new EmailBuilder($this->options);

        return $message->build($debug);
    }

    /**
     * Adds an attachment to the mailer
     *
     * @param string $file file with full path e.g. /data/invoices/inv-12345.pdf
     * @param string $name invoice-12345.pdf
     * @return void
     */
    public function attachment(string $file, string $name = null) : void
    {
        if ($name === null) {
            $name = basename($file);
        }
        $this->attachments[$file] = $name;
    }

    /**
     * Sets or gets the attachments
     *
     * @param array $attachments an array ['/tmp/filename','/images/logo.png'=>'Your Logo.png']
     * @return array
     */
    public function attachments(array $attachments = null) : array
    {
        if ($attachments === null) {
            return $this->attachments;
        }

        return $this->attachments = $attachments;
    }

    /**
     * Sets the header
     *
     * @param string $name e.g. 'Disposition-Notification-To'
     * @param string $value e.g. 'header value'
     * @return void
     */
    public function header(string $name, string $value) : void
    {
        $this->headers[$name] = $value;
    }

    /**
     * Sets or gets the headers
     *
     * @param array $headers an array ['Disposition-Notification-To' => 'me@example.com']
     * @return array
     */
    public function headers(array $headers = null) : array
    {
        if ($headers === null) {
            return $this->headers;
        }

        return $this->headers = $headers;
    }
}
