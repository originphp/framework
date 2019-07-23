<?php
namespace Origin\Test\Command;

use Origin\Utility\Folder;
use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class LocaleGenerateCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    public function testRun()
    {
        Folder::delete(CONFIG . DS . 'locales', ['recursive' => true]); // reach path

        $this->exec('locale:generate --force');
        $this->assertExitSuccess();
        $output = $this->output();
        $this->assertRegExp('/Generated ([0-9]{3}) locale definitions/', $output); // Different systems generate different amounts
      
        // Remove files
        Folder::delete(CONFIG . DS . 'locales', ['recursive' => true]); // reach path
    }

    public function testQualityCheck()
    {
        $this->exec('locale:generate en_GB --force');
        $this->assertExitSuccess();
        $this->assertOutputContains('Generated 1 locale definitions');

        // $hash = md5(file_get_contents('/var/www/config/locales/en_GB.yml'));
        $this->assertEquals('00c71cc38eec600727fd82e06e59a730', md5(file_get_contents(CONFIG . DS . 'locales' . DS . 'en_GB.yml')));
        # Dont DELETE THIS. This is used by other tests
    }

    public function testGenerateSingleFile()
    {
        $path = CONFIG . DS . 'locales' . DS . 'locales.yml';

        $this->exec('locale:generate --single-file --force --expected en_GB en_US es_ES fr_FR');
        $this->assertExitSuccess();
        $this->assertOutputContains('Generated 4 locale definitions');
        $this->assertFileExists($path);
        unlink($path);
    }
}
