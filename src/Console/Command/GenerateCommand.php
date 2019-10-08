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
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Origin\Console\Command;

use Origin\Utility\Inflector;
use Origin\Model\ConnectionManager;

class GenerateCommand extends Command
{
    const SRC = 1;
    const TEST = 2;

    protected $name = 'generate';
    protected $description = 'Generates new code files';

    /**
     * Directory where templates are stored.
     *
     * @var string
     */
    protected $directory = SRC . '/templates';

    /**
     * Generators array and list of templates they will process.
     *
     * @var array
     */
    protected $generators = [
        'concern_controller' => 'Generates a Concern for a Controller',
        'concern_model' => 'Generates a Concern for a Model',
        'command' => 'Generates a Command class',
        'component' => 'Generates a Component class',
        'controller' => 'Generates a Controller class',
        'entity' => 'Generates an Entity class',
        'helper' => 'Generates a Helper class',
        'job' => 'Generates a Job class',
        'listener' => 'Generates a Listener class',
        'mailer' => 'Generates a Mailer class',
        'model' => 'Generates a Model class',
        'middleware' => 'Generates a Middleware class',
        'migration' => 'Generates a Migration class',
        'plugin' => 'Generates a Plugin skeleton',
        'repository' => 'Generates a Repository for a Model',
        'scaffold' => 'Generates a MVC using the database',
        'service' => 'Generates a Service object class',
    ];

    public function initialize() : void
    {
        if (! file_exists($this->directory)) {
            $this->directory = ORIGIN . DS . 'templates'; // default
        }

        $this->addArgument(
            'generator',
            [
                'description' => [
                    'The name of the generator. Generators include: behavior,command,component',
                    'controller, helper,model,middleware, migration and plugin', ], ]
        );
        $this->addArgument(
            'name',
            [
                'description' => 'This is a mixed case name, e.g Contact,ContactAddress,Plugin.Product', 'required' => false, ]
        );
        $this->addArgument('params', [
            'description' => [
                'Additional params to be passed to generator. For controllers this will be action names',
                'seperated by spaces. For models it coulmn:type also seperated by spaces.', ],
            'type' => 'array',
        ]);
        $this->addOption('force', [
            'description' => 'Forces file overwriting',
            'type' => 'boolean',
            'default' => false,
        ]);

        $this->addOption('connection', [
            'description' => 'The datasource to use for the database',
            'default' => 'default',
        ]);
    }

    public function execute() : void
    {
        $generator = $this->arguments('generator');
        $name = $this->arguments('name');

        // Go Interactive
        if (empty($this->arguments())) {
            $this->out('<yellow>Generators:</yellow>');
            foreach ($this->generators as $generator => $description) {
                $generator = str_pad($generator, 20, ' ');
                $this->io->text("<code>{$generator}</code> <white>{$description}</white>");
            }
            $this->out('');
            $generator = $this->io->ask('Which generator?');

            if ($this->isValidGenerator($generator)) {
                $name = $this->io->ask('Enter a name e.g. Single,DoubleWord');
            }
        }

        if (! $this->isValidGenerator($generator)) {
            $this->io->error("Unkown generator {$generator}");
            $this->abort();
        }

        if (! $name) {
            $this->io->error('You must provide a name e.g. Single,DoubleWord');
            $this->abort();
        }

        if (! $this->isValidName($name)) {
            $this->io->error('Invalid name format. Should be mixed case Product,ContactManager');
            $this->abort();
        }

        list($plugin, $class) = pluginSplit($name);

        $data = [
            'name' => $name,
            'class' => $class,  // Product // StudlyCaps/PascalCase
            'plugin' => $plugin,
            'underscored' => Inflector::underscored($class),
            'namespace' => $plugin ? $plugin : 'App',
        ];

        $this->{$generator}($data);
    }

    protected function entity(array $data)
    {
        $this->generate(
            $this->getTemplateFilename('entity'),
            $this->getBaseFolder($data['name'], self::SRC).DS.'Model'.DS.'Entity'.DS."{$data['class']}.php",
            $data
        );

        $this->generate(
            $this->getTemplateFilename('entity_test'),
            $this->getBaseFolder($data['name'], self::TEST).DS.'Model'.DS.'Entity'.DS."{$data['class']}Test.php",
            $data
        );
    }

