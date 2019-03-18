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

namespace Origin\View\Helper;

use Origin\Model\ModelRegistry;
use Origin\Core\Router;
use Origin\Core\Inflector;
use Origin\Model\Entity;
use Origin\Utility\Date;
use Origin\Utility\Number;

use Origin\View\TemplateTrait;
use Origin\Core\Dot;
use Origin\Core\Configure;

class FormHelper extends Helper
{
    use TemplateTrait;
    /**
     * If you want to change these, you need to copy the whole set e.g controlDefaults.
     *
     * @var array
     */
    protected $defaultConfig = [
      'controlDefaults' => array(
        'text' => ['div' => 'form-group', 'class' => 'form-control'],
        'textarea' => ['div' => 'form-group', 'class' => 'form-control'],
        'password' => ['div' => 'form-group', 'class' => 'form-control'],
        'checkbox' => ['div' => 'form-check', 'class' => 'form-check-input', 'label' => ['class' => 'form-check-label']],
        'radio' => ['div' => 'form-check', 'class' => 'form-check-input', 'label' => ['class' => 'form-check-label']],
        'number' => ['div' => 'form-group', 'class' => 'form-control'],
        'select' => ['div' => 'form-group', 'class' => 'form-control'],
        'date' => ['div' => 'form-group', 'class' => 'form-control'],
        'time' => ['div' => 'form-group', 'class' => 'form-control'],
        'datetime' => ['div' => 'form-group', 'class' => 'form-control'], // Date time appended
      ),
      'templates' => array(
        'button' => '<button type="{type}"{attributes}>{name}</button>',
        'checkbox' => '<input type="checkbox" name="{name}" value="{value}"{attributes}>',
        'control' => '<div class="{class} {type}{required}">{before}{content}{after}</div>',
        'controlError' => '<div class="{class} {type}{required} error">{before}{content}{after}{error}</div>',
        'div' => '<div{attributes}>{content}</div>',
        'error' => '<div class="error-message">{content}</div>',
        'file' => '<input type="file" name="{name}"{attributes}>',
        'formStart' => '<form{attributes}>',
        'formEnd' => '</form>',
        'hidden' => '<input type="hidden" name="{name}"{attributes}>',
        'input' => '<input type="{type}" name="{name}"{attributes}>',
        'label' => '<label for="{name}"{attributes}>{text}</label>',
        'radio' => '<input type="radio" name="{name}" value="{value}"{attributes}>',
        'select' => '<select name="{name}"{attributes}>{content}</select>',
        'option' => '<option value="{value}">{text}</option>',
        'optionSelected' => '<option value="{value}" selected>{text}</option>',
        'optgroup' => '<optgroup label="{label}">{content}</optgroup>',
        'textarea' => '<textarea name="{name}"{attributes}>{value}</textarea>',
        'postLink' => '<a href="#"{attributes}>{text}</a>',
        'onclickConfirm' => 'if (confirm(&quot;{message}&quot;)) { document.{name}.submit(); } event.returnValue = false; return false;',
        'onclick' => 'document.{name}.submit();',
      ),
    ];

    /**
     * The model name
     *
     * @var string|null
     */
    protected $modelName = null;

    /**
     * Holds the data
     *
     * @var array|entity|null
     */
    protected $data = null;

    /**
     * Filled from introspect.
     *
     * @var array
     */
    protected $requiredFields = [];

    protected $typeMap = array(
          'char' => 'string',
          'varchar' => 'string',
          'int' => 'number',
          'decimal' => 'number',
          'float' => 'number',
          'date' => 'date',
          'datetime' => 'datetime',
          'time' => 'time',
          'boolean' => 'bool',
          'tinyint' => 'bool',
          'text' => 'text',
          'tinytext' => 'text',
          'mediumtext' => 'text',
          'longtext' => 'text',
          'blob' => 'binary',
          'tinyblob' => 'binary',
          'mediumblob' => 'binary',
          'longblob' => 'binary',
        );

    protected $controlMap = array(
          'string' => 'text',
          'text' => 'textarea',
          'number' => 'number',
          'date' => 'date',
          'datetime' => 'datetime',
          'time' => 'time',
          'bool' => 'checkbox',
          'binary' => 'file',
        );

