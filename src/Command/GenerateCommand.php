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
        'behavior',
        'command',
        'component',
        'controller',
        'helper',
        'model',
        'middleware',
        'migration',
        'plugin',
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
                'controller, helper,model,middleware, migration and plugin'], 'required' => true, ]
        );
        $this->addArgument('name', [
            'description' => 'This is a mixed case name, e.g Contact,ContactAddress,Plugin.Product', 'required' => true, ]
        );
        $this->addArgument('params', [
            'description' => [
                'Additional params to be passed to generator. For controllers this will be action names',
            'seperated by spaces. For models it coulmn:type also seperated by spaces.'],
            'type' => 'array',
        ]);
    }

    public function execute()
    {
        $generator = $this->arguments('generator');
        if (!$this->isValidGenerator($generator)) {
            $this->io->error("Unkown generator {$generator}");

            $this->out('The available generators are:');
            foreach ($this->generators as $generator) {
                $this->io->list($generator);
            }
            $this->abort();
        }

        $name = $this->arguments('name');
        if (!$this->isValidName($name)) {
            $this->io->error('Invalid name format. Should be mixed case Product,ContactManager');
            $this->abort();
        }

        list($plugin, $name) = pluginSplit($name);

        $data = [
                'name' => $name,  // Product // StudlyCaps/PascalCase
                'plugin' => $plugin,
                'underscored' => Inflector::underscore($name),
                'namespace' => $plugin ? $plugin : 'App',
            ];

        return $this->{$generator}($data);
    }

    protected function behavior(array $data)
    {
        $this->generate(
            $this->getTemplateFilename('behavior'),
            $this->getBaseFolder($this->arguments('name'), self::SRC).DS.'Model'.DS.'Behavior'.DS."{$data['name']}Behavior.php",
            $data
        );
    }

    protected function command(array $data)
    {
        $data['custom'] = str_replace('_', '-', Inflector::underscore($data['namespace']).':'.$data['underscored']);

        $this->generate(
            $this->getTemplateFilename('command'),
            $this->getBaseFolder($this->arguments('name'), self::SRC).DS.'Command'.DS."{$data['name']}Command.php",
            $data
        );

        $this->generate(
            $this->getTemplateFilename('command_test'),
            $this->getBaseFolder($this->arguments('name'), self::TEST).DS.'Command'.DS."{$data['name']}CommandTest.php",
            $data
        );
    }

    
    protected function controller(array $data)
    {
        $data['model'] = Inflector::singularize($data['name']);
        $data['methods'] = '';

        $controllerMethods = $testMethods = '';

        $params = $this->arguments('params');
        if ($params) {
            foreach ($params as $method) {
                $controllerMethods .= "\tfunction {$method}(){\n\t}\n\n";
                $testMethods .= "\tfunction test".ucfirst($method)."(){\n\t}\n\n";
            }
        }

        $data['methods'] = $controllerMethods;
        $this->generate(
            $this->getTemplateFilename('controller'),
            $this->getBaseFolder($this->arguments('name'), self::SRC).DS.'Controller'.DS."{$data['name']}Controller.php",
            $data
        );
        $data['methods'] = $testMethods;
        $this->generate(
            $this->getTemplateFilename('controller_test'),
            $this->getBaseFolder($this->arguments('name'), self::TEST).DS.'Controller'.DS."{$data['name']}ControllerTest.php",
            $data
        );
    }

    protected function component(array $data)
    {
        $this->generate(
            $this->getTemplateFilename('component'),
            $this->getBaseFolder($this->arguments('name'), self::SRC).DS.'Controller'.DS.'Component'.DS."{$data['name']}Component.php",
            $data
        );
    }

    protected function helper(array $data)
    {
        $this->generate(
            $this->getTemplateFilename('helper'),
            $this->getBaseFolder($this->arguments('name'), self::SRC).DS.'View'.DS.'Helper'.DS."{$data['name']}Helper.php",
            $data
        );
    }

    protected function middleware(array $data)
    {
        $this->generate(
            $this->getTemplateFilename('middleware'),
            $this->getBaseFolder($this->arguments('name'), self::SRC).DS.'Middleware'.DS."{$data['name']}Middleware.php",
            $data
        );
    }

    protected function migration(array $data)
    {
        $data += ['code' => ''];

        $version = date('Ymdhis');
        $this->generate(
            $this->getTemplateFilename('migration'),
            ROOT.DS.'db'.DS.'migrate'.DS."{$version}{$data['name']}.php",
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
        $this->generate(
            $this->getTemplateFilename('model'),
            $this->getBaseFolder($this->arguments('name'), self::SRC).DS.'Model'.DS."{$data['name']}.php",
            $data
        );

        $this->generate(
            $this->getTemplateFilename('model_test'),
            $this->getBaseFolder($this->arguments('name'), self::TEST).DS.'Model'.DS."{$data['name']}Test.php",
            $data
        );
        $fixtureFolder = str_replace('TestCase', 'Fixture', $this->getBaseFolder($this->arguments('name'), self::TEST));
        $this->generate(
            $this->getTemplateFilename('model_fixture'),
            $this->getBaseFolder($this->arguments('name'), self::TEST).DS.'Model'.DS."{$data['name']}Fixture.php",
            $data
        );

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
            $export = $this->varExport($schema);
            $table = Inflector::tableize($data['name']);

            $data['name'] = 'Create'.$data['name'].'Table';
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

        $path = PLUGINS.DS.Inflector::underscore($data['name']);
        foreach ($structure as $folder) {
            $directory = $path.DS.$folder;
            if (!file_exists($directory)) {
                $this->createDirectory($directory);
            }
        }

        $directory = PLUGINS.DS.Inflector::underscore($data['name']).DS.'src';

        $this->generate(
            $this->getTemplateFilename('plugin_controller'),
            $directory.DS.'Controller'.DS."{$data['name']}AppController.php",
            $data
        );
        $this->generate(
            $this->getTemplateFilename('plugin_model'),
            $directory.DS.'Model'.DS."{$data['name']}AppModel.php",
            $data
        );
        $this->generate(
            $this->getTemplateFilename('plugin_routes'),
            PLUGINS.DS.Inflector::underscore($data['name']).DS.'config'.DS.'routes.php',
            $data
        );

        $this->generate(
            $this->getTemplateFilename('phpunit'),
            PLUGINS.DS.Inflector::underscore($data['name']).DS.'phpunit.xml',
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
        if ($src === self::SRC) {
            if ($plugin) {
                return PLUGINS.DS.$plugin.DS.'src';
            }

            return SRC;
        }
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
        if (!file_exists($input)) {
            $this->throwError("{$input} could not be found");
        }
        $content = file_get_contents($input);
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $content = str_replace('%'.$key.'%', $value, $content);
            }
        }
        pr($content);

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
        $result = $this->io->createFile($filename, $content);
        $filename = str_replace(ROOT.DS, '', $filename);
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
        return in_array($generator, $this->generators);
    }

    protected function isValidName(string $name)
    {
        return preg_match('/^([A-Z]+[a-z0-9]+)+/', $name);
    }
}
