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
/**
 * # Service Object
 * ## Origin
 *   - Martin Fowler: ‘Patterns of Enterprise Application Architecture’ - reference service layer
 *   - Eric Evan: Domain Driven Design (DDD) - calls it services
 *
 * ## Nice Articles
 *   - https://multithreaded.stitchfix.com/blog/2015/06/02/anatomy-of-service-objects-in-rails/
 * ## Patterns
 *   - Command Pattern: https://en.wikipedia.org/wiki/Command_pattern
 *   - Dependency Injection: https://en.wikipedia.org/wiki/Dependency_injection
 *
 * ## Notes
 *  - Services should not call other services (controversial), because then its not a single responsability
 */
namespace Origin\Service;

use Origin\Exception\Exception;

/**
 * Service object uses dependency injection, it does one thing, it contains business
 * logic and should follow the single responsibility principle.
 *
 *  // A simple example
 *
 *  class CreateNewUserService extends ApplicationService {
 *
 *      protected $User = null;
 *
 *      public function initialize(User $user) {
 *          $this->User = $user;
 *        }
 *
 *      public function execute(array $data) : Entity
 *       {
 *          $user = $this->User->create($data);
 *          if($this->User->save($user)){
 *              return $user;
 *          }
 *          throw new Exception('Error creating user');
 *      }
 *   }
 *
 *  $user = (new CreateNewUserService($User))->dispatch(['name'=>'jon snow']);
 *
 *
 *
 */

class Service
{
    /**
     * Constructor, pass any dependencies here such as model, and then
     * store them as property. Do not use configuration/settings here
     */
    public function __construct()
    {
        if (! method_exists($this, 'initialize')) {
            throw new Exception('Job must have an intialize method');
        }

        $this->initialize();
    }

    # Initialize is not defined here so user can define with proper type hints and return types

    /**
     * This is called before execute
     *
     * @return void
     */
    public function startup()
    {
    }

    # Execute is not defined here so user can define with proper type hints and return types

    /**
     * This is called after execute
     *
     * @return void
     */
    public function shutdown()
    {
    }

    /**
     * Creates an returns a Service Result object
     *
     * @param array $properties e.g. ['success'=>true] or ['error'=>'Invalid credit card details']
     * @return \Origin\Service\Result
     */
    public function result(array $data = []) : Result
    {
        return new Result($data);
    }

    /**
     * Dispatches the service
     *
     * @return \Origin\Service\Result|null
     */
    public function dispatch() : ?Result
    {
        if (! method_exists($this, 'execute')) {
            throw new Exception('Job must have an execute method');
        }
        
        $this->startup();
        $result = $this->execute(...func_get_args());
        $this->shutdown();

        return $result;
    }
}