    protected function command(array $data)
    {
        $data['custom'] = str_replace('_', '-', $data['underscored']);
    
        $this->generate(
            $this->getTemplateFilename('command'),
            $this->getBaseFolder($data['name'], self::SRC).DS.'Console'.DS.'Command'.DS."{$data['class']}Command.php",
            $data
        );

        $this->generate(
            $this->getTemplateFilename('command_test'),
            $this->getBaseFolder($data['name'], self::TEST).DS.'Console'.DS.'Command'.DS."{$data['class']}CommandTest.php",
            $data
        );
    }

    protected function controller(array $data)
    {
        $data['model'] = Inflector::singular($data['class']);
        $data['methods'] = '';
        $data['human'] = Inflector::human($data['class']);
      
        $controllerMethods = $testMethods = '';

        $params = $this->arguments('params');
        
        if ($params) {
            $string = '$this->markTestIncomplete(\'This test has not been implemented yet.\');';
           
            foreach ($params as $method) {
                if (preg_match('/^[a-z_0-9]+/', $method)) {
                    $method = Inflector::camelCase($method);
                    $controllerMethods .= "    function {$method}()\n    {\n    }\n\n";
                    $testMethods .= '    function test'. ucfirst($method) ."()\n    {\n        {$string}\n    }\n\n";
                }
            }
        }

        $data['methods'] = $controllerMethods;
        $this->generate(
            $this->getTemplateFilename('controller'),
            $this->getBaseFolder($data['name'], self::SRC).DS.'Http'.DS.'Controller'.DS."{$data['class']}Controller.php",
            $data
        );

        if ($params) {
            foreach ($params as $method) {
                $this->generate(
                    $this->getTemplateFilename('view'),
                    $this->getBaseFolder($data['name'], self::SRC).DS.'Http'.DS.'View'.DS.$data['class'] .DS. "{$method}.ctp",
                    ['action' => Inflector::human($method)] + $data
                );
            }
        }
        
        $data['methods'] = $testMethods;
        $this->generate(
            $this->getTemplateFilename('controller_test'),
            $this->getBaseFolder($data['name'], self::TEST).DS.'Http'.DS.'Controller'.DS."{$data['class']}ControllerTest.php",
            $data
        );
    }

    protected function concern_controller(array $data)
    {
        $this->generate(
            $this->getTemplateFilename('concern_controller'),
            $this->getBaseFolder($data['name'], self::SRC).DS.'Http'.DS.'Controller'.DS.'Concern'.DS."{$data['class']}.php",
            $data
        );

        $this->generate(
            $this->getTemplateFilename('concern_controller_test'),
            $this->getBaseFolder($data['name'], self::TEST).DS.'Http'.DS.'Controller'.DS.'Concern'.DS."{$data['class']}Test.php",
            $data
        );
    }

    protected function concern_model(array $data)
    {
        $this->generate(
            $this->getTemplateFilename('concern_model'),
            $this->getBaseFolder($data['name'], self::SRC).DS.'Model'.DS.'Concern'.DS."{$data['class']}.php",
            $data
        );

        $this->generate(
            $this->getTemplateFilename('concern_model_test'),
            $this->getBaseFolder($data['name'], self::TEST).DS.'Model'.DS.'Concern'.DS."{$data['class']}Test.php",
            $data
        );
    }

    protected function component(array $data)
    {
        $this->generate(
            $this->getTemplateFilename('component'),
            $this->getBaseFolder($data['name'], self::SRC).DS.'Http'.DS.'Controller'.DS.'Component'.DS."{$data['class']}Component.php",
            $data
        );

        $this->generate(
            $this->getTemplateFilename('component_test'),
            $this->getBaseFolder($data['name'], self::TEST).DS.'Http'.DS.'Controller'.DS.'Component'.DS."{$data['class']}ComponentTest.php",
            $data
        );
    }
    protected function helper(array $data)
    {
        $this->generate(
            $this->getTemplateFilename('helper'),
            $this->getBaseFolder($data['name'], self::SRC).DS.'Http'.DS.'View'.DS.'Helper'.DS."{$data['class']}Helper.php",
            $data
        );

        $this->generate(
            $this->getTemplateFilename('helper_test'),
            $this->getBaseFolder($data['name'], self::TEST).DS.'Http'.DS.'View'.DS.'Helper'.DS."{$data['class']}HelperTest.php",
            $data
        );
    }
    protected function mailer(array $data)
    {
        $this->generate(
            $this->getTemplateFilename('mailer'),
            $this->getBaseFolder($data['name'], self::SRC).DS.'Mailer'.DS."{$data['class']}Mailer.php",
            $data
        );

        $this->generate(
            $this->getTemplateFilename('mailer_test'),
            $this->getBaseFolder($data['name'], self::TEST).DS.'Mailer'.DS."{$data['class']}MailerTest.php",
            $data
        );
 
        $input = $this->getTemplateFilename('mailer_html');
        $out = $this->getBaseFolder($data['name'], self::SRC).DS.'Mailer'.DS.'Template'.DS. $data['underscored'] . '.html.ctp';
        $this->saveGeneratedCode($out, file_get_contents($input));

        $input = $this->getTemplateFilename('mailer_text');
        $out = $this->getBaseFolder($data['name'], self::SRC).DS.'Mailer'.DS.'Template'.DS. $data['underscored'] . '.text.ctp';
        $this->saveGeneratedCode($out, file_get_contents($input));
    }

