<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Make\Console;

use Origin\Console\Shell;
use Origin\Model\ConnectionManager;
use Origin\Core\Inflector;
use Origin\Exception\Exception; // @todo a different exception?
use Origin\View\Templater;
use Make\Utils\MakeTemplater;

/**
*  Reference
*  [model] => BookmarksTag
*  [controller] => BookmarksTags
*  [singularName] => bookmarksTag
*  [pluralName] => bookmarksTags
*  [singularHuman] => Bookmarks Tag
*  [pluralHuman] => Bookmarks Tags
*  [singularHumanLower] => bookmarks tag
*  [pluralHumanLower] => bookmarks tags
*/


class MakeShell extends Shell
{
    /**
     * Meta information from introspecting the database.
     * Contains the following keys vars, associations, schema, validate
     *
     * @var array
     */
    protected $meta = [];
    
    protected $force = false;

    protected function introspectDatabase()
    {
        $this->loadTask('Make.Make');
        $this->Make->introspectDatabase();
        $this->meta = $this->Make->build();
    }
    public function initialize(array $arguments)
    {
        if (!file_exists(CONFIG . DS . 'database.php')) {
            $this->out('<danger>No database configuration found. </danger>');
            $this->out('Create config/database.php using the template in the same directory.');
            return;
        }
        $this->introspectDatabase();
        # handle forcing
        $key = array_search('-force', $this->args);
        if ($key !== false) {
            unset($this->args[$key]);
            $this->force = true;
        }
    }
    public function main()
    {
        $this->showUsage();
    }
    public function showUsage()
    {
        $this->out('make all');
        $this->out('make all Contact');
        $this->out('make model Contact');
        $this->out('make controller Contacts');
        $this->out('make view Contacts');
        $this->out('make plugin ContactManager');
        $this->out('');
        $this->out('You can use -force to not prompt');
        //$this->out('make test Lead'); /**@todo test */
    }

    public function plugin()
    {
        if (empty($this->args)) {
            throw new Exception('You must speficify a plugin name');
        }
        $plugin = $this->args[0];
        $underscored = Inflector::underscore($plugin);
        
        $path = PLUGINS . DS. $underscored;
        if (file_exists($path)) {
            throw new Exception(sprintf('Plugin folder %s already exists', $underscored));
        }
        $folders = [
            $path,
            $path . DS . 'src',
            $path . DS . 'tests',
            $path . DS . 'src' . DS . 'config',
            $path . DS . 'src' . DS . 'Console',
            $path . DS . 'src' . DS . 'Controller',
            $path . DS . 'src' . DS . 'Controller' . DS . 'Component',
            $path . DS . 'src' . DS . 'Model',
            $path . DS . 'src' . DS . 'Model' . DS . 'Behavior',
            $path . DS . 'src' . DS . 'View',
            $path . DS . 'src' . DS . 'View' . DS . 'Helper',
        ];
        foreach ($folders as $folder) {
            if (!mkdir($folder)) {
                throw new Exception('Error creating folder');
            }
        }
        $data = [
            'plugin' => $plugin,
            'underscored' => $underscored
        ];
    
        $Templater = new MakeTemplater();
        $result = $Templater->generate('plugin/routes', $data);
        if (!file_put_contents($path. DS . 'src' . DS .'config' . DS .'routes.php', $result)) {
            throw new Exception('Error writing file');
        }
        $result = $Templater->generate('plugin/controller', $data);
        if (!file_put_contents($path. DS . 'src' . DS .'Controller' . DS . $data['plugin']. 'AppController.php', $result)) {
            throw new Exception('Error writing file');
        }
        $result = $Templater->generate('plugin/model', $data);
        if (!file_put_contents($path. DS . 'src' . DS .'Model' . DS . $data['plugin']. 'AppModel.php', $result)) {
            throw new Exception('Error writing file');
        }
    }

    public function all()
    {
        if (empty($this->args)) {
            $models = $this->getAvailable();
            $this->out('Generate Model, View and Controller for each of the following models:');
            $this->out('');
            foreach ($models as $model) {
                $this->out("<white>- {$model}</white>");
            }
            $this->out('<yellow>hasAndBelongsToMany wont be listed here.</yellow>');
            $result = $this->in('Do you want to continue?', ['y','n'], 'n');
            if ($result === 'n') {
                return;
            }
        } else {
            $models = $this->args;
        }
       
        foreach ($models as $model) {
            $controller = Inflector::pluralize($model);
            $this->controller($controller);
            $this->model($model);
            $this->view($controller);
        }
    }

    public function in(string $prompt, array $options = [], string $default = null)
    {
        if ($this->force) {
            return 'y';
        }
        return parent::in($prompt, $options, $default);
    }

    public function controller(string $controller = null)
    {
        if ($controller === null and isset($this->args[0])) {
            $controller = $this->args[0];
        }
        if ($controller === null) {
            $this->showAvailable(true);
            return ;
        }
        $options = $this->getAvailable(true);
        
        if (in_array($controller, $options) === false) {
            throw new Exception(sprintf('Invalid controller %s', $controller));
        }
        $controller =$controller;

        $filename = SRC . DS . 'Controller' .DS . $controller .'Controller.php';
        if (file_exists($filename)) {
            $result = $this->in(sprintf('%sController already exist, overwrite?', $controller), ['y','n'], 'n');
            if ($result === 'n') {
                exit;
            }
        }

        $model = Inflector::singularize($controller);
        $data = $this->getData($model);
       
        $belongsTo = $this->meta['associations'][$model]['belongsTo'];

        // Create Block Data Controller
        $data['blocks'] = []; // Controller Blocks
        $compact = [ $data['singularName'] ];
        foreach ($belongsTo as $otherModel) {
            // foreignKey exists
            if (isset($this->meta['vars'][$otherModel])) {
                $vars = $this->meta['vars'][$otherModel];
                $vars['currentModel'] = $model;
                $compact[] = $vars['pluralName'];
                $data['blocks'][] = $vars;
            }
        }
        $data['compact'] = implode("','", $compact);

        $Templater = new MakeTemplater();
        $result = $Templater->generate('controller', $data);
        if (!file_put_contents($filename, $result)) {
            throw new Exception('Error writing file');
        }
        $this->status(sprintf('%s controller', $controller), 'ok');
    }

