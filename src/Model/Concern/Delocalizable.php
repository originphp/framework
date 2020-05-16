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
namespace Origin\Model\Concern;

use Origin\Model\Entity;
use Origin\Utility\Date;
use Origin\Utility\Number;

trait Delocalizable
{
    /**
     * Initialization method, here you can register callbacks or configure model associations
     *
     * @return void
     */
    protected function initializeDelocalizable() : void
    {
        $this->beforeValidate('delocalize');
    }

    /**
    * Delocalize the values in the entity
    * This is a beforeValidate callback, so it must return true;
    *
    * @param \Origin\Model\Entity $entity
    * @return bool
    */
    public function delocalize(Entity $entity) : bool
    {
        $columns = $this->schema()['columns'];
        foreach ($entity->modified() as $field) {
            $value = $entity->get($field);
            if ($value && isset($columns[$field])) {
                $value = $this->processField($columns[$field]['type'], $value);
                
                // Restore value incase of invalid value etc
                if ($value === null) {
                    $value = $entity->get($field);
                }
                $entity->set($field, $value);
            }
        }

        return true;
    }

    /**
     * Parses values
     *
     * @param string $type
     * @param string $value
     * @return mixed
     */
    private function processField(string $type, $value)
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
