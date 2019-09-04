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
    public function testGenerateConcernModel()
    {
        $this->exec('generate --force concern_model Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Model'.DS.'Concern'.DS.'DummyConcern.php';
        $this->assertOutputContains('app/Model/Concern/DummyConcern.php');
        $this->assertFileExists($filename);
     
        $this->assertFileHash('06c6def373bf9fd83dcf4c4f80b7a930', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Model'.DS.'Concern'.DS.'DummyConcernTest.php';
        $this->assertOutputContains('TestCase/Model/Concern/DummyConcernTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('ff86c972777a202678e373ad3c1602f8', $filename);
        unlink($filename);
    }

    public function testGenerateConcernController()
    {
        $this->exec('generate --force concern_controller Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Controller'.DS.'Concern'.DS.'DummyConcern.php';
        $this->assertOutputContains('app/Controller/Concern/DummyConcern.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('c7ba3a2ad7dbf1dae689b62e4ebb4b23', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Controller'.DS.'Concern'.DS.'DummyConcernTest.php';
        $this->assertOutputContains('TestCase/Controller/Concern/DummyConcernTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('ef25ee2ffa74c40c1d0e517d3a1b900f', $filename);
        unlink($filename);
    }

    public function testGenerateRepository()
    {
        $this->exec('generate --force repository Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Model'.DS.'Repository'.DS.'DummyRepository.php';
        $this->assertOutputContains('app/Model/Repository/DummyRepository.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('3b81f973134b36822a65822a921ef1c2', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Model'.DS.'Repository'.DS.'DummyRepositoryTest.php';
        $this->assertOutputContains('TestCase/Model/Repository/DummyRepositoryTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('9c4a611beae83bd4d9915afdcb22a770', $filename);
        unlink($filename);
    }

    public function testGenerateBehavior()
    {
        $this->exec('generate --force behavior Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Model'.DS.'Behavior'.DS.'DummyBehavior.php';
        $this->assertOutputContains('app/Model/Behavior/DummyBehavior.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('713aa015d77db1653dba59b20fbe2126', $filename);
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
        $this->assertOutputContains('app/Model/Behavior/DummyBehavior.php');

        $this->assertFileExists($filename);
        $this->assertFileHash('713aa015d77db1653dba59b20fbe2126', $filename);
        unlink($filename);
    }

    public function testGenerateCommand()
    {
        $this->exec('generate --force command Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Command'.DS.'DummyCommand.php';
        $this->assertOutputContains('app/Command/DummyCommand.php');
    
        $this->assertFileHash('81b1f30a527eaa351d8be662601eb9a1', $filename);
        unlink($filename);
        
        $filename = TESTS.DS.'TestCase'.DS.'Command'.DS.'DummyCommandTest.php';
        $this->assertOutputContains('tests/TestCase/Command/DummyCommandTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('94c260173371aa32465f6cc684702c5a', $filename);
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
        $this->assertFileHash('28a239eda51d9f7b2d14240aa65a8c84', $filename);
        unlink($filename);

        Folder::delete(APP.DS.'plugins'.DS.'contact_manager', ['recursive' => true]);
    }

    public function testGenerateComponent()
    {
        $this->exec('generate --force component Dummy');
        $this->assertExitSuccess();
        $filename = SRC.DS.'Controller'.DS.'Component'.DS.'DummyComponent.php';
        $this->assertOutputContains('app/Controller/Component/DummyComponent.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('bba33e474254126d2544f41abe7c857e', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Controller'.DS.'Component'.DS.'DummyComponentTest.php';
        $this->assertOutputContains('TestCase/Controller/Component/DummyComponentTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('bbe7e46c7cf20a8c786dc5bcaee4881d', $filename);
        unlink($filename);
    }

    public function testGenerateController()
    {
        $this->exec('generate --force controller Dummies');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Controller'.DS.'DummiesController.php';
        $this->assertOutputContains('app/Controller/DummiesController.php');
        $this->assertFileExists($filename);
  
        $this->assertFileHash('3d78d5c80c21c893251b1ba3d882b84c', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Controller'.DS.'DummiesControllerTest.php';
        $this->assertOutputContains('tests/TestCase/Controller/DummiesControllerTest.php');
        $this->assertFileExists($filename);
        
        $this->assertFileHash('4a53a22ab444e18eb639c452aa9cdf27', $filename);
        unlink($filename);
    }

    public function testGenerateControllerWithActions()
    {
        $this->exec('generate --force controller Dummies index get_user');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Controller'.DS.'DummiesController.php';
        $this->assertOutputContains('app/Controller/DummiesController.php');
        $this->assertFileExists($filename);
       
        $this->assertFileHash('dbdc832d0db7942106b9822e27491300', $filename);
        unlink($filename);

        $filename = SRC.DS.'View'.DS.'Dummies'.DS .'index.ctp';
        $this->assertOutputContains('app/View/Dummies/index.ctp');
        $this->assertFileExists($filename);
        $this->assertFileHash('af90a7a0bfcd3a6ff30c0aac82c94c16', $filename);
        unlink($filename);

        $filename = SRC.DS.'View'.DS.'Dummies'.DS .'get_user.ctp';
        $this->assertOutputContains('app/View/Dummies/get_user.ctp');
        $this->assertFileExists($filename);
        $this->assertFileHash('9263ed82c0e1859690365808dcd719b0', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Controller'.DS.'DummiesControllerTest.php';
        $this->assertOutputContains('tests/TestCase/Controller/DummiesControllerTest.php');
        $this->assertFileExists($filename);
      
        $this->assertFileHash('e682c1dfe031346891dd656bb9a1bb7b', $filename);
        unlink($filename);
    }

    public function testGenerateHelper()
    {
        $this->exec('generate --force helper Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'View'.DS.'Helper'.DS.'DummyHelper.php';
        $this->assertOutputContains('app/View/Helper/DummyHelper.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('27a6aa4b8914e58092914cd7f07d7bc5', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS .'View'.DS.'Helper'.DS.'DummyHelperTest.php';
       
        $this->assertOutputContains('TestCase/View/Helper/DummyHelperTest.php');
        $this->assertFileExists($filename);

        $this->assertFileHash('94ed6e8f82210e5096f19e826c1b9c94', $filename);
        unlink($filename);
    }

    public function testGenerateMailer()
    {
        $this->exec('generate --force mailer Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Mailer'.DS.'DummyMailer.php';
        $this->assertOutputContains('app/Mailer/DummyMailer.php');
        $this->assertFileExists($filename);

        $this->assertFileHash('df49ae8527946c2e10178d3335766917', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS .'Mailer'.DS.'DummyMailerTest.php';
       
        $this->assertOutputContains('TestCase/Mailer/DummyMailerTest.php');
        $this->assertFileExists($filename);
        
        $this->assertFileHash('15c9876f12718617542f2b787ce76eba', $filename);
        unlink($filename);
    }

    public function testGenerateModel()
    {
        $this->exec('generate --force model Dummy name:string description:text');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Model'.DS.'Dummy.php';
        $this->assertOutputContains('app/Model/Dummy.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('2c84b0ddac64043deada9b4ac29a2d7e', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Model'.DS.'DummyTest.php';
        $this->assertOutputContains('tests/TestCase/Model/DummyTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('bd75480f20346c53d52f7d69b8a38caf', $filename);
   
        unlink($filename);

        $filename = TESTS.DS.'Fixture'.DS.'DummyFixture.php';
        $this->assertOutputContains('tests/Fixture/DummyFixture.php');
        $this->assertFileExists($filename);
        
        $this->assertFileHash('94b89f1f898f2e1824bfebe1e0be055d', $filename);
        unlink($filename);

        preg_match('/[0-9]{14}/', $this->output(), $match);
        $version = $match[0];
        $filename = APP.DS.DATABASE_FOLDER.DS.MIGRATIONS_FOLDER.DS.$version.'CreateDummyTable.php';
        
        $this->assertOutputContains("database/migrations/{$version}CreateDummyTable.php");
        $this->assertFileExists($filename);
        $this->assertFileHash('f848e2ea56639696a82cbc080138a029', $filename);
        unlink($filename);
    }

    public function testGenerateMiddleware()
    {
        $this->exec('generate --force middleware Dummy');
        $this->assertExitSuccess();
        $filename = SRC.DS.'Middleware'.DS.'DummyMiddleware.php';
        $this->assertOutputContains('app/Middleware/DummyMiddleware.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('79b5a3c6d032c8ea5ba2942313b0c439', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS .'Middleware'.DS.'DummyMiddlewareTest.php';
        $this->assertOutputContains('TestCase/Middleware/DummyMiddlewareTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('ae8338ddbb90131d2b8576f9a2d46f13', $filename);
        unlink($filename);
    }

    public function testGenerateJob()
    {
        $this->exec('generate --force job Dummy');
        $this->assertExitSuccess();
        $filename = SRC.DS.'Job'.DS.'DummyJob.php';
        $this->assertOutputContains('app/Job/DummyJob.php');
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
        $this->assertOutputContains('app/Service/DummyService.php');
        $this->assertFileExists($filename);
    
        $this->assertFileHash('338b324e5cad8b4ee516c2e22b7ee66e', $filename);
        unlink($filename);
        
        $filename = TESTS.DS.'TestCase'.DS .'Service'.DS.'DummyServiceTest.php';
        $this->assertOutputContains('TestCase/Service/DummyServiceTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('f646e791a1a6d9c38cf371a3ccaf22d9', $filename);
        unlink($filename);
    }

    public function testGenerateListener()
    {
        $this->exec('generate --force listener Dummy');
        $this->assertExitSuccess();
        $filename = SRC.DS.'Listener'.DS.'DummyListener.php';
        $this->assertOutputContains('app/Listener/DummyListener.php');
        $this->assertFileExists($filename);
    
        $this->assertFileHash('1bd53a0cdd0f3e549ba321967d9587b4', $filename);
        unlink($filename);
        
        $filename = TESTS.DS.'TestCase'.DS .'Listener'.DS.'DummyListenerTest.php';
        $this->assertOutputContains('TestCase/Listener/DummyListenerTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('9fe80488ae506a109468569f76598a76', $filename);
        unlink($filename);
    }

    public function testGenerateMigration()
    {
        $this->exec('generate --force migration Dummy');
        $this->assertExitSuccess();

        preg_match('/[0-9]{14}/', $this->output(), $match);
        $version = $match[0];

        $filename = APP.DS.DATABASE_FOLDER.DS.MIGRATIONS_FOLDER.DS.$version.'Dummy.php';
        
        $this->assertOutputContains("database/migrations/{$version}Dummy.php");
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
