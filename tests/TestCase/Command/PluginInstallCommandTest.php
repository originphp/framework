<?php
namespace Origin\Test\Command;

use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;
use Origin\Model\ConnectionManager;

class PluginInstallCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;


    public function startup(){

    }
  
    public function testExecute(){
        $this->exec('plugin:install originphp/originphp DummyPlugin');
        $this->assertExitSuccess();
        $this->assertOutputContains('DummyPlugin Plugin installed');
        $bootstrap = file_get_contents(CONFIG . '/bootstrap.php');
        $this->assertContains("Plugin::load('DummyPlugin')",$bootstrap);
    }

  
    public function shutdown(){
        $bootstrap = file_get_contents(CONFIG . '/bootstrap.php');
        file_put_contents(CONFIG . '/bootstrap.php',str_replace("\nPlugin::load('DummyPlugin');\n",'',$bootstrap));
        if(file_exists(PLUGINS . DS . 'dummy_plugin')){
            $this->rmdir(PLUGINS . DS . 'dummy_plugin');
        }
    }

    function rmdir($dir) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
          (is_dir("$dir/$file")) ? $this->rmdir("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
      }

  
}