    protected $meta = [];

    /**
     * Starts a form.
     *
     * @param string|entity|bool $model   model name, entity object or false to no model
     * @param array              $options
     *
     * @return string
     */
    public function create($model = null, array $options = [])
    {
        $attributes = [];

        if ($model === null) {
            $controller = $this->view()->request->params('controller');
            $model = Inflector::camelize(Inflector::singularize($controller));
        }
        if (is_object($model)) {
            $this->data = $entity = $model;
            $this->modelName = $entity->name();
        }

        if ($this->modelName) {
            $this->introspectModel($this->modelName);
        }

        $defaults = [
          'type' => 'post',
          'url' => $this->view()->request->url(true),
        ];

        $options = array_merge($defaults, $options);
        
        if ($options['type'] == 'file') {
            $attributes['enctype'] = 'multipart/form-data';
            $attributes['method'] = 'post';
        } else {
            $attributes['method'] = $options['type'];
            $attributes['accept-charset'] = 'utf-8';
        }
        $attributes['action'] = Router::url($options['url']);
        unset($options['type'],$options['url']);
        $attributes += $options;
     
        return $this->template('formStart', $attributes);
    }

    public function button(string $name, array $options = [])
    {
        $defaults = array('name' => $name, 'type' => 'submit');
        $options = array_merge($defaults, $options);

        return $this->template('button', $options);
    }

    public function checkbox(string $name, array $options = [])
    {
        $options = array_merge(['hiddenField' => true], $options);
        $options = $this->prepareOptions($name, $options);
        $checked = !empty($options['value'])?true:false;
        if ($checked) {
            $options['checked'] = true;
        }
        $options['value'] = 1; // Must always be one

        $hiddenField = $options['hiddenField'];
        unset($options['hiddenField']);

        $checkbox = $this->template('checkbox', $options);

        if ($hiddenField) {
            $hiddenField = $this->template('hidden', ['value' => 0, 'name' => $options['name']]);
            unset($options['hiddenField']);

            return $hiddenField . $checkbox;
        }

        return $checkbox;
    }

    protected function introspectModel(string $name)
    {
        if (isset($this->meta[$name])) {
            return $this->meta[$name];
        }
        $meta = [
          'columnMap' => [],
          'requiredFields' => [],
          'primaryKey' => null,
          'maxlength' => [],
        ];

        $entity = $this->data;

        //? Introspect related models as well?
        $model = ModelRegistry::get($name);
        if ($model) {
            $meta['primaryKey'] = $model->primaryKey;

            foreach ($model->schema() as $column => $row) {
                $type = 'text';
                if (isset($this->typeMap[$row['type']])) {
                    $type = $this->typeMap[$row['type']];
                }

                if (in_array($row['type'], ['float', 'integer', 'decimal'])) {
                    $type = 'number';
                }
                $meta['columnMap'][$column] = $type;
           
                if (empty($row['length']) == false and $type != 'bool') {
                    $meta['maxlength'][$column] = $row['length'];
                }
            }

            if ($entity) {
                $create = true;
                if ($entity->has($meta['primaryKey'])) {
                    $create = false;
                }
                $validator = $model->validator();
                $meta['requiredFields'] = $this->parseRequiredFields($validator->rules(), $create);
            }
        }

        $this->meta[$name] = $meta;

        return $meta;
    }

    /**
    * Gets the required fields for the form, in terms of a form, reuqired field is one
    * that cannot be blank, in terms of the validator required means the key must be present.
    */
    protected function parseRequiredFields(array $validationRules, $create = true)
    {
        $result = [];

        foreach ($validationRules as $field => $ruleset) {
            foreach ($ruleset as $validationRule) {
                if ($validationRule['rule'] === 'notBlank') {
                    $result[] = $field;
                }
            }
        }

        return $result;
    }

