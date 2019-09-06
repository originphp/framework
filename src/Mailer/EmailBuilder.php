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

use Origin\Utility\Html;
use Origin\Utility\Inflector;
use Origin\Exception\Exception;

/**
 * This class builds an configured email from an arra for Mailer.
 */
class EmailBuilder
{
    protected $options = null;

    /**
     * Rendered content
     *
     * @var string
     */
    protected $content = null;

    /**
     * Vars to be injected
     *
     * @var array
     */
    protected $viewVars = [];

    public function __construct(array $options)
    {
        $options += [
            'to' => null,
            'subject' => null,
            'from' => null,
            'bcc' => null,
            'cc' => null,
            'sender' => null,
            'replyTo' => null,
            'format' => null,
            'folder' => null,
            'headers' => null,
            'attachments' => null,
            'viewVars' => null, ];
      
        $this->options = $options;
    }

    /**
     * Builds the email object
     *
     * @param boolean $debug set to true to ensure send does not send emails
     * @return \Origin\Mailer\Email
     */
    public function build(bool $debug = false) : Email
    {
        $account = ($debug === true) ? ['engine' => 'Test'] : $this->options['account'];
      
        $this->message = new Email($account);
     
        extract($this->options);
        
        $this->message->format($format);

        if ($headers) {
            foreach ($headers as $key => $value) {
                $this->message->addHeader($key, $value);
            }
        }

        foreach (['to','from','sender','replyTo'] as $key) {
            if ($$key) {
                foreach ((array) $$key as $email => $name) {
                    $this->message->$key(...$this->buildArguments($email, $name));
                }
            }
        }

        if ($subject) {
            $this->message->subject($subject);
        }

        if ($cc) {
            foreach ((array) $cc as $email => $name) {
                $this->message->addCc(...$this->buildArguments($email, $name));
            }
        }
    
        if ($bcc) {
            foreach ((array) $bcc as $email => $name) {
                $this->message->addBcc(...$this->buildArguments($email, $name));
            }
        }
        
        if ($attachments) {
            foreach ($attachments as $filename => $name) {
                if (is_int($filename)) {
                    $filename = $name;
                    $name = null;
                }
                $this->message->addAttachment($filename, $name);
            }
        }
  
        $this->render();
       
        return $this->message;
    }

    /**
     * Renders the message using the templates, if no text template is found
     * it will create a version from the
     *
     * @return void
     */
    protected function render() : void
    {
        if (in_array($this->options['format'], ['html','both'])) {
            $this->renderHtmlMessage();
            $this->message->htmlMessage($this->content);
        }
        if (in_array($this->options['format'], ['text','both'])) {
            $filename = $this->getPath($this->options['folder']) . DS . 'text.ctp';
            
            if (file_exists($filename)) {
                $content = $this->renderTemplate($filename);
                $this->message->textMessage($content);
            } elseif ($this->content and $this->options['format'] === 'both') {
                $content = Html::toText($this->content);
            } else {
                throw new Exception(sprintf('Template %s does not exist', $filename));
            }
        }
    }

    /**
     * Creates an array to use as arguments
     *
     * @param string|int $email
     * @param string|null $name
     * @return array
     */
    protected function buildArguments($email, $name) : array
    {
        if (is_int($email)) {
            $email = $name;
            $name = null;
        }

        return [$email,$name];
    }

    /**
     * Renders html template with layout
     *
     * @return void
     */
    protected function renderHtmlMessage() : void
    {
        $this->content = $this->renderTemplate(
            $this->getPath($this->options['folder']) . DS . 'html.ctp'
        );
        if ($this->options['layout']) {
            $this->content = $this->renderTemplate(
                $this->getLayoutFilename($this->options['layout'])
            );
        }
    }

    /**
     * Renders a file
     */
    protected function renderTemplate($__filename) : string
    {
        if (! file_exists($__filename)) {
            throw new Exception(sprintf('Template %s does not exist', $__filename));
        }
        extract($this->options['viewVars']);
        ob_start();
        include $__filename;

        return ob_get_clean();
    }

    /**
    * Returns the rendered content, this is used for inside
    * rendering.
    *
    * @return string|null
    */
    public function content() : ?string
    {
        return $this->content;
    }

    /**
     * Gets the path
     *
     * @param string $name
     * @return void
     */
    protected function getPath(string $name) : string
    {
        list($plugin, $name) = pluginSplit($name);
        if ($plugin) {
            return PLUGINS .DS . Inflector::underscored($plugin) . DS . 'src' . DS . 'View' . DS . 'Mailer' .DS . $name ;
        }

        return SRC . DS . 'View' . DS . 'Mailer' .DS . $name ;
    }

    /**
     * Layout filename
     *
     * @param string $name
     * @return string
     */
    protected function getLayoutFilename(string $name) : string
    {
        list($plugin, $name) = pluginSplit($name);
        if ($plugin) {
            return PLUGINS .DS .Inflector::underscored($plugin) . DS . 'src' . DS . 'View' . DS . 'Layout' . DS . $name . '.ctp';
        }

        return SRC . DS . 'View' . DS .  'Layout' . DS . $name . '.ctp';
    }
}
