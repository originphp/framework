<?php
namespace Origin\Publisher;

use Origin\Model\Traits\ModelTrait;

class Listener
{
    use ModelTrait;
    public function __construct()
    {
        $this->initialize();
    }

    /**
     * This is called when the listener is created
     *
     * @return void
     */
    public function initialize()
    {
    }

    /**
     * This is called before the event method is called on this listener
     *
     * @return void
     */
    public function startup()
    {
    }
    /**
     * This is called after the event method is called on this listener
     *
     * @return void
     */
    public function shutdown()
    {
    }
}
