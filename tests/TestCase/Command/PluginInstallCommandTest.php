<?php
namespace Origin\Test\Command;

use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;
use Origin\Model\ConnectionManager;
use Origin\Command\PluginInstallCommand;
use Origin\TestSuite\TestTrait;
use Origin\Console\ConsoleIo;
use Origin\TestSuite\Stub\ConsoleOutput;

class MockPluginInstallCommand extends PluginInstallCommand
{
    use TestTrait;
}

/**
 * Its a mockery trying to test this
 */

class PluginInstallCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;
  
    public function testGetUrl(){
        $cmd = new MockPluginInstallCommand();
        $this->assertEquals(
            'https://github.com/originphp/originphp.git',
            $cmd->callMethod('getUrl',['originphp/originphp'])
        );
    }

    public function testGetPlugin(){
        $cmd = new MockPluginInstallCommand();
        $this->assertEquals(
            'user_management',
            $cmd->callMethod('getPlugin',['https://github.com/originphp/originphp.git','UserManagement'])
        );

        $this->assertEquals(
            'funky_name',
            $cmd->callMethod('getPlugin',['https://github.com/originphp/FunkyName.git'])
        );
    }

    public function testRunSuccess(){
        $cmd = $this->getMockBuilder(PluginInstallCommand::class)
                     ->setMethods(['download'])
                     ->getMock();

                     $cmd->expects($this->once())
                     ->method('download')
                     ->willReturn(true);
    

        $bufferedOutput = new ConsoleOutput();
        $cmd->io = new ConsoleIo($bufferedOutput,new ConsoleOutput());
 
        $cmd->run(['originphp/originphp','UserManagement']);
        $this->assertContains('UserManagement Plugin installed',$bufferedOutput->read());
        $bootstrap = file_get_contents(CONFIG . '/bootstrap.php');
        file_put_contents(CONFIG . '/bootstrap.php',str_replace("\nPlugin::load('UserManagement');\n",'',$bootstrap));
    }

    public function testRunError(){
        $cmd = $this->getMockBuilder(PluginInstallCommand::class)
                     ->setMethods(['download','appendBootstrap'])
                     ->getMock();

                     $cmd->expects($this->once())
                     ->method('download')
                     ->willReturn(false);
    

        $bufferedOutput = new ConsoleOutput();
        $cmd->io = new ConsoleIo($bufferedOutput,$bufferedOutput);
 
        $cmd->run(['originphp/originphp','UserManagement']);
        $this->assertContains('Plugin not downloaded from `https://github.com/originphp/originphp.git`',$bufferedOutput->read());
    }

    public function testInvalidPluginName(){
        $this->exec('plugin:install cool/repo abc-123');
        $this->assertExitError();
        $this->assertErrorContains('Plugin name `abc-123` is invalid');
    }

    public function testPluginAlreadyExists(){
        $this->exec('plugin:install cool/repo Make');
        $this->assertExitError();
        $this->assertErrorContains('Plugin `make` already exists');
    }

    /*
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
      */
  
}