<?php
namespace Origin\Model\Behavior;

use Origin\Model\Behavior\Behavior;
use Origin\Model\Entity;
use Origin\Utility\Date;
use Origin\Utility\Number;

class DelocalizeBehavior extends Behavior
{
    protected $schema = null;

    /**
     * Before save callback
     *
     * @param \Origin\Model\Entity $entity
     * @param array $options
     * @return bool must return true to continue
     */
    public function beforeSave(Entity $entity, array $options = [])
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
            if($value === null){
                $value = $entity->get($field);
            }
            $entity->set($field, $value);
        }

        return true;
    }
}
