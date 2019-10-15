<?php
namespace Origin\Test\Console\Command;

use Origin\Console\ConsoleIo;
use Origin\TestSuite\TestTrait;
use Origin\TestSuite\Stub\ConsoleOutput;
use Origin\Console\Command\PluginInstallCommand;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class MockPluginInstallCommand extends PluginInstallCommand
{
    use TestTrait;
}

/**
 * Its a mockery trying to test this
 */

class PluginInstallCommandTest extends \PHPUnit\Framework\TestCase
{
    use ConsoleIntegrationTestTrait;
  
    public function testGetUrl()
    {
        $cmd = new MockPluginInstallCommand();
        $this->assertEquals(
            'https://github.com/originphp/framework.git',
            $cmd->callMethod('getUrl', ['originphp/framework'])
        );
    }

    public function testGetPlugin()
    {
        $cmd = new MockPluginInstallCommand();
        $this->assertEquals(
            'user_management',
            $cmd->callMethod('getPlugin', ['https://github.com/originphp/framework.git','UserManagement'])
        );

        $this->assertEquals(
            'funky_name',
            $cmd->callMethod('getPlugin', ['https://github.com/originphp/FunkyName.git'])
        );
    }

    public function testRunSuccess()
    {
        $cmd = $this->getMockBuilder(PluginInstallCommand::class)
            ->setMethods(['download'])
            ->getMock();

        $cmd->expects($this->once())
            ->method('download')
            ->willReturn(true);
    
        $bufferedOutput = new ConsoleOutput();
        $consoleIo = new ConsoleIo($bufferedOutput, new ConsoleOutput());
        $cmd->io($consoleIo);
 
        $cmd->run(['originphp/framework','UserManagement']);
        $this->assertStringContainsString('UserManagement Plugin installed', $bufferedOutput->read());
        $bootstrap = file_get_contents(CONFIG . '/bootstrap.php');
        file_put_contents(CONFIG . '/bootstrap.php', str_replace("Plugin::load('UserManagement');\n", '', $bootstrap));
    }

    public function testRunError()
    {
        $cmd = $this->getMockBuilder(PluginInstallCommand::class)
            ->setMethods(['download','appendApplication'])
            ->getMock();

        $cmd->expects($this->once())
            ->method('download')
            ->willReturn(false);
    
        $bufferedOutput = new ConsoleOutput();
        $consoleIo = new ConsoleIo($bufferedOutput, $bufferedOutput);
        $cmd->io($consoleIo);
 
        $cmd->run(['originphp/framework','UserManagement']);
        $this->assertStringContainsString('Plugin not downloaded from `https://github.com/originphp/framework.git`', $bufferedOutput->read());
    }

    public function testInvalidPluginName()
    {
        $this->exec('plugin:install cool/repo abc-123');
        $this->assertExitError();
        $this->assertErrorContains('Plugin name `abc-123` is invalid');
    }

    public function testPluginAlreadyExists()
    {
        $this->exec('plugin:install cool/repo Make');
        $this->assertExitError();
        $this->assertErrorContains('Plugin `make` already exists');
    }

    /**
     * This is flakey but need to test it
     *
     * @return void
     */
    public function testDownload()
    {
        $command = new  MockPluginInstallCommand();
        $result = $command->callMethod('download', ['https://github.com/originphp/debug-plugin',TMP . DS . 'debug-plugin']);
        
        $this->assertTrue($result);
        $this->recursiveDelete(TMP . DS . 'debug-plugin');
    }

    private function recursiveDelete(string $directory)
    {
        $files = array_diff(scandir($directory), ['.', '..']);
        foreach ($files as $filename) {
            if (is_dir($directory . DS . $filename)) {
                $this->recursiveDelete($directory . DS . $filename);
                continue;
            }
            unlink($directory . DS . $filename);
        }

        return rmdir($directory);
    }
}
