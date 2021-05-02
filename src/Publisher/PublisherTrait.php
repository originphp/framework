<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
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
namespace Origin\Publisher;

trait PublisherTrait
{
    /**
     * @var Origin\Publisher\Publisher
     */
    private $publisherInstance;

    /**
     * Gets the local instance of Publisher
     *
     * @return \Origin\Publisher\Publisher
     */
    private function getPublisher(): Publisher
    {
        if (! isset($this->publisherInstance)) {
            $this->publisherInstance = new Publisher();
        }

        return $this->publisherInstance;
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
    public function subscribe($object, array $options = []): bool
    {
        return $this->getPublisher()->subscribe($object, $options);
    }

    /**
     * Publish an event with any number of arguments
     * Example:
     *
     * $this->publish('cancelCustomerOrder',$order, $user);
     *
     * @param string $event  e.g. 'cancelCustomerOrder'
     * @param mixed ...$args argument or multiple arguments that will be passed to event
     * @return void
     */
    public function publish(string $event, ...$args): void
    {
        $this->getPublisher()->publish($event, ...$args);
    }
}
