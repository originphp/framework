<?php
namespace Origin\Publisher;

use Origin\Core\Resolver;

trait PublisherTrait
{
    /**
     * Publisher
     *
     * @var \Origin\Publisher\Publisher
     */
    protected $Publisher = null;

    /**
     * Gets the event manager
     *
     * @return Origin\Publisher\Publisher
     */
    public function publisher() : Publisher
    {
        if (! $this->Publisher) {
            $this->Publisher = new Publisher();
        }

        return $this->Publisher;
    }
    /**
         * Subscribes an object
         *
         * @param object|string $object
         * @param array $options You can pass the following option keys
         *   - on: an array of methods that this object that will listen to, by default it will listen to all
         *   - queue: true or name of queue connection. All will go into
         * @return bool
         */
    public function subscribe($object, array $options = []) : bool
    {
        if (is_string($object)) {
            $object = Resolver::className($object, 'Listener');
        }

        return $this->publisher()->subscribe($object, $options);
    }

    /**
     * Publish an event with any number of arguments
     * Example:
     *
     * $this->publish('cancelCustomerOrder',$order,$user);
     *
     * @param string $event  'cancelCustomerOrder'
     * @return void
     */
    public function publish(string $event) : void
    {
        $this->publisher()->publish(...func_get_args());
    }

    /**
    * Alias for Publish (use any number of arguments)
    *
    * @param string $event  'Order.afterPlace'
    * @return void
    */
    public function broadcast(string $event) : void
    {
        $this->publisher()->publish(...func_get_args());
    }
}