    public function model(string $model = null)
    {
        if ($model === null and isset($this->args[0])) {
            $model = $this->args[0];
        }
        if ($model === null) {
            $this->showAvailable();
            return ;
        }
        $options = $this->getAvailable();
        
        if (in_array($model, $options) === false) {
            throw new Exception(sprintf('Invalid model %s', $this->args[0]));
        }

        $filename = SRC . DS . 'Model' .DS .$model .'.php';
        if (file_exists($filename)) {
            $result = $this->in(sprintf('%s model already exist, overwrite?', $model), ['y','n'], 'n');
            if ($result === 'n') {
                exit;
            }
        }

        $data = $this->getData($model);
        // Wont use Record blocks since, we validation rules and assocations are two different things
        // Load Assocations
        $data['initialize'] = '';
        $associations = $this->meta['associations'][$model];
        foreach ($associations as $association => $models) {
            if ($models) {
                foreach ($models as $associatedModel) {
                    $data['initialize'] .= '$this->' . $association . "('{$associatedModel}');\n";
                }
            }
        }
        $validationRules = [];
        // Add Validation Rules
        $validate = $this->meta['validate'][$model];
        foreach ($validate as $field => $rules) {
            if ($rules) {
                $buffer = [];
                $validationRules[$field] = [];
                foreach ($rules as $rule) {
                    if (count($rules) === 1) {
                        $validationRules[$field] = [ 'rule' => $rule];
                    } else {
                        $validationRules[$field][$rule] = [ 'rule' => $rule];
                    }
                }
                $export = var_export($validationRules[$field], true);
                $data['initialize'] .= '$this->' . "validate('{$field}',{$export});\n";
            }
        }

        $Templater = new MakeTemplater();
        $result = $Templater->generate('model', $data);
        if (!file_put_contents($filename, $result)) {
            throw new Exception('Error writing file');
        }
        $this->status(sprintf('%s model', $model), 'ok');
    }

    public function view(string $controller = null)
    {
        if ($controller === null and isset($this->args[0])) {
            $controller = $this->args[0];
        }
        if ($controller === null) {
            $this->showAvailable(true);
            return ;
        }
        $options = $this->getAvailable(true);
        
        if (in_array($controller, $options) === false) {
            throw new Exception(sprintf('Invalid controller %s', $controller));
        }

        $folder = SRC . DS . 'View' . DS . $controller ;
        if (file_exists($folder)) {
            $result = $this->in(sprintf('%s views already exist, overwrite?', $controller), ['y','n'], 'n');
            if ($result === 'n') {
                exit;
            }
        } else {
            mkdir($folder, 0775);
        }

        $model = Inflector::singularize($controller);
        $data = $this->getData($model);
       
        $data += [
            'controllerUnderscored' => Inflector::underscore($controller)
        ];
        $Templater = new MakeTemplater();

        foreach (['add','edit','index','view'] as $view) {
            $result = $Templater->generate('View/'. $view, $data);
            // create related lists
            if ($view === 'view') {
                $associations = $this->meta['associations'][$model];
                $related = array_merge($associations ['hasMany'], $associations ['hasAndBelongsToMany']);
                $relatedList = '';
                foreach ($related as $associated) {
                    $relatedList .= $Templater->generate('View/view_related', $this->getData($associated));
                }
                $result = str_replace('{RELATEDLISTS}', $relatedList, $result);//One off tag this allows user to wrap in div etc
            }
   
            if (!file_put_contents($folder . DS . $view . '.ctp', $result)) {
                throw new Exception('Error writing file');
            }
        }
        $this->status(sprintf('%s views', $controller), 'ok');
    }

    

    protected $statusCodes = [
        'ok' => 'green',
        'error' => 'red',
        'ignore' => 'yellow'
    ];

    public function status(string $message, string $code)
    {
        $color = $this->statusCodes[$code];
        $status = strtoupper($code);
        $this->out("<white>[</white> <{$color}>{$status}</{$color}> <white>] {$message}</white>");
    }

    protected function getData(string $model)
    {
        $data = $this->meta['vars'][$model];
        $data['primaryKey'] = $this->Make->primaryKey($model);
        $fields = array_keys($this->meta['schema'][$model]);
        $key = array_search($data['primaryKey'], $fields);
        if ($key !== false) {
            unset($fields[$key]);
        }
        /**
         * Create a block for each field
         */
        $blocks = [];
        foreach ($fields as $field) {
            $block = $data;
            $block['field'] = $field;
            $block['fieldName'] = Inflector::humanize(Inflector::underscore($field));
            $blocks[] = $block;
        }
        $data['blocks'] = $blocks;
        return $data;
    }

    protected function showAvailable($plural=false)
    {
        $this->out('<cyan>Available Choices:</cyan>');
        foreach ($this->getAvailable($plural) as $item) {
            $this->out('<white>' . $item  . '</white>');
        }
    }

    protected function getAvailable($isPlural=false)
    {
        $data = array_keys($this->meta['schema']);
        if ($isPlural) {
            array_walk($data, function (&$value, &$key) {
                $value = Inflector::pluralize(($value));
            });
        }
 
        return $data;
    }
}
