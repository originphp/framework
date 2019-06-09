<?php

namespace Origin\Test\Command;

use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class GenerateCommandTest extends OriginTestCase
{

    use ConsoleIntegrationTestTrait;

    public $fixtures = ['Origin.Bookmark','Origin.BookmarksTag','Origin.Tag'];

    public function testGenerateScaffold()
    {
   
        $this->exec('generate --force scaffold Bookmark');
        
        $this->assertExitSuccess();
       /**
        * Run the generator on the bookmarks app and test its all working as accepted before changing Hashes
        */
        $this->assertFileHash('c871e290c6a3c66a82800231e58abc79',SRC . DS . 'Model' . DS . 'Bookmark.php');
        unlink(SRC . DS . 'Model' . DS . 'Bookmark.php');

        $this->assertFileHash('1e6187f77c1156f55da9fb97db036a54',SRC . DS . 'Controller' . DS . 'BookmarksController.php');
        unlink(SRC . DS . 'Controller' . DS . 'BookmarksController.php');

        $this->assertFileHash('a5dd94c570075bc9eb9339b65f8ffb2c',SRC . DS . 'View' . DS . 'Bookmarks' . DS . 'add.ctp');
        unlink(SRC . DS . 'View' . DS . 'Bookmarks' . DS . 'add.ctp');

        $this->assertFileHash('328574e062cef0c52cf3c4b2310b2f6e',SRC . DS . 'View' . DS . 'Bookmarks' . DS . 'edit.ctp');
        unlink(SRC . DS . 'View' . DS . 'Bookmarks' . DS . 'edit.ctp');

        $this->assertFileHash('e1ee56fedeb7936a4fa4c7d438ae9c94',SRC . DS . 'View' . DS . 'Bookmarks' . DS . 'index.ctp');
        unlink(SRC . DS . 'View' . DS . 'Bookmarks' . DS . 'index.ctp');

        $this->assertFileHash('f1109b973aa26d24ad851400d6e7ca2e',SRC . DS . 'View' . DS . 'Bookmarks' . DS . 'view.ctp');
        unlink(SRC . DS . 'View' . DS . 'Bookmarks' . DS . 'view.ctp');
    }


    public function testInvalidGenerator()
    {
        $this->exec('generate foo');
        $this->assertExitError();
        $this->assertErrorContains('Unkown generator foo');
    }

    public function testInvalidName()
    {
        $this->exec('generate command bar-foo');
        $this->assertExitError();
        $this->assertErrorContains('Invalid name format');
    }

    public function testInvalidSchema()
    {
        $this->exec('generate model Foo foo bar');
        $this->assertExitError();
        $this->assertErrorContains('Invalid format for foo, should be name:type');
    }

    public function testGenerateBehavior()
    {
        $this->exec('generate --force behavior Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Model'.DS.'Behavior'.DS.'DummyBehavior.php';
        $this->assertOutputContains('src/Model/Behavior/DummyBehavior.php');
        $this->assertTrue(file_exists($filename));
        $this->assertFileHash('867b015a8861035ef184cb4965771b9e', $filename);
        unlink($filename);
    }
    /**
     * sssdepennds testBehavior
     */
    public function testInteractive()
    {
        $this->exec('generate', ['behavior','Dummy']);
        $this->assertExitSuccess();

        $filename = SRC.DS.'Model'.DS.'Behavior'.DS.'DummyBehavior.php';
        $this->assertOutputContains('src/Model/Behavior/DummyBehavior.php');
        $this->assertTrue(file_exists($filename));
        $this->assertFileHash('867b015a8861035ef184cb4965771b9e', $filename);
        unlink($filename);
    }

    public function testGenerateCommand()
    {
        $this->exec('generate --force command Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Command'.DS.'DummyCommand.php';
        $this->assertOutputContains('src/Command/DummyCommand.php');
        $this->assertFileHash('947d6979cdaf3cac75b06a527fbf95a2', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Command'.DS.'DummyCommandTest.php';
        $this->assertOutputContains('tests/TestCase/Command/DummyCommandTest.php');
        $this->assertTrue(file_exists($filename));
        $this->assertFileHash('2c9b8b9981679b1046ae8e278be5f15a', $filename);
        unlink($filename);
    }

    public function testGenerateCommandPlugin()
    {
        $this->exec('generate --force command ContactManager.Duck');
        $this->assertExitSuccess();

        $filename =  APP.DS.'plugins'.DS.'contact_manager'.DS.'src'.DS.'Command'.DS.'DuckCommand.php';
 
        $this->assertOutputContains('contact_manager/src/Command/DuckCommand.php');
        $this->assertFileHash('756c980100dc9eda742fff6172117dc4', $filename);
        unlink($filename);

        $filename =  APP.DS.'plugins'.DS.'contact_manager'.DS.'tests'.DS.'TestCase'.DS.'Command'.DS.'DuckCommandTest.php';
     
        $this->assertOutputContains('contact_manager/tests/TestCase/Command/DuckCommandTest.php');
        $this->assertTrue(file_exists($filename));
        $this->assertFileHash('a3e958f578c2464757f9ae9f99d01b86', $filename);
        unlink($filename);

        $this->rmdir(APP.DS.'plugins'.DS.'contact_manager');
    }

    public function testGenerateComponent()
    {
        $this->exec('generate --force component Dummy');
        $this->assertExitSuccess();
        $filename = SRC.DS.'Controller'.DS.'Component'.DS.'DummyComponent.php';
        $this->assertOutputContains('src/Controller/Component/DummyComponent.php');
        $this->assertTrue(file_exists($filename));
        $this->assertFileHash('bba33e474254126d2544f41abe7c857e', $filename);
        unlink($filename);
    }

    public function testGenerateController()
    {
        $this->exec('generate --force controller Dummies');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Controller'.DS.'DummiesController.php';
        $this->assertOutputContains('src/Controller/DummiesController.php');
        $this->assertTrue(file_exists($filename));
        $this->assertFileHash('81465b7ec67173d7509485d126629e74', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Controller'.DS.'DummiesControllerTest.php';
        $this->assertOutputContains('tests/TestCase/Controller/DummiesControllerTest.php');
        $this->assertTrue(file_exists($filename));
        $this->assertFileHash('5ffcf940dc5b0fdd8b9e84717f5c25e9', $filename);
        unlink($filename);
    }

    public function testGenerateControllerWithActions()
    {
        $this->exec('generate --force controller Dummies index get_user');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Controller'.DS.'DummiesController.php';
        $this->assertOutputContains('src/Controller/DummiesController.php');
        $this->assertTrue(file_exists($filename));
        $this->assertFileHash('136dbf2be60889e47afd4602b26482a5', $filename);
        unlink($filename);

        $filename = SRC.DS.'View'.DS.'Dummies'.DS .'index.ctp';
        $this->assertOutputContains('src/View/Dummies/index.ctp');
        $this->assertTrue(file_exists($filename));
        $this->assertFileHash('af90a7a0bfcd3a6ff30c0aac82c94c16', $filename);
        unlink($filename);

        $filename = SRC.DS.'View'.DS.'Dummies'.DS .'get_user.ctp';
        $this->assertOutputContains('src/View/Dummies/get_user.ctp');
        $this->assertTrue(file_exists($filename));
        $this->assertFileHash('9263ed82c0e1859690365808dcd719b0', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Controller'.DS.'DummiesControllerTest.php';
        $this->assertOutputContains('tests/TestCase/Controller/DummiesControllerTest.php');
        $this->assertTrue(file_exists($filename));
        $this->assertFileHash('9adf0228b86c271a6c4e625669c3d04e', $filename);
        unlink($filename);
    }

    public function testGenerateHelper()
    {
        $this->exec('generate --force helper Dummy');
        $this->assertExitSuccess();
        $filename = SRC.DS.'View'.DS.'Helper'.DS.'DummyHelper.php';

        $this->assertOutputContains('src/View/Helper/DummyHelper.php');
        $this->assertTrue(file_exists($filename));
        $this->assertFileHash('4bcedbb8cdb17badd16d992f0f9c6f68', $filename);
        unlink($filename);
    }

    public function testGenerateModel()
    {
        $this->exec('generate --force model Dummy name:string description:text');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Model'.DS.'Dummy.php';
        $this->assertOutputContains('src/Model/Dummy.php');
        $this->assertTrue(file_exists($filename));
        $this->assertFileHash('bf5848a931354bddf853b37e5eda3958', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Model'.DS.'DummyTest.php';
        $this->assertOutputContains('tests/TestCase/Model/DummyTest.php');
        $this->assertTrue(file_exists($filename));
        $this->assertFileHash('34e52437fdf949137b91807ea8cae5c2', $filename);
        unlink($filename);

        $filename = TESTS.DS.'Fixture'.DS.'DummyFixture.php';
        $this->assertOutputContains('tests/Fixture/DummyFixture.php');
        $this->assertTrue(file_exists($filename));
        $this->assertFileHash('19a8d51358b8796b3e18d44385f69b98', $filename);
        unlink($filename);

        preg_match('/[0-9]{14}/', $this->output(), $match);
        $version = $match[0];
        $filename = APP.DS.'db'.DS.'migrate'.DS.$version.'CreateDummyTable.php';
        
        $this->assertOutputContains("db/migrate/{$version}CreateDummyTable.php");
        $this->assertTrue(file_exists($filename));
        $this->assertFileHash('f848e2ea56639696a82cbc080138a029', $filename);
        unlink($filename);
    }

    public function testGenerateMiddleware()
    {
        $this->exec('generate --force middleware Dummy');
        $this->assertExitSuccess();
        $filename = SRC.DS.'Middleware'.DS.'DummyMiddleware.php';
        $this->assertOutputContains('src/Middleware/DummyMiddleware.php');
        $this->assertTrue(file_exists($filename));
        $this->assertFileHash('6500ed5e5f09fbfe3d5849f86db2df92', $filename);
        unlink($filename);
    }

    public function testGenerateMigration()
    {
        $this->exec('generate --force migration Dummy');
        $this->assertExitSuccess();

        preg_match('/[0-9]{14}/', $this->output(), $match);
        $version = $match[0];

        $filename = APP.DS.'db'.DS.'migrate'.DS.$version.'Dummy.php';
        
        $this->assertOutputContains("db/migrate/{$version}Dummy.php");
        $this->assertTrue(file_exists($filename));
        $this->assertFileHash('692f0f096c758b18dd3197f0ce1c6c2f', $filename);
        unlink($filename);
    }



    
    public function testPlugin()
    {
        $this->exec('generate --force plugin Dummy');
        $this->assertExitSuccess();

        $filename = APP.DS.'plugins'.DS.'dummy'.DS.'src'.DS.'Controller'.DS.'DummyAppController.php';
        $this->assertTrue(file_exists($filename));
        $this->assertFileHash('fa169b36a04b4d9133c1eba5c9e364cd', $filename);

        $filename = APP.DS.'plugins'.DS.'dummy'.DS.'src'.DS.'Model'.DS.'DummyAppModel.php';
        $this->assertTrue(file_exists($filename));
        $this->assertFileHash('80c5e6b57f47789bc3e5f99c0b225a92', $filename);

        $filename = APP.DS.'plugins'.DS.'dummy'.DS.'config'.DS.'routes.php';
        $this->assertTrue(file_exists($filename));
        $this->assertFileHash('6f107423fcdde9f10e7b099f8149b3cf', $filename);

        $filename = APP.DS.'plugins'.DS.'dummy'.DS.'phpunit.xml';
        $this->assertTrue(file_exists($filename));
        $this->assertFileHash('8cb27d99afeb20945a7ad5e0babebb27', $filename);

        $this->rmdir(APP.DS.'plugins'.DS.'dummy');
    }

    /**
     * Recursively delete a directory
     *
     * @param string $directory
     * @return bool
     */
    protected function rmdir(string $directory)
    {
        $files = array_diff(scandir($directory), ['.', '..']);
        foreach ($files as $filename) {
            if (is_dir($directory . DS . $filename)) {
                $this->rmdir($directory . DS . $filename);
                continue;
            }
            unlink($directory . DS . $filename);
        }

        return rmdir($directory);
    }

    /*

        'plugin' => 'Generates a plugin skeleton',
        */

    protected function assertFileHash(string $hash, String $filename)
    {
        $this->assertEquals($hash, md5(file_get_contents($filename)));
    }
}
