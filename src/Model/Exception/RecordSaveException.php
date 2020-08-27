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

namespace Origin\Model\Exception;

use Origin\Model\Entity;
use Origin\Core\Exception\Exception;

class RecordSaveException extends Exception
{
    /**
     * @var \Origin\Model\Entity
     */
    protected $entity = null;

    public function __construct(Entity $entity, string $message, int $code = 500)
    {
        $this->entity = $entity;
    
        $message = $this->formatMessage($entity, $message);
    
        parent::__construct($message, $code);
    }

    /**
     * @return \Origin\Model\Entity
     */
    public function getEntity(): Entity
    {
        return $this->entity;
    }

    /**
     * @param \Origin\Model\Entity $entity
     * @param string $message
     * @return string
     */
    private function formatMessage(Entity $entity, string $message): string
    {
        $out = [];
        foreach ($entity->errors() as $field => $errors) {
            foreach ($errors as $error) {
                $out[] = "{$field}: {$error}";
            }
        }

        return sprintf(
            '%s %s failure. The following errors were found (%s).',
            $entity->name(),
            $message,
            implode(', ', $out),
        );
    }
}
