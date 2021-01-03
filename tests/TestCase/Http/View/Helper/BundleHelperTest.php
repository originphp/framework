<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Http\View\Helper;

use Origin\Cache\Cache;
use Origin\Http\View\View;
use Origin\Http\Controller\Controller;
use Origin\Http\View\Helper\BundleHelper;

class MockBundleHelper extends BundleHelper
{
    public function bundles(array $config)
    {
        $this->bundles = $config;
    }
}

class BundleHelperTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $controller = new Controller();
        $view = new View($controller);
        $this->Bundler = new MockBundleHelper($view);
    }

    public function testCss()
    {
        $this->Bundler->config([
            'cache' => false
        ]);
        $this->Bundler->bundles([
            'bundle.css' => [
                'foo.css',
                '/css/main.css'
            ]
        ]);
        $html = $this->Bundler->css('bundle.css');
        $version = Cache::read('bundle-' . md5('bundle.css'), ['config' => 'origin']);
        $expected = '<link rel="stylesheet" type="text/css" href="/cache_css/bundle.css?version='. $version.'" />';
        $this->assertEquals($expected, $html);

        $bundled = file_get_contents(WEBROOT . '/cache_css/bundle.css');
        $this->assertEquals('e92db20dac95b7bbdf0ae5ade794e097', md5($bundled));
    }

    /**
     * This tests both the minfication and no cache thing
     */
    public function testNoMinficationAndNoCaching()
    {
        $this->Bundler->config([
            'cache' => false,
            'minify' => false
        ]);
        
        $this->Bundler->bundles([
            'bundle.css' => [
                'foo.css',
                '/css/main.css'
            ]
        ]);
        $html = $this->Bundler->css('bundle.css');
        $version = Cache::read('bundle-' . md5('bundle.css'), ['config' => 'origin']);
        $expected = '<link rel="stylesheet" type="text/css" href="/cache_css/bundle.css?version='. $version.'" />';
        $this->assertEquals($expected, $html);

        $bundled = file_get_contents(WEBROOT . '/cache_css/bundle.css');
        $this->assertEquals('b61a27cc46b3cb6f9de24f724631de78', md5($bundled));
    }

    public function testJs()
    {
        $this->Bundler->bundles([
            'bundle.js' => [
                'application.js',
                '/js/foo.js'
            ]
        ]);
        $html = $this->Bundler->js('bundle.js');
        $version = Cache::read('bundle-' . md5('bundle.js'), ['config' => 'origin']);
        $expected = '<script type="text/javascript" src="/cache_js/bundle.js?version='. $version.'"></script>';
        $this->assertEquals($expected, $html);

        $bundled = file_get_contents(WEBROOT . '/cache_js/bundle.js');
        $this->assertEquals('d89cd5546e43468132fa44b30947f49f', md5($bundled));
    }

    public function testRawCss()
    {
        $this->Bundler->config('raw', true);
        $this->Bundler->bundles([
            'bundle.css' => [
                'foo.css',
                '/css/main.css'
            ]
        ]);
        $html = $this->Bundler->css('bundle');
      
        $this->assertStringContainsString('<link rel="stylesheet" type="text/css" href="/css/foo.css" />', $html);
        $this->assertStringContainsString('<link rel="stylesheet" type="text/css" href="/css/main.css" />', $html);
    }

    public function testRawJs()
    {
        $this->Bundler->config('raw', true);
        $this->Bundler->bundles([
            'bundle.js' => [
                'application.js',
                '/js/foo.js'
            ]
        ]);
        $html = $this->Bundler->js('bundle');
        $this->assertStringContainsString('<script type="text/javascript" src="/js/application.js"></script>', $html);
        $this->assertStringContainsString('<script type="text/javascript" src="/js/foo.js"></script>', $html);
    }

    public function testNoPath()
    {
        $this->Bundler->config('jsPath', '');
        $this->Bundler->bundles([
            'bundle.js' => [
                'application.js',
                '/js/foo.js'
            ]
        ]);
        $html = $this->Bundler->js('bundle.js');
        $version = Cache::read('bundle-' . md5('bundle.js'), ['config' => 'origin']);
        $expected = '<script type="text/javascript" src="/bundle.js?version='. $version.'"></script>';
        $this->assertEquals($expected, $html);
    }
}
