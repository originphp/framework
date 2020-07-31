<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
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
namespace Origin\Record;

use Origin\Core\HookTrait;
use BadMethodCallException;
use Origin\Core\ContainerTrait;
use Origin\Core\InitializerTrait;
use \Origin\Validation\ValidateTrait;
use Origin\Core\CallbackRegistrationTrait;

/**
 * Active record but without persistance, may need to carry out an action like send an email (contact form) or send data to an API like Stripe.
 *
 * Objective to have an object that work with form data that is not in database, or other data that might
 * need validating without having to setup in the database. This needs to work with the FormHelper
 */
class Record
{
    use InitializerTrait;
    use HookTrait;
    use CallbackRegistrationTrait;
    use ValidateTrait {
        validates as private validatesTrait;
    }
    use ContainerTrait;

    /**
     * Record name (private)
     *
     * @var string
     */
    private $name = null;

    /**
     * Holds the schema for Record
     *
     * @example
     *
     * 'email' => [
     *   'type' => 'string',
     *   'length' => 255
     * ]
     *
     * @var array
     */
    protected $schema = [];

    /**
     * Acceptable field types
     *
     * @var array
     */
    private $fieldTypes = [
        'string','text','integer','float','decimal','datetime','time','date','binary','boolean'
    ];

    public function __construct(array $data = [], array $options = [])
    {
        $options += ['name' => $this->name];

        if ($options['name'] === null) {
            list($namespace, $options['name']) = namespaceSplit(get_class($this));
        }
        $this->name = $options['name'];

        $this->executeHook('initialize');
        $this->initializeTraits();

        $this->setDefaultValues();
        $this->set($data);
    }

    /**
     * Creates a new object
     *
     * @param array $data
     * @param array $options
     *  - fields : array of fields that are allowed or leave blank for all
     * @return static
     */
    public static function new(array $data = [], array $options = [])
    {
        $options += ['fields' => null];
        
        if (is_array($options['fields'])) {
            $data = static::filterData($data, $options['fields']);
        }

        return new static($data, $options);
    }

    /**
     * Patches an existing record with an array of data
     *
     * @param \Origin\Record\Record $record
     * @param array $data
     * @param array $options
     * @return static
     */
    public static function patch(Record $record, array $data = [], array $options = [])
    {
        $options += ['fields' => null];
        
        if (is_array($options['fields'])) {
            $data = static::filterData($data, $options['fields']);
        }

        $record->set($data);

        return $record;
    }

    /**
     * Protect from mass assignment
     *
     * @param array $data
     * @param array $fields
     * @return array
     */
    private static function filterData(array $data, array $fields = null): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if ($fields === null || in_array($key, $fields)) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /**
        * Sets the default values for the record
        *
        * @return void
        */
    private function setDefaultValues(): void
    {
        foreach ($this->schema as $field => $config) {
            if ($config['default'] !== null) {
                $this->$field = $config['default'];
            }
        }
    }

    /**
     * Validates this object.
     *
     *
     * @param boolean $isNewRecord
     * @return boolean
     */
    public function validates(bool $isNewRecord = true): bool
    {
        if ($this->dispatchCallbacks('beforeValidate')) {
            $this->validationErrors = $this->validator()->validate(
                $this->containerData,
                $isNewRecord
            );
           
            if (empty($this->validationErrors)) {
                $this->dispatchCallbacks('afterValidate');

                return true;
            }
        }

        return false;
    }

    /**
     * Dispatches the callbacks
     *
     * @param string $callback
     * @param array $arguments
     * @return boolean
     */
    protected function dispatchCallbacks(string $callback, array $arguments = []): bool
    {
        foreach ($this->registeredCallbacks($callback) as $method => $options) {
            $this->validateCallback($method);
            if ($this->$method(...$arguments) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Registers a callback to be called before validation
     *
     * @param string $method
     * @return void
     */
    protected function beforeValidate(string $method): void
    {
        $this->registerCallback('beforeValidate', $method);
    }

    /**
     * Registers a callback to be called after validation
     *
     * @param string $method
     * @return void
     */
    protected function afterValidate(string $method): void
    {
        $this->registerCallback('afterValidate', $method);
    }

    /**
     * Add a field to the schema for this object
     *
     * @param string $name
     * @param string|array $typeOrOptions e.g. type or ['type'=>'string','limit']
     *  - type: string, text, integer, float, decimal, datetime, time, date, binary, boolean
     *  - length: default:null
     *  - default: default value to use
     * @return self
     */
    protected function addField(string $name, $typeOrOptions): self
    {
        $defaults = ['type' => null,'length' => null,'default' => null];
        if (! is_array($typeOrOptions)) {
            $typeOrOptions = ['type' => $typeOrOptions];
        }

        if (! in_array($typeOrOptions['type'], $this->fieldTypes)) {
            throw new BadMethodCallException('Unknown field type');
        }

        $this->schema[$name] = array_merge($defaults, $typeOrOptions);

        return $this;
    }

    /**
     * Gets the schema for this Record
     *
     * @param string $name
     * @return array|null
     */
    public function schema(string $name = null): ? array
    {
        if ($name === null) {
            return $this->schema;
        }

        return $this->schema[$name] ?? null;
    }

    /**
     * Gets the name of this Record
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }
}
