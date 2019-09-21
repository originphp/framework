<?php
declare(strict_types = 1);
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

namespace Origin\Model;

use DateTime;
use Origin\Exception\Exception;
use Origin\Model\Exception\ValidatorException;

class ModelValidator
{
    /**
     * Holds a reference to the model.
     */
    protected $model = null;
    /**
     * Contains the validation rules.
     */
    protected $validationRules = [];

    /**
     * Holds the default date format for valdiation
     *
     * @var [type]
     */
    protected $dateFormat = null;
    protected $datetimeFormat = null;
    protected $timeFormat = null;

    protected $defaultMessageMap = [
        'notBlank' => 'This field is required',
        'mimeType' => 'Invalid mime type',
        'extension' => 'Invalid file extension',
        'upload' => 'File upload error',
    ];

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Sets and gets rules
     *
     * @param array $rules
     * @return array|void
     */
    public function rules(array $rules = null)
    {
        if ($rules === null) {
            return $this->validationRules;
        }
        foreach ($rules as $field => $params) {
            $this->setRule($field, $params);
        }
    }

    public function setRule(string $field, $params) :void
    {
        if (is_string($params)) {
            $params = ['rule1' => ['rule' => $params]];
        }
        if (isset($params['rule'])) {
            $params = ['rule1' => $params];
        }

        foreach ($params as $key => $value) {
            $value += [
                'rule' => null,
                'message' => null,
                'required' => false,
                'on' => null,
                'allowBlank' => false,
            ];
            if ($value['message'] === null) {
                $value['message'] = 'Invalid value';
                if ($value['rule'] === 'notBlank') {
                    $value['message'] = 'This field is required';
                }
                $rule = $value['rule'];
                if (is_array($value['rule'])) {
                    $rule = $value['rule'][0];
                }
        
                if (isset($this->defaultMessageMap[$rule])) {
                    $value['message'] = $this->defaultMessageMap[$rule];
                }
            }
           
            $params[$key] = $value;
        }
        $this->validationRules[$field] = $params;
    }
    
    /**
     * Validates a value
     *
     * @param mixed $value
     * @param string|array $ruleSet email or ['equalTo', 'origin']
     * @return bool
     */
    public function validate($value, $ruleSet) : bool
    {
       
        // ['extension',['csv','txt']]
        if (is_array($ruleSet)) {
            $rule = $ruleSet[0];
            $args = $ruleSet;
            $args[0] = $value;
        } else {
            $rule = $ruleSet;
            $args = [$value];
        }
     
        // Validation methods here
        if (method_exists($this, $rule)) {
            return call_user_func_array([$this, $rule], $args);
        }
       
        // Validation methods in model
        if (method_exists($this->model, $rule)) {
            return call_user_func_array([$this->model, $rule], $args);
        }
        // Regex expressions
        if ($rule[0] === '/') {
            return $this->custom($value, $rule);
        }

        throw new ValidatorException('Unkown Validation Rule');
    }

    /**
     * Handles the on
     *
     * @param bool $create
     * @param string|bool $on null,'create','update'
     * @return bool
     */
    protected function runRule(bool $create, $on)  : bool
    {
        if ($on === null or ($create and $on === 'create') or (! $create and $on === 'update')) {
            return true;
        }

        return false;
    }