    protected function job(array $data)
    {
        $this->generate(
            $this->getTemplateFilename('job'),
            $this->getBaseFolder($data['name'], self::SRC).DS.'Job'.DS."{$data['class']}Job.php",
            $data
        );

        $this->generate(
            $this->getTemplateFilename('job_test'),
            $this->getBaseFolder($data['name'], self::TEST).DS.'Job'.DS."{$data['class']}JobTest.php",
            $data
        );
    }

    protected function repository(array $data)
    {
        $data['model'] = Inflector::singular($data['class']);
        
        $this->generate(
            $this->getTemplateFilename('repository'),
            $this->getBaseFolder($data['name'], self::SRC).DS . 'Model' . DS. 'Repository'.DS."{$data['class']}Repository.php",
            $data
        );

        $this->generate(
            $this->getTemplateFilename('repository_test'),
            $this->getBaseFolder($data['name'], self::TEST).DS . 'Model' . DS. 'Repository'.DS."{$data['class']}RepositoryTest.php",
            $data
        );
    }

    protected function service(array $data)
    {
        $this->generate(
            $this->getTemplateFilename('service'),
            $this->getBaseFolder($data['name'], self::SRC).DS.'Service'.DS."{$data['class']}Service.php",
            $data
        );

        $this->generate(
            $this->getTemplateFilename('service_test'),
            $this->getBaseFolder($data['name'], self::TEST).DS.'Service'.DS."{$data['class']}ServiceTest.php",
            $data
        );
    }

    protected function listener(array $data)
    {
        $this->generate(
            $this->getTemplateFilename('listener'),
            $this->getBaseFolder($data['name'], self::SRC).DS.'Listener'.DS."{$data['class']}Listener.php",
            $data
        );

        $this->generate(
            $this->getTemplateFilename('listener_test'),
            $this->getBaseFolder($data['name'], self::TEST).DS.'Listener'.DS."{$data['class']}ListenerTest.php",
            $data
        );
    }

    protected function middleware(array $data)
    {
        $this->generate(
            $this->getTemplateFilename('middleware'),
            $this->getBaseFolder($data['name'], self::SRC).DS.'Http'.DS.'Middleware'.DS."{$data['class']}Middleware.php",
            $data
        );

        $this->generate(
            $this->getTemplateFilename('middleware_test'),
            $this->getBaseFolder($data['name'], self::TEST).DS.'Http'.DS.'Middleware'.DS."{$data['class']}MiddlewareTest.php",
            $data
        );
    }

    protected function migration(array $data)
    {
        $data += ['code' => ''];

        $version = date('Ymdhis');
        $this->generate(
            $this->getTemplateFilename('migration'),
            APP .DS . 'database' . DS . 'migrations' . DS . "{$version}{$data['class']}.php",
            $data
        );
    }

    /**
     * Modern version of varExport
     *
     * @param array $data
     * @return string
     */
    protected function varExport(array $data) : string
    {
        $data = var_export($data, true);
        $data = str_replace(
            ['array (', "),\n", " => \n"],
            ['[', "],\n", ' => '],
            $data
        );
        $data = preg_replace('/=>\s\s+\[/i', '=> [', $data);
        $data = preg_replace("/=> \[\s\s+\]/m", '=> []', $data);

        return substr($data, 0, -1).']';
    }

