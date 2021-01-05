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
namespace Origin\Service;

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

use Origin\Core\HookTrait;

/**
 * Service object uses dependency injection, it does one thing, it contains business
 * logic and should follow the single responsibility principle.
 *
 *  // A simple example how to use, but you would not create user in db from service object.
 *
 *  class CreateNewUserService extends ApplicationService {
 *
 *      protected $User = null;
 *
 *      protected function initialize(User $user) {
 *          $this->User = $user;
 *        }
 *
 *      protected function execute(array $data) : : ?Result
 *       {
 *          $user = $this->User->create($data);
 *          if($this->User->save($user)){
 *              return $this->result([
 *                  'success' => true,
 *                   'data' => $user
 *              ]);
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

/**
 * Add the execute method to your class
 * @method \Origin\Service\Result execute() This will be called by dispatch
 */
abstract class Service
{
    use HookTrait;

    public function __construct()
    {
        $this->executeHook('initialize', func_get_args());
    }
   
    /**
     * Creates an returns a Service Result object
     *
     * @param array $data e.g. ['data' => []] or ['error'=>['message'=>''Invalid credit card details','code'=>400]]
     * @return \Origin\Service\Result
     */
    public function result(array $data = []): Result
    {
        return new Result($data);
    }

    /**
     * Dispatches the service calling the execute method, make sure this returns
     * a Result object as in future this will be required. (null will be deprecated)
     *
     * @return \Origin\Service\Result|null
     */
    public function dispatch(): ?Result
    {
        $this->executeHook('startup');
        $result = $this->execute(...func_get_args());
        $this->executeHook('shutdown');

        if ($result === null) {
            deprecationWarning('Service objects must return a Result object, returning null has been deprecated.');
        }

        return $result;
    }
}