    /**
     * Validates data
     *
     * @internal Data should only be validated if it is submmited, it will cause issues.
     *
     * @param Entity $entity
     * @param boolean $create
     * @return bool
     */
    public function validates(Entity $entity, bool $create = true) : bool
    {
        $modified = $entity->modified();
        
        foreach ($this->validationRules as $field => $ruleset) {
            foreach ($ruleset as $validationRule) {
                if ($validationRule['on'] and ! $this->runRule($create, $validationRule['on'])) {
                    continue;
                }

                // Don't run validation rule on field if its not in the entity
                if (! $validationRule['required'] and in_array($field, $modified) === false) {
                    continue;
                }
            
                $value = $entity->get($field);
  
                // Required means the key must be present not wether it has a value or not
                if ($validationRule['required'] and ! in_array($field, $entity->properties())) {
                    $entity->invalidate($field, 'This field is required');
                    break; // dont run any more validation rules on this field if blank
                }

                // If its required rule (which does not exist), check and break or continue
                if ($validationRule['rule'] === 'notBlank') {
                    if (! $this->validate($value, 'notBlank')) {
                        $entity->invalidate($field, $validationRule['message']);
                    }
                    continue; // goto next rule
                }

                // If the value is not required and value is empty then don't validate
                if ($this->isBlank($value)) {
                    if ($validationRule['allowBlank'] === true) {
                        continue;
                    }
                }
         
                // Handle both types
                if ($validationRule['rule'] === 'isUnique') {
                    $validationRule['rule'] = ['isUnique',[$field]];
                }
                if (is_array($validationRule['rule']) and $validationRule['rule'][0] === 'isUnique') {
                    $value = $entity;
                }
               
                if (! $this->validate($value, $validationRule['rule'])) {
                    $entity->invalidate($field, $validationRule['message']);
                }
            }
        }

        return empty($entity->errors());
    }

    /**
     * Check if a value is considered blank for running a rule.
     * It also checks for empty file uploads
     *
     * @param mixed $value
     * @return boolean
     */
    protected function isBlank($value) : bool
    {
        if ($value === '' or $value === null) {
            return true;
        }
        
        if (is_array($value) and isset($value['error'])) {
            return $value['error'] === UPLOAD_ERR_NO_FILE;
        }

        return false;
    }

    /**
     * VALIDATORS.
     */
    public function alphaNumeric($value) : bool
    {
        return ctype_alnum($value);
    }

    public function boolean($value) : bool
    {
        return is_bool($value);
    }

    public function custom($value, $regex) : bool
    {
        return (bool) preg_match($regex, $value);
    }

    /**
     * Validates datetime using a format compatible with the php date function.
     *
     * @param string $value
     * @param string $timeFormat Y-m-d
     *
     * @return bool
     */
    public function date($value, $dateFormat = 'Y-m-d') : bool
    {
        $dateTime = DateTime::createFromFormat($dateFormat, $value);
        if ($dateTime !== false and $dateTime->format($dateFormat) === $value) {
            return true;
        }
        
        return false;
    }

    /**
     * Validates datetime using a format compatible with the php date function.
     *
     * @param string $value
     * @param string $timeFormat Y-m-d H:i:s
     *
     * @return bool
     */
    public function datetime($value, $dateFormat = 'Y-m-d H:i:s') : bool
    {
        $dateTime = DateTime::createFromFormat($dateFormat, $value);
      
        if ($dateTime !== false and $dateTime->format($dateFormat) === $value) {
            return true;
        }

        return false;
    }

    /**
     * This is alias for float
     */
    public function decimal($value) : bool
    {
        return $this->float($value);
    }

    public function email($value) : bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function equalTo($value, $comparedTo = null) : bool
    {
        return $value === $comparedTo;
    }

    /**
     * Checks that value has an extension.
     *
     * @param string|array $value
     * @param string|array $extensions [description]
     *
     * @return bool true or false
     */
    public function extension($value, $extensions = []) : bool
    {
        if (is_array($value)) {
            $value = $value['name'] ?? 'none';
        }
        if (is_string($extensions)) {
            $extensions = [$extensions];
        }
        $extension = mb_strtolower(pathinfo($value, PATHINFO_EXTENSION));

        return $this->inList($extension, $extensions, true);
    }

    public function inList($value, $values, $caseInSensitive = false) : bool
    {
        if ($caseInSensitive) {
            $values = array_map('mb_strtolower', $values);

            return in_array(mb_strtolower($value), $values);
        }

        return in_array($value, $values);
    }

    public function ip($value, $options = null) : bool
    {
        return (bool) filter_var($value, FILTER_VALIDATE_IP);
    }