    /**
     * Creates a form control from the field name. Displays validation errors
     * and other magic!
     *
     * @param string $name    field name
     * @param array  $options
     *                        - type
     *                        - label  can be string or array
     *
     * @return string
     */
    public function control(string $name, array $options = [])
    {
        $selectOptions = [];

        if (!isset($options['type'])) {
            $options['type'] = $this->detectType($name);
        }

        if (isset($this->config['controlDefaults'][$options['type']])) {
            $options += $this->config['controlDefaults'][$options['type']];
        }

        $before = $options['before'] ?? null;
        $after = $options['after'] ?? null;

        unset($options['before'],$options['after']);

        $label = $name;
        // Determine Label and check for list
        if (substr($name, -3) === '_id') {
            $label = substr($name, 0, -3);
            $parts = explode('.', $label);
            $models = Inflector::pluralize($parts[0]);

            if (isset($this->view()->vars[$models])) {
                $selectOptions = $this->view()->vars[$models];
            }
        }
        $label = Inflector::humanize($label);

        $options += array(
              'label' => $label,
              'id' => $this->domId($name),
              'div' => 'input',
            );

        $div = $options['div']; // Div for Group
        unset($options['div']);

        $type = $options['type']; // Select/Text etc

        unset($options['type']);

        if (isset($options['options'])) {
            $selectOptions = $options['options'];
            unset($options['options']);
            $type = 'select';
        }

        // Handle Label
        $labelOutput = '';
        if ($options['label']) {
            if (is_array($options['label'])) {
                $labelOptions = $options['label'];
            } else {
                $labelOptions = [
                  'name' => $options['id'],
                  'text' => $options['label'],
                ];
            }

            $labelOptions['name'] = $options['id'];
            if (empty($labelOptions['text'])) {
                $labelOptions['text'] = $label;
            }

            $labelOutput = $this->template('label', $labelOptions);
            unset($options['label']);
        }

        $template = 'control';
        $model = $this->modelName;

        $parts = explode('.', $name);
        $column = end($parts);

        $errorOutput = '';
        // Get Validation Errors
        if ($this->data) {
            $entity = $this->getEntity($this->data, $name);
            if ($entity) {
                $model = $entity->name();
                if ($entity->getError($column)) {
                    foreach ($entity->getError($column) as $error) {
                        $errorOutput .= $this->template('error', ['content' => $error]);
                    }
                    $template = 'controlError';
                }
            }
        }
      
        
        // Check if field is required to add required class
        $required = false;
        if ($model and in_array($column, $this->requiredFields($model))) {
            $required = true;
        }

        if ($type === 'select' or $type === 'radio') {
            $fieldOutput = $this->{$type}($name, $selectOptions, $options);
        } else {
            $fieldOutput = $this->{$type}($name, $options);
            if ($type === 'hidden') {
                return $fieldOutput;
            }
        }

        if ($type === 'checkbox' or $type === 'radio') {
            $output = $fieldOutput.$labelOutput;
        } else {
            $output = $labelOutput.$fieldOutput;
        }

        $options['class'] = $div;
        if ($required) {
            $options['required'] = ' required';
        }

        return $this->template($template, $options + [
            'type' => $type,
            'before' => $before,
            'after' => $after,
            'content' => $output.$errorOutput,
            ]);
    }

    protected function requiredFields(string $model)
    {
        if (!isset($this->meta[$model])) {
            $this->introspectModel($model);
        }

        return $this->meta[$model]['requiredFields'];
    }

    public function date(string $name, array $options = [])
    {
        $options = $this->prepareOptions($name, $options);
        $options['type'] = 'text';

        return $this->template('input', $options);
    }

    /**
     * The datetime widget is designed for datetime fields which require user input.
     *
     * @param string $name
     * @param array  $options
     */
    public function datetime(string $name, array $options = [])
    {
        $options = $this->prepareOptions($name, $options);
        $options['type'] = 'text';

        return $this->template('input', $options);
    }

    /**
     * Creates a file input. Form create must be set to multipart/form-data.
     *
     * @param string $name    field name
     * @param array  $options array of options
     *
     * @return string html
     */
    public function file(string $name, array $options = [])
    {
        $options = $this->prepareOptions($name, $options);

        return $this->template('file', $options);
    }

    public function hidden(string $name, array $options = [])
    {
        $options = $this->prepareOptions($name, $options);

        return $this->template('hidden', $options);
    }

    public function end()
    {
        return $this->template('formEnd');
    }