    protected function model(array $data)
    {
        // Create Migration
        $params = $this->arguments('params');
        $schema = [];
        if ($params) {
            foreach ($params as $param) {
                if (strpos($param, ':') === false) {
                    $this->throwError("Invalid format for {$param}, should be name:type");
                }
                list($key, $value) = explode(':', $param);
                $schema[$key] = $value;
            }
        }

        $this->generate(
            $this->getTemplateFilename('model'),
            $this->getBaseFolder($data['name'], self::SRC).DS.'Model'.DS."{$data['class']}.php",
            $data
        );

        $this->generate(
            $this->getTemplateFilename('model_test'),
            $this->getBaseFolder($data['name'], self::TEST).DS.'Model'.DS."{$data['class']}Test.php",
            $data
        );
        $fixtureFolder = str_replace('TestCase', 'Fixture', $this->getBaseFolder($data['name'], self::TEST));
        $this->generate(
            $this->getTemplateFilename('model_fixture'),
            $fixtureFolder.DS."{$data['class']}Fixture.php",
            $data
        );

        # Generate Migration
        if ($schema) {
            $export = $this->varExport($schema);
            $table = Inflector::tableName($data['class']);

            $data['class'] = 'Create'.$data['class'].'Table';
            $data['code'] = sprintf('$this->createTable(\'%s\',%s);', $table, $export);
            $this->migration($data);
        }
    }

