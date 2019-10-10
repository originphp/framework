<?php

namespace Origin\Test\Console\Command;

use Origin\Utility\Folder;
use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class GenerateCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    protected $fixtures = ['Origin.Bookmark','Origin.BookmarksTag','Origin.Tag','Origin.User'];

    public function testGenerateException()
    {
        $this->exec('generate --force exception Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Exception'.DS.'DummyException.php';
        $this->assertOutputContains('app/Exception/DummyException.php');
        $this->assertFileExists($filename);
     
        $this->assertFileHash('1a3e8fe379af40f6d4c70401a772bb2c', $filename);
        unlink($filename);
    }

    public function testGenerateQuery()
    {
        $this->exec('generate --force query Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Model'.DS.'Query'.DS.'DummyQuery.php';
        $this->assertOutputContains('app/Model/Query/DummyQuery.php');
        $this->assertFileExists($filename);
     
        $this->assertFileHash('79f0ce6885050b2a3895c5e26e116186', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Model'.DS.'Query'.DS.'DummyQueryTest.php';
        $this->assertOutputContains('TestCase/Model/Query/DummyQueryTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('433184ce6814ebe56919e86794e3be2c', $filename);
        unlink($filename);
    }

    public function testScaffoldUnkownModel()
    {
        $this->exec('generate --force --connection=test scaffold Foo');
        $this->assertExitError();
        $this->assertErrorContains('Unkown model Foo');
    }
    public function testGenerateScaffold()
    {
        $this->exec('generate --force --connection=test scaffold Bookmark');
        
        $this->assertExitSuccess();
        /**
         * Run the generator on the bookmarks app and test its all working as accepted before changing Hashes
         */
        $this->assertFileHash('940945582b2e06f804ce18c64e12dbfa', SRC . DS . 'Model' . DS . 'Bookmark.php');
        unlink(SRC . DS . 'Model' . DS . 'Bookmark.php');
       
        $this->assertFileHash('ae4389221897c48b12d35b3755969059', SRC . DS .'Http'. DS . 'Controller' . DS . 'BookmarksController.php');
        unlink(SRC . DS .'Http'. DS .  'Controller' . DS . 'BookmarksController.php');
     
        $this->assertFileHash('9298725c00be555fb7b8751484f41780', SRC . DS . 'Http'. DS .'View' . DS . 'Bookmarks' . DS . 'add.ctp');
        unlink(SRC . DS . 'Http'. DS . 'View' . DS . 'Bookmarks' . DS . 'add.ctp');

        $this->assertFileHash('11ed3ae60350bfc07c170aae750e02d1', SRC . DS . 'Http'. DS .'View' . DS . 'Bookmarks' . DS . 'edit.ctp');
        unlink(SRC . DS . 'Http'. DS . 'View' . DS . 'Bookmarks' . DS . 'edit.ctp');

        $this->assertFileHash('c8d3d6cd1474cee688f8173d1a640b08', SRC . DS . 'Http'. DS .'View' . DS . 'Bookmarks' . DS . 'index.ctp');
        unlink(SRC . DS . 'Http'. DS . 'View' . DS . 'Bookmarks' . DS . 'index.ctp');

        $this->assertFileHash('193fa37f0d96400e39d025b6a0f92a2d', SRC . DS . 'Http'. DS .'View' . DS . 'Bookmarks' . DS . 'view.ctp');
        unlink(SRC . DS . 'Http'. DS . 'View' . DS . 'Bookmarks' . DS . 'view.ctp');
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

        $filename = SRC.DS.'Model'.DS.'Concern'.DS.'Dummy.php';
        $this->assertOutputContains('app/Model/Concern/Dummy.php');
        $this->assertFileExists($filename);
     
        $this->assertFileHash('c08bbe4ee37ea3434e44ed90dee7d274', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Model'.DS.'Concern'.DS.'DummyTest.php';
        $this->assertOutputContains('TestCase/Model/Concern/DummyTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('6101038c59d5bddda633033d000e8d54', $filename);
        unlink($filename);
    }

    public function testGenerateConcernController()
    {
        $this->exec('generate --force concern_controller Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Http'.DS. 'Controller'.DS.'Concern'.DS.'Dummy.php';
        $this->assertOutputContains('app/Http/Controller/Concern/Dummy.php');
        $this->assertFileExists($filename);
       
        $this->assertFileHash('3bae903c64bf5f9529c38d3a1e48e703', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Http'.DS.'Controller'.DS.'Concern'.DS.'DummyTest.php';
        $this->assertOutputContains('TestCase/Http/Controller/Concern/DummyTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('5c7642139d28c8b50284b72c27cc5c96', $filename);
        unlink($filename);
    }

    public function testGenerateRepository()
    {
        $this->exec('generate --force repository Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Model'.DS.'Repository'.DS.'DummyRepository.php';
        $this->assertOutputContains('app/Model/Repository/DummyRepository.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('6de4e3286718202163357be3f120b041', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Model'.DS.'Repository'.DS.'DummyRepositoryTest.php';
        $this->assertOutputContains('TestCase/Model/Repository/DummyRepositoryTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('3f6a31b30cdea43b12b0167d06453dda', $filename);
        unlink($filename);
    }

    public function testInteractive()
    {
        // this can be annoying when changes are maded
        @unlink(SRC.DS.'Model'.DS.'Concern'.DS.'Fooable.php');
        @unlink(TESTS.DS.'TestCase'.DS.'Model'.DS.'Concern'.DS.'FooableTest.php');

        $this->exec('generate', ['concern_model','Fooable']);
        $this->assertExitSuccess();

        $filename = SRC.DS.'Model'.DS.'Concern'.DS.'Fooable.php';
        $this->assertOutputContains('app/Model/Concern/Fooable.php');

        $this->assertFileExists($filename);
        $this->assertFileHash('9172a9ddc39f69243191f44462b0aeac', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Model'.DS.'Concern'.DS.'FooableTest.php';
        $this->assertFileExists($filename);
        $this->assertFileHash('b1fdd4d2db77721b6366c1b643a1e2dd', $filename);
        unlink($filename);
    }

    public function testGenerateCommand()
    {
        $this->exec('generate --force command Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Console'.DS.'Command'.DS.'DummyCommand.php';
        $this->assertOutputContains('app/Console/Command/DummyCommand.php');
    
        $this->assertFileHash('74fb0bd6ef8504278b6066e3d3d4144e', $filename);
        unlink($filename);
        
        $filename = TESTS.DS.'TestCase'.DS.'Console'.DS.'Command'.DS.'DummyCommandTest.php';
        $this->assertOutputContains('tests/TestCase/Console/Command/DummyCommandTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('4f55ac867d5e6be53caee7592d21da47', $filename);
        unlink($filename);
    }

    public function testGenerateCommandPlugin()
    {
        $this->exec('generate --force command ContactManager.Duck');
        $this->assertExitSuccess();

        $filename = APP.DS.'plugins'.DS.'contact_manager'.DS.'src'.DS.'Console'.DS.'Command'.DS.'DuckCommand.php';
 
        $this->assertOutputContains('contact_manager/src/Console/Command/DuckCommand.php');
        $this->assertFileHash('3645eded9e53b8b4cc2561ee4c65741c', $filename);
        unlink($filename);

        $filename = APP.DS.'plugins'.DS.'contact_manager'.DS.'tests'.DS.'TestCase'.DS.'Console'.DS.'Command'.DS.'DuckCommandTest.php';
     
        $this->assertOutputContains('contact_manager/tests/TestCase/Console/Command/DuckCommandTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('3df2fbe6dad0388ccc8f61afba77cb2c', $filename);
        unlink($filename);

        Folder::delete(APP.DS.'plugins'.DS.'contact_manager', ['recursive' => true]);
    }

    public function testGenerateComponent()
    {
        $this->exec('generate --force component Dummy');
        $this->assertExitSuccess();
        $filename = SRC.DS.'Http'.DS.'Controller'.DS.'Component'.DS.'DummyComponent.php';
        $this->assertOutputContains('app/Http/Controller/Component/DummyComponent.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('2b538c44783096845bf649525056b7eb', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Http'.DS.'Controller'.DS.'Component'.DS.'DummyComponentTest.php';
        $this->assertOutputContains('TestCase/Http/Controller/Component/DummyComponentTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('056304acf5ba255fe49e566463df7b70', $filename);
        unlink($filename);
    }

    public function testGenerateController()
    {
        $this->exec('generate --force controller Dummies');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Http'.DS.'Controller'.DS.'DummiesController.php';
        $this->assertOutputContains('app/Http/Controller/DummiesController.php');
        $this->assertFileExists($filename);
  
        $this->assertFileHash('2f94aaf0f830ef7363dbd0be90305d14', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Http'.DS.'Controller'.DS.'DummiesControllerTest.php';
        $this->assertOutputContains('tests/TestCase/Http/Controller/DummiesControllerTest.php');
        $this->assertFileExists($filename);
        
        $this->assertFileHash('c35dd943bdba74d8ccc9e24794652767', $filename);
        unlink($filename);
    }

    public function testGenerateControllerWithActions()
    {
        $this->exec('generate --force controller Dummies index get_user');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Http'.DS.'Controller'.DS.'DummiesController.php';
        $this->assertOutputContains('app/Http/Controller/DummiesController.php');
        $this->assertFileExists($filename);
       
        $this->assertFileHash('e483c175c4c08ef52c62851b45cb92d8', $filename);
        unlink($filename);

        $filename = SRC.DS.'Http'.DS.'View'.DS.'Dummies'.DS .'index.ctp';
        $this->assertOutputContains('app/Http/View/Dummies/index.ctp');
        $this->assertFileExists($filename);
        $this->assertFileHash('af90a7a0bfcd3a6ff30c0aac82c94c16', $filename);
        unlink($filename);

        $filename = SRC.DS.'Http'.DS.'View'.DS.'Dummies'.DS .'get_user.ctp';
        $this->assertOutputContains('app/Http/View/Dummies/get_user.ctp');
        $this->assertFileExists($filename);
        $this->assertFileHash('9263ed82c0e1859690365808dcd719b0', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Http'.DS.'Controller'.DS.'DummiesControllerTest.php';
        $this->assertOutputContains('tests/TestCase/Http/Controller/DummiesControllerTest.php');
        $this->assertFileExists($filename);
      
        $this->assertFileHash('f8fc4a44d914822618f4f059ff8ef1a5', $filename);
        unlink($filename);
    }

    public function testGenerateHelper()
    {
        $this->exec('generate --force helper Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Http'.DS.'View'.DS.'Helper'.DS.'DummyHelper.php';
        $this->assertOutputContains('app/Http/View/Helper/DummyHelper.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('dbbcb9ab70dd713aee78118d411f027e', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS .'Http'.DS.'View'.DS.'Helper'.DS.'DummyHelperTest.php';
       
        $this->assertOutputContains('TestCase/Http/View/Helper/DummyHelperTest.php');
        $this->assertFileExists($filename);

        $this->assertFileHash('f6ace37b347625d33a38c0feb1202e20', $filename);
        unlink($filename);
    }

    public function testGenerateMailer()
    {
        $this->exec('generate --force mailer Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Mailer'.DS.'DummyMailer.php';
        $this->assertOutputContains('app/Mailer/DummyMailer.php');
        $this->assertFileExists($filename);
     
        $this->assertFileHash('1e83ba4831e6db9ac93211dd9c1ebc2a', $filename);
        unlink($filename);
    
        $filename = TESTS.DS.'TestCase'.DS .'Mailer'.DS.'DummyMailerTest.php';
       
        $this->assertOutputContains('TestCase/Mailer/DummyMailerTest.php');
        $this->assertFileExists($filename);
        
        $this->assertFileHash('503570d23057abf7ae96e2d6201c2c70', $filename);
        unlink($filename);

        $filename = SRC.DS.'Mailer'.DS.'Template'.DS .'dummy.html.ctp';
        $this->assertOutputContains('app/Mailer/Template/dummy.html.ctp');
        $this->assertFileHash('dcd7e3b40d5e4d840e8e2ba0a9721a81', $filename);
        unlink($filename);

        $filename = SRC.DS.'Mailer'.DS.'Template'.DS .'dummy.text.ctp';
        $this->assertOutputContains('app/Mailer/Template/dummy.text.ctp');
        $this->assertFileHash('b336631ad91ce8c22975f1bea7c0da4e', $filename);
        unlink($filename);
    }

    public function testGenerateModel()
    {
        $this->exec('generate --force model Dummy name:string description:text');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Model'.DS.'Dummy.php';
        $this->assertOutputContains('app/Model/Dummy.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('f85adcc93cceab2310ba2a4a350cf433', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Model'.DS.'DummyTest.php';
        $this->assertOutputContains('tests/TestCase/Model/DummyTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('d95803ef95a4a5e3e9a15213ff108115', $filename);
   
        unlink($filename);

        $filename = TESTS.DS.'Fixture'.DS.'DummyFixture.php';
        $this->assertOutputContains('tests/Fixture/DummyFixture.php');
        $this->assertFileExists($filename);
        
        $this->assertFileHash('d3cd54bd8aa2d6b22560b51b16d04e56', $filename);
        unlink($filename);

        preg_match('/[0-9]{14}/', $this->output(), $match);
        $version = $match[0];
        $filename = APP.DS.'database'.DS.'migrations'.DS.$version.'CreateDummyTable.php';
        
        $this->assertOutputContains("database/migrations/{$version}CreateDummyTable.php");
        $this->assertFileExists($filename);
        $this->assertFileHash('c1ac45eb671fb571e313b97e4acf93d1', $filename);
        unlink($filename);
    }

    public function testGenerateMiddleware()
    {
        $this->exec('generate --force middleware Dummy');
        $this->assertExitSuccess();
        $filename = SRC.DS.'Http'.DS.'Middleware'.DS.'DummyMiddleware.php';
        $this->assertOutputContains('app/Http/Middleware/DummyMiddleware.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('7e322d4bf23c2ec92858b3e35a2e7be5', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS .'Http'.DS . 'Middleware'.DS.'DummyMiddlewareTest.php';
        $this->assertOutputContains('TestCase/Http/Middleware/DummyMiddlewareTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('20e7bbc3d50a8e2f296bedd366e8a4cb', $filename);
        unlink($filename);
    }

    public function testGenerateJob()
    {
        $this->exec('generate --force job Dummy');
        $this->assertExitSuccess();
        $filename = SRC.DS.'Job'.DS.'DummyJob.php';
        $this->assertOutputContains('app/Job/DummyJob.php');
        $this->assertFileExists($filename);
       
        $this->assertFileHash('8bf932c3856401a04c3b481b6186cf7d', $filename);
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
    
        $this->assertFileHash('f8b6b793ba5d876c87253ab481fa128a', $filename);
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

        $filename = APP.DS.'database'.DS.'migrations'.DS.$version.'Dummy.php';
        
        $this->assertOutputContains("database/migrations/{$version}Dummy.php");
        $this->assertFileExists($filename);
        $this->assertFileHash('7ae46d1b60868d54f09ccce43ca35068', $filename);
        unlink($filename);
    }

    public function testPlugin()
    {
        $this->exec('generate --force plugin Dummy');
        $this->assertExitSuccess();

        $filename = APP.DS.'plugins'.DS.'dummy'.DS.'src'.DS.'Http'.DS.'Controller'.DS.'DummyApplicationController.php';
        $this->assertFileExists($filename);
        $this->assertFileHash('b056004d9383d8b6cc982dbd17a1cb60', $filename);

        $filename = APP.DS.'plugins'.DS.'dummy'.DS.'src'.DS.'Model'.DS.'DummyApplicationModel.php';
        $this->assertFileExists($filename);
        $this->assertFileHash('66d57a656bd1290df6358294566a0d3d', $filename);

        $filename = APP.DS.'plugins'.DS.'dummy'.DS.'config'.DS.'routes.php';
        $this->assertFileExists($filename);
        $this->assertFileHash('6f107423fcdde9f10e7b099f8149b3cf', $filename);

        $filename = APP.DS.'plugins'.DS.'dummy'.DS.'phpunit.xml';
        $this->assertFileExists($filename);
        $this->assertFileHash('8cb27d99afeb20945a7ad5e0babebb27', $filename);

        $filename = APP.DS.'plugins'.DS.'dummy'.DS.'composer.json';
        $this->assertFileExists($filename);
        $this->assertFileHash('3aac15995b02c9505537ccdb85130f31', $filename);

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
