<?php
namespace App\Test\Controller;

use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;
use Origin\Exception\Exception;

class BookmarksShellTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    public function testHelp()
    {
        $this->exec('bookmarks');
        $this->assertOutputContains('list     	Fetch a list of bookmarks from the db');
        $this->assertOutputContains('exception	Throws an exception so you can see the debug magic');
        $this->assertOutputContains('uninstall	Uninstalls the bookmark demo files');
        $this->assertExitSuccess();
    }
    public function testList()
    {
        $this->exec('bookmarks list');
        $this->assertOutputContains('[OriginPHP] - https://www.originphp.com');
        $this->assertExitSuccess();
    }
    public function testException()
    {
        $this->expectException(Exception::class);
        $this->exec('bookmarks exception');
    }
    public function testUninstallNo()
    {
        $this->exec('bookmarks uninstall', ['no']);
        $this->assertOutputContains('The following files will deleted');
        $this->assertExitSuccess();
    }
}