    public function plugin(array $data)
    {

        // Namespace should be plugin name
        $data['namespace'] = $data['class'];

        $structure = [
            'config',
            'src',
            'src' . DS . 'Http',
            'src' . DS . 'Console',
            'src' . DS . 'Console' . DS . 'Command',
            'src' . DS .'Http' . DS . 'Controller',
            'src' . DS .'Http' . DS . 'View',
            'src' . DS .'Model',
            'tests',
            'database',
        ];
        $pluginDirectory = APP.DS.'plugins';

        $path = $pluginDirectory.DS.Inflector::underscored($data['class']);
        foreach ($structure as $folder) {
            $directory = $path.DS.$folder;
            if (! file_exists($directory)) {
                $this->createDirectory($directory);
            }
        }

        $directory = $pluginDirectory.DS.Inflector::underscored($data['class']).DS.'src';

        $this->generate(
            $this->getTemplateFilename('plugin_controller'),
            $directory.DS.'Http'.DS.'Controller'.DS."{$data['class']}ApplicationController.php",
            $data
        );
        $this->generate(
            $this->getTemplateFilename('plugin_model'),
            $directory.DS.'Model'.DS."{$data['class']}ApplicationModel.php",
            $data
        );
        $this->generate(
            $this->getTemplateFilename('plugin_routes'),
            $pluginDirectory.DS.Inflector::underscored($data['class']).DS.'config'.DS.'routes.php',
            $data
        );

        $this->generate(
            $this->getTemplateFilename('plugin_bootstrap'),
            $pluginDirectory.DS.Inflector::underscored($data['class']).DS.'config'.DS.'bootstrap.php',
            $data
        );

        $this->generate(
            $this->getTemplateFilename('phpunit'),
            $pluginDirectory.DS.Inflector::underscored($data['class']).DS.'phpunit.xml',
            $data
        );

        $this->generate(
            $this->getTemplateFilename('plugin_composer'),
            $pluginDirectory.DS.Inflector::underscored($data['class']).DS.'composer.json',
            $data
        );
    }
    /*
    %model% e.g. BookmarksTag
%controller% e.g. BookmarksTags
%singularName% e.g. bookmarksTag
%pluralName% e.g. bookmarksTags
%singularHuman% e.g. Bookmarks Tag
%pluralHuman% e.g. Bookmarks Tags
%singularHumanLower% e.g. bookmarks tag
%pluralHumanLower% e.g. bookmarks tags
%controllerUnderscored% e.g. bookmarks_tags
%primaryKey% e.g. id
*/
    public function scaffold(array $data)
    {
        $datasource = $this->options('connection');
        $scaffold = new Scaffold($datasource);

        $model = $data['class'];
        $meta = $scaffold->meta();
        $models = array_keys($meta['schema']);
        if (! in_array($data['class'], $models)) {
            $this->io->error(sprintf('Unkown model %s', $data['class']));
            $this->abort();
        }
        # Prepare Data
        $vars = $meta['vars'][$model];
        $belongsTo = $meta['associations'][$model]['belongsTo'];
        $hasMany = $meta['associations'][$model]['hasMany'];
        $hasAndBelongsToMany = $meta['associations'][$model]['hasAndBelongsToMany'];
        $associated = array_merge($belongsTo, $hasMany, $hasAndBelongsToMany);
        $templateFolder = $this->directory.DS. 'scaffold';
        
        # Build Controller
        $template = file_get_contents($templateFolder . DS . 'controller.tpl');
        $blocks = [];
        $vars['compact'] = [$vars['singularName']];
        foreach ($belongsTo as $otherModel) {
            // foreignKey exists
            if (isset($meta['vars'][$otherModel])) {
                $v = $meta['vars'][$otherModel];
                $vars['compact'][] = $v['pluralName'];
                $blocks[] = [
                    'currentModel' => $model,
                    'pluralName' => $v['pluralName'],
                    'model' => $otherModel,
                ];
            }
        }
        $vars['compact'] = implode("','", $vars['compact']);
        $vars['associated'] = '';
        if ($associated) {
            $vars['associated'] = "'" . implode("','", $associated)."'";
        }
        $template = $this->buildBlocks($template, $blocks);
        $template = $this->format($template, $vars);
       
        $controller = Inflector::plural($model);
        $filename = $this->getBaseFolder($data['name'], self::SRC).DS.'Http'.DS.'Controller'.DS."{$controller}Controller.php";
        $this->saveGeneratedCode($filename, $template);
        unset($vars['compact'],$vars['associated']);
       
        # Build Model
        $template = file_get_contents($templateFolder . DS . 'model.tpl');
        $vars['initialize'] = "\n";
        $associations = $meta['associations'][$model];
        foreach ($associations as $association => $models) {
            if ($models) {
                foreach ($models as $associatedModel) {
                    $vars['initialize'] .= '       $this->' . $association . "('{$associatedModel}');\n";
                }
            }
        }
        $validationRules = [];
        $validate = $meta['validate'][$model];
        foreach ($validate as $field => $rules) {
            if ($rules) {
                $buffer = [];
                $validationRules[$field] = [];
                foreach ($rules as $rule) {
                    if (count($rules) === 1) {
                        $validationRules[$field] = ['rule' => $rule];
                    } else {
                        $validationRules[$field][$rule] = ['rule' => $rule];
                    }
                }
                $export = $this->varExport($validationRules[$field]);
                $vars['initialize'] .= '       $this->' . "validate('{$field}',{$export});\n";
            }
        }
        $filename = $this->getBaseFolder($data['name'], self::SRC).DS.'Model'.DS."{$model}.php";
        $template = $this->format($template, $vars);
        $this->saveGeneratedCode($filename, $template);

        # View
        $vars += [
            'controllerUnderscored' => Inflector::underscored($controller),
        ];
        $fields = array_keys($meta['schema'][$model]['columns']);
        $blocks = [];
        foreach ($fields as $field) {
            $block = $data;
            $block['field'] = $field;
            $block['fieldName'] = Inflector::human(Inflector::underscored($field));
            $blocks[] = $block;
        }
      
        $directory = $this->getBaseFolder($data['name'], self::SRC) . DS .'Http'.DS.'View'.DS. $controller;

        foreach (['add','edit','index','view'] as $view) {
            // $blocks = $originalBlocks;

            $template = file_get_contents($templateFolder . DS . 'view_' . $view. '.tpl');
        
            $template = $this->format($template, $vars);
            $template = $this->buildBlocks($template, $blocks);

            # Build Related
            if ($view === 'view') {
                $related = array_merge($associations['hasMany'], $associations['hasAndBelongsToMany']);
                $relatedLists = [];
                foreach ($related as $associated) {
                    $v = $meta['vars'][$associated];
                    $v['currentModel'] = lcfirst($model); // This for records
                    $t = file_get_contents($templateFolder . DS . 'view_related.tpl');
                    $t = $this->format($t, $v);
                    $fields = array_keys($meta['schema'][$associated]['columns']);
                    $blocks = [];
                    foreach ($fields as $field) {
                        $block = $data;
                        $block['field'] = $field;
                        $block['fieldName'] = Inflector::human(Inflector::underscored($field));
                        $blocks[] = $block;
                    }
                    $relatedLists[] = $this->buildBlocks($t, $blocks);
                }
                $template = str_replace('%relatedLists%', implode("\n\n", $relatedLists), $template);//One off tag this allows user to wrap in div etc
            }

            $this->saveGeneratedCode($directory . DS . $view . '.ctp', $template);
        }
    }