    public function label(string $name, string $text = null, array $options = [])
    {
        $options['name'] = $name;

        if ($text === null) {
            $text = $name;
        }
        $options['text'] = $text;

        return $this->template('label', $options);
    }

    public function text(string $name, array $options = [])
    {
        $options = $this->prepareOptions($name, $options);
        $options['type'] = 'text';

        return $this->template('input', $options);
    }

    public function textarea(string $name, array $options = [])
    {
        $options = $this->prepareOptions($name, $options);

        return $this->template('textarea', $options);
    }

    public function time(string $name, array $options = [])
    {
        $options = $this->prepareOptions($name, $options);
        $options['type'] = 'text';
         
        return $this->template('input', $options);
    }

    public function number(string $name, array $options = [])
    {
        $options = $this->prepareOptions($name, $options);
        $options['type'] = 'text';

        return $this->template('input', $options);
    }

    public function password(string $name, array $options = [])
    {
        $options = $this->prepareOptions($name, $options);
        $options['type'] = 'password';

        return $this->template('input', $options);
    }

    public function postLink(string $name, $url, $options = [])
    {
        if (is_array($url)) {
            $url = Router::url($url);
        }
        global $formElementCounter;
        if (!$formElementCounter) {
            $formElementCounter = 1000;
        }
        $form = 'link_'.$formElementCounter++;
        $attributes = [
          'name' => $form,
          'style' => 'display:none',
          'method' => 'post',
          'action' => $url,
        ];

        $output = $this->template('formStart', $attributes);
        $output .= $this->hidden('_method', ['value' => 'POST']);
        $options['text'] = $name;

        if (empty($options['confirm'])) {
            $options['onclick'] = $this->template('onclick', ['name' => $form]);
        } else {
            $options['onclick'] = $this->template('onclickConfirm', ['name' => $form, 'message' => $options['confirm']]);
        }
        unset($options['confirm']);

        $output .= $this->template('formEnd');
        $output .= $this->template('postLink', $options);

        return $output;
    }

    public function radio(string $name, array $options = [], array $radioOptions = [])
    {
        $radioOptions['id'] = true;
        $radioOptions = $this->prepareOptions($name, $radioOptions);

        $output = '';

        $radioId = $radioOptions['id'];

        foreach ($options as $key => $value) {
            $radioOptions['id'] = $radioId.'-'.$key;
            $radioOptions['value'] = $key;
            $radio = $this->template('radio', $radioOptions);
            $output .= $this->template('label', ['name' => $radioOptions['id'], 'text' => $radio.$value]);
        }

        return $output;
    }

    /**
     * Select form element
     *
     * @param string $name
     * @param array $options
     * @param array $selectOptions empty,value
     * @return void
     */
    public function select(string $name, array $options = [], array $selectOptions = [])
    {
        $selectOptions = $this->prepareOptions($name, $selectOptions);

        if (!empty($selectOptions['empty'])) {
            if ($selectOptions['empty'] === true) {
                $selectOptions['empty'] = '--None--';
            }
            $options = ['' => $selectOptions['empty']] + $options;
        }
        unset($selectOptions['empty']);

        $selectOptions['content'] = $this->buildSelectOptions($options, $selectOptions);
        if (array_key_exists('value', $selectOptions)) { // Work with null values
            unset($selectOptions['value']);
        }

        return $this->template('select', $selectOptions);
    }

    private function buildSelectOptions(array $options, array $selectOptions = [])
    {
        $output = '';
        foreach ($options as $key => $value) {
            if (is_array($value)) {
                $optgroup = str_replace('{label}', $key, $this->config['templates']['optgroup']);
                $output .= str_replace('{content}', $this->buildSelectOptions($value), $optgroup);
                continue;
            }

            $template = $this->config['templates']['option'];
      
            if (array_key_exists('value', $selectOptions) and $selectOptions['value'] === $key) {
                $template = $this->config['templates']['optionSelected'];
            }
            $template = str_replace('{value}', $key, $template);
            $output .= str_replace('{text}', $value, $template);
        }

        return $output;
    }

