<?php
namespace Agavi\Tests\Unit\Config;

use Agavi\Config\AutoloadConfigHandler;
use Agavi\Config\Config;
use Agavi\Exception\ParseException;

require_once(__DIR__ . '/ConfigHandlerTestBase.php');

class MyAutoloader
{
    public static $classes;
    public static $namespaces;
    
    public static function addClasses($classes)
    {
        static::$classes = $classes;
    }
    
    public static function addNamespaces($namespaces)
    {
        static::$namespaces = $namespaces;
    }
}

class AutoloadConfigHandlerTest extends \Agavi\Tests\Unit\Config\ConfigHandlerTestBase
{
    protected function runHandler($environment = null)
    {
        $ACH = new AutoloadConfigHandler();

        $document = $this->parseConfiguration(
            Config::get('core.config_dir') . '/tests/autoload_simple.xml',
            Config::get('core.agavi_dir') . '/config/xsl/autoload.xsl',
            $environment
        );
        // Autoloader will have all of Agavi's as well, so let's replace it with our "mock"
        $code = str_replace('Agavi\\Util\\Autoloader::', 'Agavi\\Tests\\Unit\\Config\\MyAutoloader::', $ACH->execute($document));

        $this->includeCode($code);
    }
    
    public function testBasic()
    {
        $this->runHandler();
        $expected = array(
            'AgaviConfigAutoloadClass1' => Config::get('core.app_dir') . '/lib/config/autoload/AgaviConfigAutoloadClass1.class.php',
            'AgaviConfigAutoloadClass2' => Config::get('core.app_dir') . '/lib/config/autoload/AgaviConfigAutoloadClass2.class.php',
            'AgaviConfigAutoloadClass3' => Config::get('core.app_dir') . '/lib/config/autoload/AgaviConfigAutoloadClass3.class.php',
        );

        $this->assertEquals($expected, MyAutoloader::$classes);
        
        $expected = array(
            'TestAbsolute' => Config::get('core.app_dir') . '/lib/config/autoload',
            'TestRelative' => Config::get('core.app_dir') . '/lib/config/autoload',
        );

        $this->assertEquals($expected, MyAutoloader::$namespaces);
    }

    public function testOverwrite()
    {
        $this->runHandler('test-overwrite');
        $expected = array(
            'AgaviConfigAutoloadClass1' => Config::get('core.app_dir') . '/lib/config/autoload/AgaviConfigAutoloadClass1.class.php',
            'AgaviConfigAutoloadClass2' => Config::get('core.app_dir') . '/lib/config/autoload/AgaviConfigAutoloadClass3.class.php',
            'AgaviConfigAutoloadClass3' => Config::get('core.app_dir') . '/lib/config/autoload/AgaviConfigAutoloadClass3.class.php',
        );

        $this->assertEquals($expected, MyAutoloader::$classes);
        
        $expected = array(
            'TestAbsolute' => Config::get('core.app_dir') . '/lib/config',
            'TestRelative' => Config::get('core.app_dir') . '/lib/config/autoload',
        );

        $this->assertEquals($expected, MyAutoloader::$namespaces);
    }

    /**
     * @expectedException Agavi\Exception\ParseException
     */
    public function testClassMissing()
    {
        $this->runHandler('test-class-missing');
    }

    /**
     * @expectedException Agavi\Exception\ParseException
     */
    public function testNamespaceMissing()
    {
        $this->runHandler('test-namespace-missing');
    }
}