    protected function getTemplateFilename(string $name)
    {
        return $this->directory.DS.'generator'.DS.$name.'.tpl';
    }

    protected function getBaseFolder(string $class, $src = true)
    {
        list($plugin, $name) = pluginsplit($class);
        if ($plugin) {
            $plugin = Inflector::underscored($plugin);
        }
        // Src
        if ($src === self::SRC) {
            if ($plugin) {
                return PLUGINS.DS.$plugin.DS.'src';
            }

            return SRC;
        }
        // Tests
        if ($plugin) {
            return PLUGINS.DS.$plugin.DS.'tests'.DS.'TestCase';
        }

        return TESTS.DS.'TestCase';
    }

    /**
     * Generates code using a template and saves it.
     *
     * @param string $input
     * @param string $output
     * @param array  $data
     */
    protected function generate(string $input, string $output, array $data)
    {
        $content = $this->format(file_get_contents($input), $data);

        return $this->saveGeneratedCode($output, $content);
    }

    protected function format(string $template, array $data = [])
    {
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $template = str_replace('%' . $key . '%', $value, $template);
            }
        }

        return $template;
    }

    /**
     * Build the sub templates block
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    protected function buildBlocks(string $template, array $data = [])
    {
        if (preg_match_all('/<RECORDBLOCK>\n(.*?)<\/RECORDBLOCK>/s', $template, $matches)) {
            foreach ($matches[1] as $index => $block) {
                $recordBlock = '';
                foreach ($data as $field => $blockData) {
                    $recordBlock .= $this->format($block, $blockData);
                }
                $template = str_replace($matches[0][$index], $recordBlock, $template);
            }
        }

        return $template;
    }

    /**
     * Wrapper for directory for testing.
     *
     * @param string $directory
     *
     * @return bool
     */
    protected function createDirectory(string $directory)
    {
        return mkdir($directory, 0777, true);
    }

    protected function saveGeneratedCode(string $filename, string $content)
    {
        $this->debug("<cyan>{$filename}</cyan>\n\n<code>{$content}</code>");

        $result = $this->io->createFile($filename, $content, $this->options('force'));
        $this->io->status($result?'ok':'error', $filename);

        return $result;
    }

    protected function isValidGenerator(string $generator)
    {
        return isset($this->generators[$generator]);
    }

    protected function isValidName(string $name)
    {
        return preg_match('/^([A-Z]+[a-z0-9]+)+/', $name);
    }
}

class Scaffold
{
    protected $schema = [];

    protected $meta = [];

    public function meta()
    {
        return $this->meta;
    }

    public function __construct(string $datasource)
    {
        $this->introspectDatabase($datasource);
        $this->build();
    }

    public function introspectDatabase(string $datasource)
    {
        $connection = ConnectionManager::get($datasource);
        $tables = $connection->tables();
        foreach ($tables as $table) {
            $model = Inflector::className($table);
            $this->schema[$model] = $connection->describe($table);
        }
    }

    /**
     * Gets the validations rules array
     *
     * @return array
     */
    public function validationRules() : array
    {
        $validationRules = [];
        foreach ($this->schema as $model => $schema) {
            $validationRules[$model] = [];
            $primaryKey = (array) $this->primaryKey($model);
            foreach ($schema['columns'] as $field => $meta) {
                if (in_array($field, $primaryKey)) {
                    continue;
                }
                $validationRules[$model][$field] = [];
                if ($meta['null'] == false) {
                    $validationRules[$model][$field][] = 'notBlank';
                }
                if ($field === 'email') {
                    $validationRules[$model][$field][] = 'email';
                }
                if (in_array($field, ['url','website'])) {
                    $validationRules[$model][$field][] = 'url';
                }
                foreach (['date','datetime','time'] as $type) {
                    if ($meta['type'] === $type) {
                        $validationRules[$model][$field][] = $type;
                    }
                }
            }
        }

        return $validationRules;
    }

