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

declare(strict_types=1);

namespace Origin\Model;

use InvalidArgumentException;

/**
 * Validation Rule Set setup and parsing
 *
 * @internal 15.05.20 Despite the original move more towards ruby style blank, feedback suggests that empty is more appropriate
 */
class ValidationRuleSet
{
    /**
     * @var array
     */
    private $messageMap = [
        'accepted' => 'This must be accepted',
        'boolean' => 'This value must be true or false',
        'alpha' => 'This value can only contain letters',
        'alphaNumeric' => 'This value can only contain letters, numbers, dashes and underscores',
        'array' => 'This value must be an array',
        'confirm' => 'The confirmed value does not match',
        'date' => 'Invalid date',
        'datetime' => 'Invalid datetime',
        'dateFormat' => 'Invalid date format',
        'email' => 'Invalid email address',
        'ext' => 'Invalid file type',
        'extension' => 'Invalid file extension',
        'fqdn' => 'Invalid domain',
        'ip' => 'Invalid IP address',
        'ipRange' => 'Invalid IP address',
        'isUnique' => 'Has already been taken',
        'in' => 'Invalid value',
        'notIn' => 'Invalid value',
        'json' => 'Invalid JSON string',
        'macAddress' => 'Invalid MAC address',
        'mimeType' => 'Invalid mime type',
        'notBlank' => 'This field cannot be blank',
        'notEmpty' => 'This field cannot be empty',
        'required' => 'This field is required',
        'time' => 'Invalid time',
        'uid' => 'Invalid UUID string',
        'upload' => 'File upload error',
        'url' => 'Invalid URL',
    ];

    /**
     * @var array
     */
    private $rules = [];

    public function __construct($params)
    {
        if (is_string($params)) {
            $params = [
                'rule-1' => [
                    'rule' => $params
                ]
            ];
        }

        if (isset($params['rule'])) {
            $params = [
                'rule-1' => $params
            ];
        }

        $counter = 1;
        foreach ($params as $key => $definition) {
            $name = 'rule-' . $counter;

            # Transform string name, e.g rule or ['range',10, 20]
            if (is_int($key) && is_string($definition) || (is_array($definition) && ! isset($definition['rule']))) {
                $definition = ['rule' => $definition];
            }

            if (isset($definition['present'])) {
                deprecationWarning('present key has been deprecated use present rule instead');
            }

            $this->rules[$name] = $this->add($definition);
       
            $counter++;
        }
    }

    /**
     * Create a single rule
     *
     * @param array $params
     *   - rule: name of rule e.g. required, numeric, ['date', 'Y-m-d']
     *   - message: the error message to show if the rule fails
     *   - on: default:null. set to create or update to run the rule only on thos
     *   - allowEmpty: default:false validation will be pass on empty values
     *   - stopOnFail: default:false wether to continue if validation fails
     *   - present: default:false the field (key) must be present (but can be empty)
     * @return array
     */
    private function add(array $params): array
    {
        $params += [
            'rule' => null, 'message' => null, 'on' => null, 'present' => false, 'allowEmpty' => false, 'stopOnFail' => false,
        ];

        $rule = $params['rule'];
        if (is_array($params['rule'])) {
            $rule = $params['rule'][0] ?? null;
        }

        if (empty($rule) || ! is_string($rule)) {
            throw new InvalidArgumentException('Invalid rule definition');
        }

        if ($params['message'] === null) {
            $params['message'] = $this->messageMap[$rule] ?? 'Invalid value';
        }

        # Parse minLength:2 etc
        if (is_string($params['rule']) && mb_strpos($params['rule'], ':') !== false) {
            $params['rule'] = $this->convertRule($params['rule']);
        }

        return $params;
    }

    /**
     * Converts minLength:5 or in:1,2,3,6 in correct format
     *
     * @internal hidden feature for now, not sure if this will make things confusing
     *
     * @param string $name
     * @return string|array
     */
    private function convertRule(string $name)
    {
        list($rule, $setting) = explode(':', $name, 2);
        if (mb_strpos($setting, ',') !== false) {
            $setting = explode(',', $setting);
        }

        $setting = array_map(function ($value) {
            if (is_numeric($value)) {
                $value = ctype_digit($value) ? (int) $value : (float) $value;
            }

            return $value;
        }, (array) $setting);

        if (in_array($rule, ['in', 'notIn'])) {
            return  [$rule, $setting];
        }

        return array_merge([$rule], (array) $setting);
    }

    /**
     * Returns the validation rules
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->rules;
    }
}
