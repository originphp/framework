<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Core;

use Origin\Core\Dot;
use ReflectionClass;
use Origin\Core\Preloader;
use Origin\Core\Autoloader;

class PreloaderTest extends \PHPUnit\Framework\TestCase
{
    private function removeBasePath(array $result, string $path = APP)
    {
        array_walk($result['loaded'], function (&$value, &$key) use ($path) {
            $value = str_replace($path . '/', '', $value);
        });

        return $result;
    }

    public function testAddDirectory()
    {
        $preloader = new Preloader();
        $preloader->addDirectory(APP);

        $result = $this->removeBasePath($preloader->load());
        // check a couple so later we know ignore works
        $this->assertContains('Service/ApplicationService.php', $result['loaded']);
        $this->assertContains('Console/Command/SaySomethingCommand.php', $result['loaded']);
        $this->assertContains('Model/ApplicationModel.php', $result['loaded']);

        // check that .preloadignore was loaded and respected
        $this->assertNotContains('Console/Command/CacheResetCommand.php', $result['loaded']);
    }

    public function testAddDirectories()
    {
        $preloader = new Preloader();
        $preloader->addDirectories([APP .'/Console', APP .'/Http']);
        $result = $this->removeBasePath($preloader->load());
        $this->assertContains('Console/Command/CacheResetCommand.php', $result['loaded']);
        $this->assertContains('Http/View/ApplicationView.php', $result['loaded']);
    }

    public function testAddFile()
    {
        $preloader = new Preloader();
        $preloader->addFile(APP . '/Console/Command/CacheResetCommand.php');
        $result = $this->removeBasePath($preloader->load());
        $this->assertContains('Console/Command/CacheResetCommand.php', $result['loaded']);
    }

    public function testAddFiles()
    {
        $preloader = new Preloader();
        $preloader->addFiles([APP . '/Console/Command/CacheResetCommand.php',APP . '/Http/View/ApplicationView.php']);
        $result = $this->removeBasePath($preloader->load());
        $this->assertContains('Console/Command/CacheResetCommand.php', $result['loaded']);
        $this->assertContains('Http/View/ApplicationView.php', $result['loaded']);
    }

    public function testAddClass()
    {
        $preloader = new Preloader();
        $preloader->addClass(Autoloader::class);
        $result = $this->removeBasePath($preloader->load());
        $this->assertStringEndsWith('Core/Autoloader.php', $result['loaded'][0]);
    }

    public function testAddClasses()
    {
        $preloader = new Preloader();
        $preloader->addClasses([Autoloader::class,Dot::class]);
        $result = $this->removeBasePath($preloader->load());
        $this->assertStringEndsWith('Core/Autoloader.php', $result['loaded'][0]);
        $this->assertStringEndsWith('Core/Dot.php', $result['loaded'][1]);
    }

    public function testIgnoreFile()
    {
        $preloader = new Preloader();
        $preloader->addDirectory(APP);
        $preloader->ignoreFile(APP . '/Service/ApplicationService.php');
        $result = $this->removeBasePath($preloader->load());

        $this->assertNotContains('Service/ApplicationService.php', $result['loaded']);
        $this->assertContains('Console/Command/SaySomethingCommand.php', $result['loaded']);
        $this->assertContains('Model/ApplicationModel.php', $result['loaded']);
    }

    public function testIgnoreFiles()
    {
        $preloader = new Preloader();
        $preloader->addDirectory(APP);
        $preloader->ignoreFiles([
            APP . '/Service/ApplicationService.php',
            APP . '/Console/Command/SaySomethingCommand.php'
        ]);
 
        $result = $this->removeBasePath($preloader->load());

        $this->assertNotContains('Service/ApplicationService.php', $result['loaded']);
        $this->assertNotContains('Console/Command/SaySomethingCommand.php', $result['loaded']);
        $this->assertContains('Model/ApplicationModel.php', $result['loaded']);
    }

    public function testIgnoreClass()
    {
        $file = (new ReflectionClass(Autoloader::class))->getFileName();
        $directory = pathinfo($file, PATHINFO_DIRNAME);
       
        $preloader = new Preloader();
        $preloader->addDirectory($directory);
        $preloader->ignoreClass(Autoloader::class);
        $result = $this->removeBasePath($preloader->load(), $directory);
        $this->assertNotContains('Autoloader.php', $result['loaded']);
    }

    public function testIgnoreClasses()
    {
        $file = (new ReflectionClass(Autoloader::class))->getFileName();
        $directory = pathinfo($file, PATHINFO_DIRNAME);
       
        $preloader = new Preloader();
        $preloader->addDirectory($directory);
        $preloader->ignoreClasses([Autoloader::class,Dot::class]);
        $result = $this->removeBasePath($preloader->load(), $directory);
        $this->assertNotContains('Autoloader.php', $result['loaded']);
        $this->assertNotContains('Dot.php', $result['loaded']);
    }
}
