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
        $this->assertFileHash('916dec6889141581581735a68e1ad74c', SRC . DS . 'Model' . DS . 'Bookmark.php');
        unlink(SRC . DS . 'Model' . DS . 'Bookmark.php');
       
        
        $this->assertFileHash('f689f0555aceb18461773a0c3505d002', SRC . DS . 'Controller' . DS . 'BookmarksController.php');
        unlink(SRC . DS . 'Controller' . DS . 'BookmarksController.php');
     
        $this->assertFileHash('6dc45f63dedc9fb40495bb1f4f38420c', SRC . DS . 'View' . DS . 'Bookmarks' . DS . 'add.ctp');
        unlink(SRC . DS . 'View' . DS . 'Bookmarks' . DS . 'add.ctp');

        $this->assertFileHash('ec4952f8520b12b37525bfba163c1bd3', SRC . DS . 'View' . DS . 'Bookmarks' . DS . 'edit.ctp');
        unlink(SRC . DS . 'View' . DS . 'Bookmarks' . DS . 'edit.ctp');

        $this->assertFileHash('af7fbc89c94ababac615937d6233eab1', SRC . DS . 'View' . DS . 'Bookmarks' . DS . 'index.ctp');
        unlink(SRC . DS . 'View' . DS . 'Bookmarks' . DS . 'index.ctp');

        $this->assertFileHash('991257cdd979d06a27392ddda40689b7', SRC . DS . 'View' . DS . 'Bookmarks' . DS . 'view.ctp');
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
     
        $this->assertFileHash('6febd1c79aa7046c1fa8e4f1b6753751', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Model'.DS.'Concern'.DS.'DummyConcernTest.php';
        $this->assertOutputContains('TestCase/Model/Concern/DummyConcernTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('227c1b3efbeea3caacf7ba6d464bdcf6', $filename);
        unlink($filename);
    }

    public function testGenerateConcernController()
    {
        $this->exec('generate --force concern_controller Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Controller'.DS.'Concern'.DS.'DummyConcern.php';
        $this->assertOutputContains('app/Controller/Concern/DummyConcern.php');
        $this->assertFileExists($filename);
       
        $this->assertFileHash('9d3b29b29e9d75e2bdc4fe0f49fc2a0e', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Controller'.DS.'Concern'.DS.'DummyConcernTest.php';
        $this->assertOutputContains('TestCase/Controller/Concern/DummyConcernTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('5cad140ec475d55b4c9ab364c02fe611', $filename);
        unlink($filename);
    }

    public function testGenerateRepository()
    {
        $this->exec('generate --force repository Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Model'.DS.'Repository'.DS.'DummyRepository.php';
        $this->assertOutputContains('app/Model/Repository/DummyRepository.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('e76f216a37c6c0f9c3ed664e9de3b162', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Model'.DS.'Repository'.DS.'DummyRepositoryTest.php';
        $this->assertOutputContains('TestCase/Model/Repository/DummyRepositoryTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('4cf5e8468fdef0efca44f7f939a09740', $filename);
        unlink($filename);
    }

    public function testGenerateBehavior()
    {
        $this->exec('generate --force behavior Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Model'.DS.'Behavior'.DS.'DummyBehavior.php';
        $this->assertOutputContains('app/Model/Behavior/DummyBehavior.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('df253510c99fd5a4177bb2ed76f9d000', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Model'.DS.'Behavior'.DS.'DummyBehaviorTest.php';
        $this->assertOutputContains('TestCase/Model/Behavior/DummyBehaviorTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('6b617ece2f598ce84b0784f8180dc1f0', $filename);
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
        $this->assertFileHash('df253510c99fd5a4177bb2ed76f9d000', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Model'.DS.'Behavior'.DS.'DummyBehaviorTest.php';
        unlink($filename);
    }

    public function testGenerateCommand()
    {
        $this->exec('generate --force command Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Command'.DS.'DummyCommand.php';
        $this->assertOutputContains('app/Command/DummyCommand.php');
    
        $this->assertFileHash('0e45a50915134529b5da403c8d7a88e4', $filename);
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
        $this->assertFileHash('0ab88ef86124e26a352d2987eb92dfd7', $filename);
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
        $this->assertFileHash('19338fca7709fce15290d3a6b1adda05', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Controller'.DS.'Component'.DS.'DummyComponentTest.php';
        $this->assertOutputContains('TestCase/Controller/Component/DummyComponentTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('736746e70c2545a7f5ec061e11c9ca9d', $filename);
        unlink($filename);
    }

    public function testGenerateController()
    {
        $this->exec('generate --force controller Dummies');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Controller'.DS.'DummiesController.php';
        $this->assertOutputContains('app/Controller/DummiesController.php');
        $this->assertFileExists($filename);
  
        $this->assertFileHash('c86c5d322f5eae1effc7a8a5972d7955', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Controller'.DS.'DummiesControllerTest.php';
        $this->assertOutputContains('tests/TestCase/Controller/DummiesControllerTest.php');
        $this->assertFileExists($filename);
        
        $this->assertFileHash('28ea0db607281c70f8dc5eee41b8b68e', $filename);
        unlink($filename);
    }

    public function testGenerateControllerWithActions()
    {
        $this->exec('generate --force controller Dummies index get_user');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Controller'.DS.'DummiesController.php';
        $this->assertOutputContains('app/Controller/DummiesController.php');
        $this->assertFileExists($filename);
       
        $this->assertFileHash('e8841f1131587e4d63682ec927396d01', $filename);
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
      
        $this->assertFileHash('d2f087de8d5d52ede1b866aefe3c3131', $filename);
        unlink($filename);
    }

    public function testGenerateHelper()
    {
        $this->exec('generate --force helper Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'View'.DS.'Helper'.DS.'DummyHelper.php';
        $this->assertOutputContains('app/View/Helper/DummyHelper.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('0dd045114d56d6b3d3b4d95b97258897', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS .'View'.DS.'Helper'.DS.'DummyHelperTest.php';
       
        $this->assertOutputContains('TestCase/View/Helper/DummyHelperTest.php');
        $this->assertFileExists($filename);

        $this->assertFileHash('6f9d8e72865b03926581addc98cddb41', $filename);
        unlink($filename);
    }

    public function testGenerateMailer()
    {
        $this->exec('generate --force mailer Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Mailer'.DS.'DummyMailer.php';
        $this->assertOutputContains('app/Mailer/DummyMailer.php');
        $this->assertFileExists($filename);
     
        $this->assertFileHash('5eaf99189ea3784eaf93e888a72fa8ee', $filename);
        unlink($filename);
    
        $filename = TESTS.DS.'TestCase'.DS .'Mailer'.DS.'DummyMailerTest.php';
       
        $this->assertOutputContains('TestCase/Mailer/DummyMailerTest.php');
        $this->assertFileExists($filename);
        
        $this->assertFileHash('2a5ec49ee5a166bd753f4af917811a45', $filename);
        unlink($filename);
    }

    public function testGenerateModel()
    {
        $this->exec('generate --force model Dummy name:string description:text');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Model'.DS.'Dummy.php';
        $this->assertOutputContains('app/Model/Dummy.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('a1e3b0f0ea829c309dacd37d15b7f101', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Model'.DS.'DummyTest.php';
        $this->assertOutputContains('tests/TestCase/Model/DummyTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('8f821b0d79f913ccf1abe7854eaa2264', $filename);
   
        unlink($filename);

        $filename = TESTS.DS.'Fixture'.DS.'DummyFixture.php';
        $this->assertOutputContains('tests/Fixture/DummyFixture.php');
        $this->assertFileExists($filename);
        
        $this->assertFileHash('94b89f1f898f2e1824bfebe1e0be055d', $filename);
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
        $filename = SRC.DS.'Middleware'.DS.'DummyMiddleware.php';
        $this->assertOutputContains('app/Middleware/DummyMiddleware.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('79b5a3c6d032c8ea5ba2942313b0c439', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS .'Middleware'.DS.'DummyMiddlewareTest.php';
        $this->assertOutputContains('TestCase/Middleware/DummyMiddlewareTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('0c1d75dec331d16f739dc45a08320ca6', $filename);
        unlink($filename);
    }

    public function testGenerateJob()
    {
        $this->exec('generate --force job Dummy');
        $this->assertExitSuccess();
        $filename = SRC.DS.'Job'.DS.'DummyJob.php';
        $this->assertOutputContains('app/Job/DummyJob.php');
        $this->assertFileExists($filename);
       
        
        $this->assertFileHash('7efda7a73beb245da8da012eb5ce7215', $filename);
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
    
        $this->assertFileHash('015514584db8b0f72cbcc743df38f474', $filename);
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

        $filename = APP.DS.'plugins'.DS.'dummy'.DS.'src'.DS.'Controller'.DS.'DummyApplicationController.php';
        $this->assertFileExists($filename);
        $this->assertFileHash('4102428b27da8aefe73016ef792ebda7', $filename);

        $filename = APP.DS.'plugins'.DS.'dummy'.DS.'src'.DS.'Model'.DS.'DummyApplicationModel.php';
        $this->assertFileExists($filename);
        $this->assertFileHash('fda67d202cc1cd6ea520661fc3f0740c', $filename);

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
