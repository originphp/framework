<?php

namespace Origin\Test\Command;

use Origin\Utility\Folder;
use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class GenerateCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    public $fixtures = ['Origin.Bookmark','Origin.BookmarksTag','Origin.Tag','Origin.User'];

    public function testScaffoldUnkownModel()
    {
        $this->exec('generate --force --datasource=test scaffold Foo');
        $this->assertExitError();
        $this->assertErrorContains('Unkown model Foo');
    }
    public function testGenerateScaffold()
    {
        $this->exec('generate --force --datasource=test scaffold Bookmark');
        
        $this->assertExitSuccess();
        /**
         * Run the generator on the bookmarks app and test its all working as accepted before changing Hashes
         */

        $this->assertFileHash('9c9f3a586a45dac7a3edb8b0144877ed', SRC . DS . 'Model' . DS . 'Bookmark.php');
        unlink(SRC . DS . 'Model' . DS . 'Bookmark.php');

        $this->assertFileHash('e09190c9f65a07c2a6c739afb4fadab2', SRC . DS . 'Controller' . DS . 'BookmarksController.php');
        unlink(SRC . DS . 'Controller' . DS . 'BookmarksController.php');
     
        $this->assertFileHash('255346de5fe4d28c9fe66ee4ab1d200e', SRC . DS . 'View' . DS . 'Bookmarks' . DS . 'add.ctp');
        unlink(SRC . DS . 'View' . DS . 'Bookmarks' . DS . 'add.ctp');

        $this->assertFileHash('752079993cbd5909d81a358104154e0a', SRC . DS . 'View' . DS . 'Bookmarks' . DS . 'edit.ctp');
        unlink(SRC . DS . 'View' . DS . 'Bookmarks' . DS . 'edit.ctp');

        $this->assertFileHash('8bde3ab1bef406aeb53bca4b5d4f7606', SRC . DS . 'View' . DS . 'Bookmarks' . DS . 'index.ctp');
        unlink(SRC . DS . 'View' . DS . 'Bookmarks' . DS . 'index.ctp');

        $this->assertFileHash('86c6758a5198f828288a0bab4c71d36e', SRC . DS . 'View' . DS . 'Bookmarks' . DS . 'view.ctp');
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

    public function testNoName()
    {
        $this->exec('generate command');
        $this->assertExitError();
        $this->assertErrorContains('You must provide a name e.g. Single,DoubleWord');
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
        $this->assertFileExists($filename);
        $this->assertFileHash('aaf850b60f00e768c55ff6fc43f8a075', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Model'.DS.'Behavior'.DS.'DummyBehaviorTest.php';
        $this->assertOutputContains('TestCase/Model/Behavior/DummyBehaviorTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('9da6d12785a0dc07eae3f3c35f12f581', $filename);
        unlink($filename);
    }
    /**
     * depennds testBehavior
     */
    public function testInteractive()
    {
        $this->exec('generate', ['behavior','Dummy']);
        $this->assertExitSuccess();

        $filename = SRC.DS.'Model'.DS.'Behavior'.DS.'DummyBehavior.php';
        $this->assertOutputContains('src/Model/Behavior/DummyBehavior.php');

        $this->assertFileExists($filename);
        $this->assertFileHash('aaf850b60f00e768c55ff6fc43f8a075', $filename);
        unlink($filename);
    }

    public function testGenerateCommand()
    {
        $this->exec('generate --force command Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Command'.DS.'DummyCommand.php';
        $this->assertOutputContains('src/Command/DummyCommand.php');
    
        $this->assertFileHash('81b1f30a527eaa351d8be662601eb9a1', $filename);
        unlink($filename);
        
        $filename = TESTS.DS.'TestCase'.DS.'Command'.DS.'DummyCommandTest.php';
        $this->assertOutputContains('tests/TestCase/Command/DummyCommandTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('2c9b8b9981679b1046ae8e278be5f15a', $filename);
        unlink($filename);
    }

    public function testGenerateCommandPlugin()
    {
        $this->exec('generate --force command ContactManager.Duck');
        $this->assertExitSuccess();

        $filename = APP.DS.'plugins'.DS.'contact_manager'.DS.'src'.DS.'Command'.DS.'DuckCommand.php';
 
        $this->assertOutputContains('contact_manager/src/Command/DuckCommand.php');
        $this->assertFileHash('674ebdcdfbe430d61e31671acd0cdd69', $filename);
        unlink($filename);

        $filename = APP.DS.'plugins'.DS.'contact_manager'.DS.'tests'.DS.'TestCase'.DS.'Command'.DS.'DuckCommandTest.php';
     
        $this->assertOutputContains('contact_manager/tests/TestCase/Command/DuckCommandTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('a3e958f578c2464757f9ae9f99d01b86', $filename);
        unlink($filename);

        Folder::delete(APP.DS.'plugins'.DS.'contact_manager', ['recursive' => true]);
    }

    public function testGenerateComponent()
    {
        $this->exec('generate --force component Dummy');
        $this->assertExitSuccess();
        $filename = SRC.DS.'Controller'.DS.'Component'.DS.'DummyComponent.php';
        $this->assertOutputContains('src/Controller/Component/DummyComponent.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('bba33e474254126d2544f41abe7c857e', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Controller'.DS.'Component'.DS.'DummyComponentTest.php';
        $this->assertOutputContains('TestCase/Controller/Component/DummyComponentTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('a7809ae8481f61cc56c0dcae2777ed68', $filename);
        unlink($filename);
    }

    public function testGenerateController()
    {
        $this->exec('generate --force controller Dummies');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Controller'.DS.'DummiesController.php';
        $this->assertOutputContains('src/Controller/DummiesController.php');
        $this->assertFileExists($filename);
  
        $this->assertFileHash('3d78d5c80c21c893251b1ba3d882b84c', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Controller'.DS.'DummiesControllerTest.php';
        $this->assertOutputContains('tests/TestCase/Controller/DummiesControllerTest.php');
        $this->assertFileExists($filename);
        
        $this->assertFileHash('f19cad90cdca811884f483da36809b32', $filename);
        unlink($filename);
    }

    public function testGenerateControllerWithActions()
    {
        $this->exec('generate --force controller Dummies index get_user');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Controller'.DS.'DummiesController.php';
        $this->assertOutputContains('src/Controller/DummiesController.php');
        $this->assertFileExists($filename);
       
        $this->assertFileHash('dbdc832d0db7942106b9822e27491300', $filename);
        unlink($filename);

        $filename = SRC.DS.'View'.DS.'Dummies'.DS .'index.ctp';
        $this->assertOutputContains('src/View/Dummies/index.ctp');
        $this->assertFileExists($filename);
        $this->assertFileHash('af90a7a0bfcd3a6ff30c0aac82c94c16', $filename);
        unlink($filename);

        $filename = SRC.DS.'View'.DS.'Dummies'.DS .'get_user.ctp';
        $this->assertOutputContains('src/View/Dummies/get_user.ctp');
        $this->assertFileExists($filename);
        $this->assertFileHash('9263ed82c0e1859690365808dcd719b0', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Controller'.DS.'DummiesControllerTest.php';
        $this->assertOutputContains('tests/TestCase/Controller/DummiesControllerTest.php');
        $this->assertFileExists($filename);
      
        $this->assertFileHash('4fdca9e914d9ddb4c8582c527fccd2fa', $filename);
        unlink($filename);
    }

    public function testGenerateHelper()
    {
        $this->exec('generate --force helper Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'View'.DS.'Helper'.DS.'DummyHelper.php';
        $this->assertOutputContains('src/View/Helper/DummyHelper.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('27a6aa4b8914e58092914cd7f07d7bc5', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS .'View'.DS.'Helper'.DS.'DummyHelperTest.php';
       
        $this->assertOutputContains('TestCase/View/Helper/DummyHelperTest.php');
        $this->assertFileExists($filename);

        $this->assertFileHash('108242422afa5b9a7318a5e8d109a759', $filename);
        unlink($filename);
    }

    public function testGenerateMailer()
    {
        $this->exec('generate --force mailer Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Mailer'.DS.'DummyMailer.php';
        $this->assertOutputContains('src/Mailer/DummyMailer.php');
        $this->assertFileExists($filename);

        $this->assertFileHash('df49ae8527946c2e10178d3335766917', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS .'Mailer'.DS.'DummyMailerTest.php';
       
        $this->assertOutputContains('TestCase/Mailer/DummyMailerTest.php');
        $this->assertFileExists($filename);
        
        $this->assertFileHash('3c9d1b2bdf1533cc989f149e008324cc', $filename);
        unlink($filename);
    }

    public function testGenerateModel()
    {
        $this->exec('generate --force model Dummy name:string description:text');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Model'.DS.'Dummy.php';
        $this->assertOutputContains('src/Model/Dummy.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('bf5848a931354bddf853b37e5eda3958', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Model'.DS.'DummyTest.php';
        $this->assertOutputContains('tests/TestCase/Model/DummyTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('3142d48c1dac457de6bb71224f379233', $filename);
   
        unlink($filename);

        $filename = TESTS.DS.'Fixture'.DS.'DummyFixture.php';
        $this->assertOutputContains('tests/Fixture/DummyFixture.php');
        $this->assertFileExists($filename);
        
        $this->assertFileHash('94b89f1f898f2e1824bfebe1e0be055d', $filename);
        unlink($filename);

        preg_match('/[0-9]{14}/', $this->output(), $match);
        $version = $match[0];
        $filename = APP.DS.DATABASE_FOLDER.DS.'migrate'.DS.$version.'CreateDummyTable.php';
        
        $this->assertOutputContains("db/migrate/{$version}CreateDummyTable.php");
        $this->assertFileExists($filename);
        $this->assertFileHash('f848e2ea56639696a82cbc080138a029', $filename);
        unlink($filename);
    }

    public function testGenerateMiddleware()
    {
        $this->exec('generate --force middleware Dummy');
        $this->assertExitSuccess();
        $filename = SRC.DS.'Middleware'.DS.'DummyMiddleware.php';
        $this->assertOutputContains('src/Middleware/DummyMiddleware.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('79b5a3c6d032c8ea5ba2942313b0c439', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS .'Middleware'.DS.'DummyMiddlewareTest.php';
        $this->assertOutputContains('TestCase/Middleware/DummyMiddlewareTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('df9f62e0fc6436ffbdd0bd27be273952', $filename);
        unlink($filename);
    }

    public function testGenerateJob()
    {
        $this->exec('generate --force job Dummy');
        $this->assertExitSuccess();
        $filename = SRC.DS.'Job'.DS.'DummyJob.php';
        $this->assertOutputContains('src/Job/DummyJob.php');
        $this->assertFileExists($filename);
        
        $this->assertFileHash('c2b2cd39f0974d27358854bcdf2b448c', $filename);
        unlink($filename);
        
        $filename = TESTS.DS.'TestCase'.DS .'Job'.DS.'DummyJobTest.php';
        
        $this->assertOutputContains('TestCase/Job/DummyJobTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('27e6b98f491d555a056ce929b30df5d6', $filename);
        unlink($filename);
    }

    public function testGenerateService()
    {
        $this->exec('generate --force service Dummy');
        $this->assertExitSuccess();
        $filename = SRC.DS.'Service'.DS.'DummyService.php';
        $this->assertOutputContains('src/Service/DummyService.php');
        $this->assertFileExists($filename);
    
        $this->assertFileHash('338b324e5cad8b4ee516c2e22b7ee66e', $filename);
        unlink($filename);
        
        $filename = TESTS.DS.'TestCase'.DS .'Service'.DS.'DummyServiceTest.php';
        $this->assertOutputContains('TestCase/Service/DummyServiceTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('f646e791a1a6d9c38cf371a3ccaf22d9', $filename);
        unlink($filename);
    }

    public function testGenerateMigration()
    {
        $this->exec('generate --force migration Dummy');
        $this->assertExitSuccess();

        preg_match('/[0-9]{14}/', $this->output(), $match);
        $version = $match[0];

        $filename = APP.DS.DATABASE_FOLDER.DS.'migrate'.DS.$version.'Dummy.php';
        
        $this->assertOutputContains("db/migrate/{$version}Dummy.php");
        $this->assertFileExists($filename);
        $this->assertFileHash('692f0f096c758b18dd3197f0ce1c6c2f', $filename);
        unlink($filename);
    }

    public function testPlugin()
    {
        $this->exec('generate --force plugin Dummy');
        $this->assertExitSuccess();

        $filename = APP.DS.'plugins'.DS.'dummy'.DS.'src'.DS.'Controller'.DS.'DummyAppController.php';
        $this->assertFileExists($filename);
        $this->assertFileHash('fa169b36a04b4d9133c1eba5c9e364cd', $filename);

        $filename = APP.DS.'plugins'.DS.'dummy'.DS.'src'.DS.'Model'.DS.'DummyAppModel.php';
        $this->assertFileExists($filename);
        $this->assertFileHash('80c5e6b57f47789bc3e5f99c0b225a92', $filename);

        $filename = APP.DS.'plugins'.DS.'dummy'.DS.'config'.DS.'routes.php';
        $this->assertFileExists($filename);
        $this->assertFileHash('6f107423fcdde9f10e7b099f8149b3cf', $filename);

        $filename = APP.DS.'plugins'.DS.'dummy'.DS.'phpunit.xml';
        $this->assertFileExists($filename);
        $this->assertFileHash('8cb27d99afeb20945a7ad5e0babebb27', $filename);

        Folder::delete(APP.DS.'plugins'.DS.'dummy', ['recursive' => true]);
    }

    /*

        'plugin' => 'Generates a plugin skeleton',
        */

    protected function assertFileHash(string $hash, String $filename)
    {
        $this->assertEquals($hash, md5(file_get_contents($filename)));
    }
}
