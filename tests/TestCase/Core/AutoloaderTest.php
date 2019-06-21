<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
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

use Origin\Core\Autoloader;
use Origin\Test\Controller\Component\MockAuthComponent;

class MockAutoloader extends Autoloader
{
    protected $mockFiles = array();

    protected $prefixes = array();
    protected static $backup = null;

    public function setFiles(array $files)
    {
        $this->mockFiles = $files;
    }

    protected function requireFile(string $file)
    {
        return in_array($file, $this->mockFiles);
    }

    public function getFolder()
    {
        return $this->directory;
    }
    public function getDirectory($prefix)
    {
        return $this->prefixes[$prefix];
    }

    public function getPrefixes()
    {
        return $this->prefixes;
    }

    /**
     *  Hacky way to test get instance without messing up autoloading
     *
     * @return void
     */
    public static function disableInstance()
    {
        self::$backup = self::$instance;
        self::$instance = null;
        return true;
    }
    public static function enableInstance()
    {
        self::$instance = self::$backup ;
        self::$backup = null;
        return true;
    }
}

class AutoloaderTest extends \PHPUnit\Framework\TestCase
{
    protected $autoloader;

    protected function setUp()
    {
        $this->autoloader = new MockAutoloader(ROOT);
        
        $this->autoloader->setFiles(array(
            ROOT.'/src/Autoloader.php',
            ROOT.'/src/Network/Socket.php',
            ROOT.'/vendor/elements/src/Database/Dbo.php',
            ROOT.'/vendor/elements/src/Database/Driver/Mysql.php',
        ));

        $this->autoloader->addNamespace(
            'Origin',
            'src'
        );

        $this->autoloader->addNamespace(
            'Elements\Database',
            'vendor/elements/src/Database'
        );
    }

    public function testInstance()
    {
        $this->assertInstanceOf(Autoloader::class, Autoloader::instance());
    }
    
    public function testRegister()
    {
        $Autoloader = Autoloader::instance();
        $this->assertTrue($Autoloader->register());
    }

    public function testDirectory()
    {
        $this->assertEquals(ROOT.'/src/', $this->autoloader->getDirectory('Origin\\'));
    }

    public function testExistingFile()
    {
        $actual = $this->autoloader->load('Origin\Autoloader');
        $expect = ROOT.'/src/Autoloader.php';
        $this->assertSame($expect, $actual);

        $actual = $this->autoloader->load('Origin\Network\Socket');
        $expect = ROOT.'/src/Network/Socket.php';
        $this->assertSame($expect, $actual);

        $actual = $this->autoloader->load('Elements\Database\Dbo');
        $expect = ROOT.'/vendor/elements/src/Database/Dbo.php';
        $this->assertSame($expect, $actual);

        $actual = $this->autoloader->load('Elements\Database\Driver\Mysql');
        $expect = ROOT.'/vendor/elements/src/Database/Driver/Mysql.php';
        $this->assertSame($expect, $actual);
    }

    public function testMissingFile()
    {
        $actual = $this->autoloader->load('No_Vendor\No_Package\NoClass');
        $this->assertFalse($actual);
    }
    public function testAddNamespaces()
    {
        $Autoloader = new MockAutoloader(ROOT);
        $Autoloader->addNamespaces(['Origin'=> 'src']);
    
        $expected = ['Origin\\'=> ROOT  . '/src/'];
        $this->assertEquals($expected, $Autoloader->getPrefixes());
    }

    public function testSetFolder()
    {
        $Autoloader = new MockAutoloader(ROOT);
        $expected = '/var/www/someFolder';
        $Autoloader->directory($expected);
        $this->assertEquals($expected, $Autoloader->directory());
    }
    public function testInstanceEnableDisable()
    {
        $this->assertTrue(MockAutoloader::disableInstance());
        $this->assertInstanceOf(Autoloader::class, MockAutoloader::instance());
        $this->assertTrue(MockAutoloader::enableInstance());
    }
}
