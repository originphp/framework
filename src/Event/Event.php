<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Origin\Event;

class Event
{
    /**
     * The name of the this event. e.g Order.complete
     *
     * @var string
     */
    protected $name = null;

    /**
     * Data that will be passed to the event
     *
     * @var mixed
     */
    protected $data = null;

    /**
     * This is the subject of the event, a object
     *
     * @var string
     */
    protected $subject = null;

    /**
     * The status of the event
     *
     * @var boolean
     */
    protected $stopped = false;

    /**
     * Holds the result from the event
     *
     * @var mixed
     */
    protected $result = null;

    /**
     * Constructor
     *
     * @param string $name
     * @param object $subject
     * @param mixed $data
     */
    public function __construct(string $name, object $subject = null, $data = null)
    {
        $this->name = $name;
        $this->subject = $subject;
        $this->data = $data;
    }

    /**
     * Returns the name of this event
     *
     * @return string
     */
    public function name() : string
    {
        return $this->name;
    }

    /**
     * Returns the subject (object) of this event
     *
     * @return mixed
     */
    public function subject()
    {
        return $this->subject;
    }

    /**
     * Gets and sets data that was passed to/in the event
     *
     * @return mixed
     */
    public function data($data = null)
    {
        if ($data === null) {
            return $this->data;
        }
        $this->data = $data;
    }

    /**
     * Stops the event
     *
     * @return void
     */
    public function stop()
    {
        $this->stopped = true;
    }
    
    /**
     * Checks if the event is stopped
     *
     * @return boolean
     */
    public function isStopped()
    {
        return $this->stopped;
    }

    /**
     * Getter and setter for result
     *
     * @param mixed $result
     * @return mixed
     */
    public function result($result = null)
    {
        if ($result === null) {
            return $this->result;
        }
        $this->result = $result;
    }
}
