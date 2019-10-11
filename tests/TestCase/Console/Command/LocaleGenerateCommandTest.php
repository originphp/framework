<?php
namespace Origin\Test\Console\Command;

use Origin\TestSuite\ConsoleIntegrationTestTrait;

class LocaleGenerateCommandTest extends \PHPUnit\Framework\TestCase
{
    use ConsoleIntegrationTestTrait;

    public function testRun()
    {
        $this->recursiveDelete(CONFIG . DS . 'locales'); // reach path
        
        $this->exec('locale:generate --force');
        $this->assertExitSuccess();
        $output = $this->output();
        $this->assertRegExp('/Generated ([0-9]{3}) locale definitions/', $output); // Different systems generate different amounts
      
        // Remove files
        $this->recursiveDelete(CONFIG . DS . 'locales'); // reach path
    }

    public function testQualityCheck()
    {
        $this->exec('locale:generate en_GB --force');
        $this->assertExitSuccess();
        $this->assertOutputContains('Generated 1 locale definitions');

        // $hash = md5(file_get_contents('/var/www/config/locales/en_GB.php'));
        $this->assertEquals('00bfc52abca78a0c72d0156af190bac6', md5(file_get_contents(CONFIG . DS . 'locales' . DS . 'en_GB.php')));
        # Dont DELETE THIS. This is used by other tests
    }

    public function testGenerateSingleFile()
    {
        $path = CONFIG . DS . 'locales' . DS . 'locales.php';

        $this->exec('locale:generate --single-file --force --expected en_GB en_US es_ES fr_FR');
        $this->assertExitSuccess();
        $this->assertOutputContains('Generated 4 locale definitions');
        $this->assertFileExists($path);
        unlink($path);
    }

    /**
     * This is slow implementation
     *
     * @param string $directory
     * @return void
     */
    private function recursiveDelete(string $directory)
    {
        foreach (glob($directory."/*.*") as $filename) {
            if (is_file($filename)) {
                unlink($filename);
            }
        }
        rmdir($directory);
    }
}
