<?php

namespace Origin\Command;

use Origin\Core\Inflector;

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
    protected $directory = ORIGIN.'/templates';

    /**
     * Generators array and list of templates they will process.
     *
     * @var array
     */
    protected $generators = [
        'behavior' => 'Generates a behavior class',
        'command' => 'Generates a command class',
        'component' => 'Generates a component class',
        'controller' => 'Generates a controller class',
        'helper' => 'Generates a helper class',
        'model' => 'Generates a model class',
        'middleware' => 'Generates a middleware class',
        'migration' => 'Generates a migration class',
        'plugin' => 'Generates a plugin skeleton',
    ];

    public function initialize()
    {
        $directory = SRC.'/templates';
        if (file_exists($directory)) {
            $this->directory = $directory;
        }
        $this->addArgument('generator', [
            'description' => [
                'The name of the generator. Generators include: behavior,command,component',
                'controller, helper,model,middleware, migration and plugin', ], ]
        );
        $this->addArgument('name', [
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
    }

    public function execute()
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

        if (!$this->isValidGenerator($generator)) {
            $this->io->error("Unkown generator {$generator}");
            $this->abort();
        }

        if (!$this->isValidName($name)) {
            $this->io->error('Invalid name format. Should be mixed case Product,ContactManager');
            $this->abort();
        }

        list($plugin, $class) = pluginSplit($name);

        $data = [
                'name' => $name,
                'class' => $class,  // Product // StudlyCaps/PascalCase
                'plugin' => $plugin,
                'underscored' => Inflector::underscore($class),
                'namespace' => $plugin ? $plugin : 'App',
            ];

        return $this->{$generator}($data);
    }

    protected function behavior(array $data)
    {
        $this->generate(
            $this->getTemplateFilename('behavior'),
            $this->getBaseFolder($data['name'], self::SRC).DS.'Model'.DS.'Behavior'.DS."{$data['class']}Behavior.php",
            $data
        );
    }

    protected function command(array $data)
    {
        $data['custom'] = str_replace('_', '-', $data['underscored']);
    
        $this->generate(
            $this->getTemplateFilename('command'),
            $this->getBaseFolder($data['name'], self::SRC).DS.'Command'.DS."{$data['class']}Command.php",
            $data
        );

        $this->generate(
            $this->getTemplateFilename('command_test'),
            $this->getBaseFolder($data['name'], self::TEST).DS.'Command'.DS."{$data['class']}CommandTest.php",
            $data
        );
    }

    protected function controller(array $data)
    {
        $data['model'] = Inflector::singularize($data['class']);
        $data['methods'] = '';

        $controllerMethods = $testMethods = '';

        $params = $this->arguments('params');
        if ($params) {
            foreach ($params as $method) {
                if (preg_match('/^[a-z_0-9]+/', $method)) {
                    $controllerMethods .= "    function {$method}(){\n    }\n\n";
                    $testMethods .= '    function test'.ucfirst($method)."(){\n    }\n\n";
                }
            }
        }

        $data['methods'] = $controllerMethods;
        $this->generate(
            $this->getTemplateFilename('controller'),
            $this->getBaseFolder($data['name'], self::SRC).DS.'Controller'.DS."{$data['class']}Controller.php",
            $data
        );
        $data['methods'] = $testMethods;
        $this->generate(
            $this->getTemplateFilename('controller_test'),
            $this->getBaseFolder($data['name'], self::TEST).DS.'Controller'.DS."{$data['class']}ControllerTest.php",
            $data
        );
    }

    protected function component(array $data)
    {
        $this->generate(
            $this->getTemplateFilename('component'),
            $this->getBaseFolder($data['name'], self::SRC).DS.'Controller'.DS.'Component'.DS."{$data['class']}Component.php",
            $data
        );
    }

    protected function helper(array $data)
    {
        $this->generate(
            $this->getTemplateFilename('helper'),
            $this->getBaseFolder($data['name'], self::SRC).DS.'View'.DS.'Helper'.DS."{$data['class']}Helper.php",
            $data
        );
    }

    protected function middleware(array $data)
    {
        $this->generate(
            $this->getTemplateFilename('middleware'),
            $this->getBaseFolder($data['name'], self::SRC).DS.'Middleware'.DS."{$data['class']}Middleware.php",
            $data
        );
    }

    protected function migration(array $data)
    {
        $data += ['code' => ''];

        $version = date('Ymdhis');
        $this->generate(
            $this->getTemplateFilename('migration'),
            APP.DS.'db'.DS.'migrate'.DS."{$version}{$data['class']}.php",
            $data
        );
    }

    protected function varExport(array $data)
    {
        $schema = var_export($data, true);
        $schema = str_replace(
            ['array (', '),', " => \n", '=>   ['],
            ['[', '],', ' => ', '=> ['], $schema);

        return substr($schema, 0, -1).']';
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
            $table = Inflector::tableize($data['class']);

            $data['class'] = 'Create'.$data['class'].'Table';
            $data['code'] = sprintf('$this->createTable(\'%s\',%s);', $table, $export);
            $this->migration($data);
        }
    }

    public function plugin(array $data)
    {
        $structure = [
            'config',
            'src',
            'src'.DS.'Command',
            'src'.DS.'Controller',
            'src'.DS.'Migration',
            'src'.DS.'Lib',
            'src'.DS.'Model',
            'src'.DS.'View',
            'tests',
            'db',
        ];
        $pluginDirectory = APP.DS.'plugins';

        $path = $pluginDirectory.DS.Inflector::underscore($data['class']);
        foreach ($structure as $folder) {
            $directory = $path.DS.$folder;
            if (!file_exists($directory)) {
                $this->createDirectory($directory);
            }
        }

        $directory = $pluginDirectory.DS.Inflector::underscore($data['class']).DS.'src';

        $this->generate(
            $this->getTemplateFilename('plugin_controller'),
            $directory.DS.'Controller'.DS."{$data['class']}AppController.php",
            $data
        );
        $this->generate(
            $this->getTemplateFilename('plugin_model'),
            $directory.DS.'Model'.DS."{$data['class']}AppModel.php",
            $data
        );
        $this->generate(
            $this->getTemplateFilename('plugin_routes'),
            $pluginDirectory.DS.Inflector::underscore($data['class']).DS.'config'.DS.'routes.php',
            $data
        );

        $this->generate(
            $this->getTemplateFilename('phpunit'),
            $pluginDirectory.DS.Inflector::underscore($data['class']).DS.'phpunit.xml',
            $data
        );
    }

    protected function getTemplateFilename(string $name)
    {
        return $this->directory.DS.'generator'.DS.$name.'.tpl';
    }

    protected function getBaseFolder(string $class, $src = true)
    {
        list($plugin, $name) = pluginsplit($class);
        if($plugin){
            $plugin = Inflector::underscore($plugin);
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
        $content = file_get_contents($input);
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $content = str_replace('%'.$key.'%', $value, $content);
            }
        }

        $this->debug("<cyan>{$output}</cyan>\n\n<code>{$content}</code>");

        return $this->saveGeneratedCode($output, $content);
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
        $result = $this->io->createFile($filename, $content, $this->options('force'));

        if ($result) {
            $this->io->status('ok', $filename);
        } else {
            $this->io->status('error', $filename);
        }
        /*$action = "<error>>  created</error>";
        if(){
         $action = "<success>>  created</success>";
        }
        $filename = str_replace(ROOT . DS,'',$filename);
        $this->out($action . "  <text>{$filename}</text>");*/
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