    protected function prepareOptions(string $name, array $options = [])
    {
        if (isset($options['id']) and $options['id'] === true) {
            $options['id'] = $this->domId($name);
        }
        if (!isset($options['name'])) {
            if (strpos($name, '.') === false) {
                $options['name'] = $name;
            } else {
                $parts = explode('.', $name);
                $_name = array_shift($parts);
                $options['name'] = $_name.'['.implode('][', $parts).']';
            }
        }
        if (!isset($options['maxlength'])) {
            if ($maxlength = $this->getMaxLength($name)) {
                $options['maxlength'] = $maxlength;
            }
        }
        if (!isset($options['value'])) {
            if ($this->data) {
                $entity = $this->getEntity($this->data, $name);
                $parts = explode('.', $name);
                $last = end($parts);
                
                // Get value unless overridden
                if (!isset($options['value']) and isset($entity->{$last}) and is_scalar($entity->{$last})) {
                    $options['value'] = $entity->{$last};
                }
    
                // Check Validation Errors
                if ($this->data->getError($last)) {
                    $options = $this->addClass('error', $options);
                }
            } else {
                // get data from request, if user is using different model or not supplying results. e.g is a search form
                $requestData = $this->view()->request->data();
                if ($requestData) {
                    $dot = new Dot($requestData);
                    $value = $dot->get($name);
                    if ($value) {
                        $options['value'] = $value;
                    }
                }
            }
        }
        
        
        return $options;
    }

    /**
     * Returns the max length for a field.
     *
     * @param string $name
     */
    protected function getMaxlength(string $name)
    {
        $model = $this->modelName;

        if (strpos($name, '.') !== false) {
            $parts = explode('.', $name);
            $model = array_shift($parts);
            $name = end($parts);
        }
        if (isset($this->meta[$model]['maxlength'][$name])) {
            return $this->meta[$model]['maxlength'][$name];
        }
    }

    /**
     * Go deep and get the entity from the from the path.
     *
     * @example
     *
     * 'title'  will return the enity for the current model
     * 'user.name' will return the user entity  (belongsTo/hasOne)
     * 'tags.0.tag' will return the tag entity number 0 (hasMany)
     *
     * @param Entity $entity
     * @param string $path   name, model.name, models.x.name
     *
     * @return Entity
     */
    protected function getEntity(Entity $entity, string $path)
    {
        if (strpos($path, '.') === false) {
            return $entity;
        }
        foreach (explode('.', $path) as $key) {
            $lastEntity = $entity;
            if (is_object($entity) and isset($entity->{$key})) {
                $entity = $entity->{$key};
            } elseif (is_array($entity) and isset($entity[$key])) {
                $entity = $entity[$key];
            } else {
                return null;
            }
        }

        return $lastEntity;
    }

    protected function addClass(string $class, array $options = [])
    {
        if (isset($options['class'])) {
            $options['class'] = "{$options['class']} {$class}";
        } else {
            $options['class'] = $class;
        }

        return $options;
    }

    protected function detectType(string $column)
    {
        $model = $this->modelName;
        if (strpos($column, '.') !== false) {
            $parts = explode('.', $column);
            $model = $parts[0];
            $column = end($parts);
        }

        if (substr($column, -3) === '_id') {
            return 'select';
        }
        if ($column === 'password') {
            return 'password';
        }

        if (isset($this->meta[$model])) {
            if ($this->meta[$model]['primaryKey'] === $column) {
                return 'hidden';
            }
            if (isset($this->meta[$model]['columnMap'][$column])) {
                $type = $this->meta[$model]['columnMap'][$column];

                return $this->controlMap[$type];
            }
        }

        return 'text';
    }

    protected function template(string $name, array $options = [])
    {
        $template = $this->getTemplate($name);

        if (empty($options)) {
            return $template;
        }
        $data = [];
        preg_match_all('/\{([a-z]+)\}/', $template, $matches);
        if ($matches) {
            foreach ($matches[1] as $mergeVar) {
                if ($mergeVar === 'attributes') {
                    continue;
                }
                $mergeValue = '';
                if (isset($options[$mergeVar])) {
                    $mergeValue = $options[$mergeVar];
                    unset($options[$mergeVar]);
                }
                $data[$mergeVar] = $mergeValue;
            }
        }
        // Remaining items in options are attributes
        $data['attributes'] = $this->attributesToString($options);
        return $this->templater()->format($name, $data);
    }
}