    /**
     * Checks if string is less than or equals to the max length.
     */
    public function maxLength($value, $max) : bool
    {
        return mb_strlen($value) <= $max;
    }

    /**
     * Checks if a string is greater or equal to the min length.
     */
    public function minLength($value, $min) : bool
    {
        return mb_strlen($value) >= $min;
    }

    /**
     * Checks if a string is not blank (not empty and not made up of whitespaces).
     */
    public function notBlank($value) : bool
    {
        if (empty($value) and (string) $value !== '0') {
            return false;
        }

        return (bool) preg_match('/[^\s]+/', (string) $value);
    }

    /**
     * Checks that value is not empty whilst dealing with 0 values.
     */
    public function notEmpty($value) : bool
    {
        if (empty($value) and (string) $value !== '0') {
            return false;
        }

        return true;
    }

    public function numeric($value) : bool
    {
        return ($this->integer($value) or $this->float($value));
    }

    /**
     * Finds whether the value is integer e.g. 123
     *
     * @param integer $value e.g. 154
     * @return bool
     */
    public function integer($value) : bool
    {
        if (is_string($value)) {
            return (bool) filter_var($value, FILTER_VALIDATE_INT);
        }

        return is_int($value);
    }

    /**
      * Finds whether the value is float e.g 123.56
      *
      * @param float $value
      * @return bool
      */
    public function float($value) : bool
    {
        if (is_string($value)) {
            return (bool) filter_var($value, FILTER_VALIDATE_FLOAT) and filter_var($value, FILTER_VALIDATE_INT) === false;
        }

        return is_float($value);
    }

    /**
     * Checks if a number is in a range
     *
     * @param int $value
     * @param int $min
     * @param int $max
     * @return boolean
     */
    public function range($value, $min = null, $max = null) : bool
    {
        if (! is_numeric($value) or ! isset($min) or ! isset($max)) {
            return false;
        }

        return $value >= $min and $value <= $max;
    }

    /**
     * Validates date using a format compatible with the php date function.
     *
     * @param string $value
     * @param string $timeFormat H:i:s
     * @return bool
     */
    public function time($value, $timeFormat = 'H:i:s') : bool
    {
        $dateTime = DateTime::createFromFormat($timeFormat, $value);
    
        if ($dateTime !== false and $dateTime->format($timeFormat) === $value) {
            return true;
        }

        return false;
    }

    /**
     * Checks that a url is valid.
     *
     * @param string $url
     * @param bool   $protocol set to false if you want a valid url not to include the protocol
     * @return bool true or false
     */
    public function url($url, $protocol = true) : bool
    {
        if ($protocol) {
            return (bool) filter_var($url, FILTER_VALIDATE_URL);
        }

        if (preg_match('/^http|https|:\/\//i', $url)) {
            return false;
        }

        return (bool) filter_var('https://'.$url, FILTER_VALIDATE_URL);
    }

    /**
     * Checks that file was uploaded ok
     *
     * @param array $result
     * @param mixed $options
     * @return boolean
     */
    public function upload($result, $optional = false) : bool
    {
        if (is_array($result) and isset($result['error'])) {
            $result = $result['error'];
        }
        /**
         * Let test pass if the upload is optional and no file was uploaded
         */
        if ($optional and $result === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        return $result === UPLOAD_ERR_OK;
    }

    /**
     * Checks the mime type of a file
     *
     * @param string|array $result
     * @param array $mimeTypes
     * @return boolean
     */
    public function mimeType($result, $mimeTypes = []) : bool
    {
        if (is_array($result) and isset($result['tmp_name'])) {
            $result = $result['tmp_name'];
        }
        if (is_string($mimeTypes)) {
            $mimeTypes = [$mimeTypes];
        }
        $mimeType = mime_content_type($result);
        if ($mimeType === false) {
            throw new Exception('Unable to determine the mimetype'); // Cant reach here
        }

        return in_array($mimeType, $mimeTypes);
    }
}
