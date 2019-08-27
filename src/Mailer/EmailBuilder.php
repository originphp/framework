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
use Origin\Exception\NotFoundException;
use Origin\Exception\InvalidArgumentException;

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
            'template' => null,
            'format' => null,
            'headers' => null,
            'attachments' => null,
            'viewVars' => null, ];
      
        $this->options = $options;
        $this->viewVars = $options['viewVars'];
    }

    /**
     * Builds the email object
     *
     * @param boolean $debug set to true to ensure send does not send emails
     * @return \Origin\Mailer\Email
     */
    public function build(bool $debug = false) : Email
    {
        $account = $debug === true ? ['debug' => true] : $this->options['account'];
       
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
                $this->message->addAttachment($filename, $name);
            }
        }
  
        if (! isset($this->options['template'])) {
            throw new InvalidArgumentException('No Template set');
        }
    
        if (in_array($this->options['format'], ['html','both'])) {
            $this->render();
            $this->message->htmlMessage($this->content);
        }
        if (in_array($this->options['format'], ['text','both'])) {
            $filename = $this->getFilename($this->options['template'], 'text');
            
            if (file_exists($filename)) {
                $content = $this->renderTemplate($filename);
                $this->message->textMessage($content);
            } elseif ($this->options['format'] === 'both') {
                $content = Html::toText($this->content);
            } else {
                throw new NotFoundException($filename . ' could not be found');
            }
        }
       
        return $this->message;
    }

    /**
     * Creates an array to use as arguments
     *
     * @param string|int $email
     * @param string|null $name
     * @return array
     */
    private function buildArguments($email, $name) : array
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
    protected function render() : void
    {
        $this->content = $this->renderTemplate(
            $this->getFilename($this->options['template'])
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
            throw new NotFoundException($__filename. ' could not be found');
        }
        extract($this->viewVars);
        ob_start();
        include $__filename;

        return ob_get_clean();
    }

    /**
    * Returns the rendered content
    *
    * @return string|null
    */
    public function content() : ?string
    {
        return $this->content;
    }

    /**
     * Used for determining layout/element filenames
     *
     * @param string $name
     * @param string $folder
     * @return string
     */
    protected function getFilename(string $name, string $type = 'html') : string
    {
        list($plugin, $name) = pluginSplit($name);
        if ($plugin) {
            return PLUGINS .DS . $plugin . DS . 'src' . DS . 'View' . DS . 'Email' .DS . $type . DS . $name . '.ctp';
        }

        return SRC . DS . 'View' . DS .  'Email' .DS . $type . DS . $name . '.ctp';
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
            return PLUGINS .DS . $plugin . DS . 'src' . DS . 'View' . DS . 'Layout' . DS . $name . '.ctp';
        }

        return SRC . DS . 'View' . DS .  'Layout' . DS . $name . '.ctp';
    }
}
