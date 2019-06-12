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

use Origin\Model\Behavior\Behavior;
use Origin\Model\Entity;
use Origin\Utility\Date;
use Origin\Utility\Number;

class DelocalizeBehavior extends Behavior
{
    protected $schema = null;

    /**
     * Before Validate, we de localize user intpu
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

    protected function delocalize(Entity $entity)
    {
        $schema = $this->model()->schema();
        foreach ($entity->modified() as $field) {
            if ($entity->get($field) === null) {
                continue;
            }

            $type = null;
            if (isset($schema[$field])) {
                $type = $schema[$field]['type'];
            }

            $value = $entity->get($field);
            switch ($type) {
                case 'date':
                    $value = Date::parseDate($value);
                break;
                case 'datetime':
                    $value = Date::parseDateTime($value);
                break;
                case 'time':
                     $value = Date::parseTime($value);
                break;
                case 'number':
                case 'decimal':
                case 'integer':
                    $value = Number::parse($value);
                break;
            }
            // Is data already in correct format, then reget - instead of regex
            if ($value === null) {
                $value = $entity->get($field);
            }
            $entity->set($field, $value);
        }
    }
}