    /**
     * Builds an array map of vars,validation rules and associations
     *
     * @return void
     */
    public function build() :void
    {
        $models = array_keys($this->schema);
       
        $template = [
            'belongsTo' => [],
            'hasMany' => [],
            'hasAndBelongsToMany' => [],
        ];

        $associations = ['ignore' => []];
        foreach ($models as $model) {
            $associations[$model] = $template;
            $associations = $this->findBelongsTo($model, $associations);
            $associations = $this->findHasAndBelongsToMany($model, $associations);
            $associations = $this->findHasMany($model, $associations); // callLast due to ignore
        }
        $validationRules = $this->validationRules();
     
        // Remove dynamic models jointable models
        foreach ($associations['ignore'] as $remove) {
            unset($associations[$remove]);
            unset($validationRules[$remove]);
            unset($this->schema[$remove]);
        }
        unset($associations['ignore']);
        
        /**
         *  [model] => BookmarksTag
         *  [controller] => BookmarksTags
         *  [singularName] => bookmarksTag
         *  [pluralName] => bookmarksTags
         *  [singularHuman] => Bookmarks Tag
         *  [pluralHuman] => Bookmarks Tags
         *  [singularHumanLower] => bookmarks tag
         *  [pluralHumanLower] => bookmarks tags
         */
        $data = [];
        foreach ($models as $model) {
            $plural = Inflector::plural($model);
            $data[$model] = [
                'model' => $model,
                'controller' => $plural,
                'singularName' => Inflector::camelCase($model), // for vars
                'pluralName' => Inflector::camelCase($plural), // for vars
                'singularHuman' => Inflector::human(Inflector::underscored($model)),
                'pluralHuman' => Inflector::human(Inflector::underscored($plural)),
                'singularHumanLower' => strtolower(Inflector::human(Inflector::underscored($model))),
                'pluralHumanLower' => strtolower(Inflector::human(Inflector::underscored($plural))),
                'primaryKey' => $this->primaryKey($model),
            ];
        }

        $this->meta = ['vars' => $data,'associations' => $associations,'validate' => $validationRules,'schema' => $this->schema];
    }

    /**
     * Finds the belongsTo
     *
     * @param string $model
     * @param array $associations
     * @return array
     */
    public function findBelongsTo(string $model, array $associations = []) : array
    {
        $fields = $this->schema[$model]['columns'];
        $primaryKey = (array) $this->primaryKey($model);
        foreach ($fields as $field => $schema) {
            if (substr($field, -3) === '_id' and ! in_array($field, $primaryKey)) {
                $associatedModel = Inflector::studlyCaps(substr($field, 0, -3));
                $associations[$model]['belongsTo'][] = $associatedModel;
            }
        }

        return $associations;
    }
    /**
     * Finds the hasMany relations (these can also be hasOne)
     *
     * @param string $model
     * @param array $associations
     * @return array
     */
    public function findHasMany(string $model, array $associations = []) : array
    {
        $models = array_keys($this->schema);
        foreach ($models as $otherModel) {
            if ($otherModel === $model or in_array($otherModel, $associations['ignore'])) {
                continue;
            }
            $schema = $this->schema[$otherModel]['columns'];
            $foreignKey = Inflector::underscored($model) . '_id';
       
            if (isset($schema[$foreignKey])) {
                $associations[$model]['hasMany'][] = $otherModel;
            }
        }

        return $associations;
    }
    /**
     * Finds the hasAndToBelongsToMany using table names. Table name needs to be alphabetical order if not
     * it will be ignored.
     *
     * @param string $model
     * @param array $associations
     * @return array
     */
    public function findHasAndBelongsToMany(string $model, array $associations = []) : array
    {
        $models = array_keys($this->schema);
        foreach ($models as $otherModel) {
            $array = [Inflector::plural($model),Inflector::plural(($otherModel))];
            sort($array);
            $hasAndBelongsToMany = Inflector::singular(implode('', $array));
            if (isset($this->schema[$hasAndBelongsToMany])) {
                $associations[$model]['hasAndBelongsToMany'][] = $otherModel;
                if (in_array($hasAndBelongsToMany, $associations['ignore']) === false) {
                    $associations['ignore'][] = $hasAndBelongsToMany;
                }
            }
        }

        return $associations;
    }

    /**
     * Gets the primary key for a model
     *
     * @param string $model
     * @return string|array|null field
     */
    public function primaryKey(string $model)
    {
        if (isset($this->schema[$model])) {
            $schema = $this->schema[$model];
            if (isset($schema['constraints']['primary'])) {
                return $schema['constraints']['primary']['column'];
            }
        }

        return null;
    }
}
