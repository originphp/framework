<?php
namespace Origin\Test\Command;

use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class LocaleGeneratorCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

  
    public function testRun()
    {
        $backup = file_get_contents(CONFIG . DS . 'locales' . DS . 'en_GB.yml');
        $this->exec('locale:generate --force');
        $this->assertExitSuccess();
        $this->assertRegexp('/Generated [0-9]{3} locale definitions/', $this->output());
      
        // Remove files
        $files = array_diff(scandir(CONFIG . DS . 'locales'), ['.', '..']);
        foreach ($files as $file) {
            unlink(CONFIG . DS . 'locales' . DS . $file);
        }
        file_put_contents(CONFIG . DS . 'locales' . DS . 'en_GB.yml', $backup);
    }

    public function testQualityCheck()
    {
        $this->exec('locale:generate en_GB --force');
        $this->assertExitSuccess();
        $this->assertOutputContains('Generated 1 locale definitions');

        // $hash = md5(file_get_contents('/var/www/config/locales/en_GB.yml'));
        $this->assertEquals('00c71cc38eec600727fd82e06e59a730', md5(file_get_contents(CONFIG . DS . 'locales' . DS . 'en_GB.yml')));
        # Dont DELETE THIS. This is required by
    }
}
