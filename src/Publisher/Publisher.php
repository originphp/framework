<?php
namespace Origin\Publisher;

use Origin\Exception\InvalidArgumentException;

class Publisher
{
    /**
     * @var \Origin\Publisher\Publisher
     */
    protected static $instance;

    /**
     * Holds the listeners
     *
     * @var array
     */
    protected $listeners = [];

    /**
     * Gets the instance of the EventDispatcher
     *
     * @return void
     */
    public static function instance() : Publisher
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Subscribes an object
     *
     * $this->Publisher->subscribe($this);
     * $this->Publisher->subscribe($this,['on'=>[
     *      'beforeSave','afterSave'
     *        ]
     *    ]);
     *
     * Example implementation
     *
     * class User
     * {
     *
     *   public __construct(){
     *      // Does not have to be in construct
     *      $this->Publisher = new Publisher();
     *      $this->Publisher->subscribe($this);
     *   }
     *
     *   // Gets the local event manager instance
     *   public function Publisher(){
     *      if(!isset($this->Publisher)){
     *         $this->Publisher = new Publisher();
     *       }
     *      return $this->Publisher;
     *    }
     * }
     *
     *
     * @param object|string $object either object or class name
     * @param array $events
     * @return void
     */
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
        $options += ['on' => null,'queue' => null];
        if ($options['queue'] and is_object($object)) {
            throw new InvalidArgumentException('Subscribe using queue requires a class name');
        }
        $this->listeners[] = [
            'object' => $object,
            'options' => $options
        ];

        return true;
    }

    /**
     * Broadcasts a event to all subscribers with any number of arguments supplied (if any)
     *
     * $events = new Publisher();
     * $events->publish('beforeSave',$entity, $saveOptions);
     * $events->publish('startup');
     *
     * @param string $event
     * @return void
     */
    public function publish(string $event) : void
    {
        $args = func_get_args();
        array_shift($args);

        $globalListeners = static::instance()->listeners();
        $listeners = array_merge($globalListeners, $this->listeners);

        foreach ($listeners as $listener) {
            $options = $listener['options'];
            $object = $listener['object'];
            if ($options['on']) {
                if (! in_array($event, (array)$options['on'])) {
                    continue;
                }
            }

            # Queue Listener
            if ($options['queue']) {
                $queue = $options['queue'] === true? 'default' : $options['queue'];
                $result = (new ListenerJob(['queue' => $queue]))->dispatch($object, $event, $args);
                if ($result === false) {
                    break;
                }
            }

            # Create object if needed
            if (is_string($object)) {
                $object = new $object();
            }
           
            if ($this->dispatch($object, $event, $args) === false) {
                break;
            }
        }
    }

    /**
     * Dispatches the event to the listener
     *
     * @param object|callable $object
     * @param string $event
     * @param array $args
     * @return boolean|null
     */
    public function dispatch($object, string $event, array $args = []) : ?bool
    {
        $instanceOfListener = $object instanceof Listener;
        if ($instanceOfListener) {
            $object->startup();
        }

        if (method_exists($object, $event)) {
            return call_user_func_array([$object,$event], $args);
        }

        if ($instanceOfListener) {
            $object->shutdown();
        }
    }

    /**
     * Gets the configured listeners (subscribers)
     *
     * @return array
     */
    public function listeners() : array
    {
        return $this->listeners;
    }
}
