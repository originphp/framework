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
  * Delocalize Behavior - In the before validate, data will be parsed to local. This is important because validation
  * is going to check raw data e.g. 123456.78. If validation fails, when it reaches the form helper, if the data is valid
  * it will format it back, if not it will leave as is.
  */
namespace Origin\Model\Behavior;

use Origin\Model\Entity;
use Origin\Utility\Date;
use Origin\Utility\Number;

class DelocalizeBehavior extends Behavior
{
    protected $schema = null;

    /**
     * Before Validate, we de localize user intput
     *
     * @param \Origin\Model\Entity $entity
     * @param array $options
     * @return bool must return true to continue
     */
    public function beforeValidate(Entity $entity)
    {
        $this->delocalize($entity);

        return true;
    }

    /**
     * Delocalize the values in the entity
     *
     * @param \Origin\Model\Entity $entity
     * @return \Origin\Model\Entity
     */
    public function delocalize(Entity $entity) : Entity
    {
        $columns = $this->model()->schema()['columns'];
        foreach ($entity->modified() as $field) {
            $value = $entity->get($field);
            if ($value and isset($columns[$field])) {
                $value = $this->processField($columns[$field]['type'], $value);
                
                // Restore value incase of invalid value etc
                if ($value === null) {
                    $value = $entity->get($field);
                }
                $entity->set($field, $value);
            }
        }

        return $entity;
    }

    /**
     * Process value
     *
     * @param string $type
     * @param string $value
     * @return mixed
     */
    protected function processField(string $type, $value)
    {
        if ($type === 'date') {
            return Date::parseDate($value);
        }
        if ($type === 'datetime') {
            return Date::parseDateTime($value);
        }
        if ($type === 'time') {
            $value = Date::parseTime($value);
        }
        if (in_array($type, ['decimal','integer','float','bigint'])) {
            return Number::parse($value);
        }

        return $value;
    }
}
