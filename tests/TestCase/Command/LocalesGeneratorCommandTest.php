<?php
namespace Origin\Test\Command;

use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class LocalesGeneratorCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    public function testRun(){
        $this->exec('locales:generator --force');
        $this->assertExitSuccess();
        $this->assertOutputContains('Generated 722 locale definitions');
    }

    public function testQualityCheck(){
       // $hash = md5(file_get_contents('/var/www/config/locales/en_GB.yml'));
        $this->assertEquals('00c71cc38eec600727fd82e06e59a730',md5(file_get_contents(CONFIG . DS . 'locales' . DS . 'en_GB.yml')));
        
        // Remove files
        $files = array_diff(scandir(CONFIG . DS . 'locales'), ['.', '..']);
        foreach($files as $file){
            unlink(CONFIG . DS . 'locales' . DS . $file);
        }
    }
  
}