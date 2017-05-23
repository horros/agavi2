<?php
namespace Agavi\Tests\Unit\Config;


use Agavi\Config\Config;
use Agavi\Config\ConfigCache;
use Agavi\Exception\UnreadableException;
use Agavi\Testing\Config\TestingConfigCache;
use Agavi\Testing\PhpUnitTestCase;
use Agavi\Util\Toolkit;

class ConfigCacheTest extends PhpUnitTestCase
{
    /**
     * Constructs a test case with the given name.
     *
     * @param  string $name
     * @param  array  $data
     * @param  string $dataName
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }
    
    /**
     * @dataProvider dataGenerateCacheName
     *
     */
    public function testGenerateCacheName($configname, $context, $expected)
    {
        $cachename = ConfigCache::getCacheName($configname, $context);
        $expected = Config::get('core.cache_dir').DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.$expected;
        $this->assertEquals($expected, $cachename);
    }
    
    public function dataGenerateCacheName()
    {
        return array(
            'slashes_null' => array(
                'foo/bar/hash#bang.xml',
                null,
                'hash_bang.xml_'.Config::get('core.environment').'__'.sha1('foo/bar/hash#bang.xml_'.Config::get('core.environment').'_').'.php',
            ),
            '<contextname>' => array(
                'foo/bar/hash#bang.xml',
                '<contextname>',
                'hash_bang.xml_'.Config::get('core.environment').'__contextname__'.sha1('foo/bar/hash#bang.xml_'.Config::get('core.environment').'_<contextname>').'.php',
            ),
        );
    }
    
    public function testCheckConfig()
    {
        $config = Config::get('core.config_dir').DIRECTORY_SEPARATOR.'autoload.xml';
        $config = Toolkit::normalizePath($config);
        $expected = ConfigCache::getCacheName($config);
        if (file_exists($expected)) {
            unlink($expected);
        }
        $cacheName = ConfigCache::checkConfig($config);
        $this->assertEquals($expected, $cacheName);
        $this->assertFileExists($cacheName);
    }
    
    public function testModified()
    {
        $config = Config::get('core.config_dir').DIRECTORY_SEPARATOR.'autoload.xml';
        $cacheName = ConfigCache::getCacheName($config);
        if (!file_exists($cacheName)) {
            $cacheName = ConfigCache::checkConfig($config);
        }
        sleep(1);
        touch($config);
        clearstatcache(); // make shure we don't get fooled by the stat cache
        $this->assertTrue(ConfigCache::isModified($config, $cacheName), 'Failed asserting that the config file has been modified.');
    }

    public function testModifiedNonexistantFile()
    {
        $config = Config::get('core.config_dir').DIRECTORY_SEPARATOR.'autoload.xml';
        $cacheName = ConfigCache::getCacheName($config);
        if (file_exists($cacheName)) {
            unlink($cacheName);
        }
        $this->assertTrue(ConfigCache::isModified($config, $cacheName), 'Failed asserting that the config file has been modified.');
    }
    
    public function testWriteCacheFile()
    {
        $expected = 'This is a config cache test.';
        $config = Config::get('core.config_dir').DIRECTORY_SEPARATOR.'foo.xml';
        $cacheName = ConfigCache::getCacheName($config);
        if (file_exists($cacheName)) {
            unlink($cacheName);
        }
        ConfigCache::writeCacheFile($config, $cacheName, $expected);
        $this->assertFileExists($cacheName);
        $content = file_get_contents($cacheName);
        $this->assertEquals($expected, $content);
        
        $append = "\nAnd a second line appended.";
        ConfigCache::writeCacheFile($config, $cacheName, $append, true);
        $content = file_get_contents($cacheName);
        $this->assertEquals($expected.$append, $content);
    }
    
    public function testload()
    {
        $this->assertFalse( defined('ConfigCacheImportTest_included') );
        ConfigCache::load(Config::get('core.config_dir') . '/tests/importtest.xml');
        $this->assertTrue( defined('ConfigCacheImportTest_included') );

        $GLOBALS["ConfigCacheImportTestOnce_included"] = false;
        ConfigCache::load(Config::get('core.config_dir') . '/tests/importtest_once.xml', true);
        $this->assertTrue( $GLOBALS["ConfigCacheImportTestOnce_included"] );

        $GLOBALS["ConfigCacheImportTestOnce_included"] = false;
        ConfigCache::load(Config::get('core.config_dir') . '/tests/importtest_once.xml', true);
        $this->assertFalse( $GLOBALS["ConfigCacheImportTestOnce_included"] );
    }

    /*
    public function testClear()
    {
        $cacheDir = Config::get('core.cache_dir').DIRECTORY_SEPARATOR.'config';
        ConfigCache::clear();
        $directory = new \DirectoryIterator($cacheDir);
        $files = [];
        foreach ($directory as $item) {
            if ($directory->current()->isDot()) {
                continue;
            }
            $files[] = $item->getFilename();
        }

        $this->assertEmpty($files, 'Failed to assert that "' . $directory->getFilename() . '" is empty, contains "' . implode(',',$files) . '"');

    }
    */
    
    /**
     * @expectedException \Agavi\Exception\UnreadableException
     * this does not seem to work in isolation
     */
    public function testAddNonexistantConfigHandlersFile()
    {
        ConfigCache::addConfigHandlersFile('does/not/exist');
    }
    
    public function testAddConfigHandlersFile()
    {
        $config = Config::get('core.module_dir').'/Default/config/config_handlers.xml';
        TestingConfigCache::addConfigHandlersFile($config);
        $this->assertTrue(TestingConfigCache::handlersDirty(), 'Failed asserting that the handlersDirty flag is set after adding a config handlers file.');
        $handlerFiles = TestingConfigCache::getHandlerFiles();
        $this->assertFalse($handlerFiles[$config], sprintf('Failed asserting that the config file "%1$s" has not been loaded.', $config));
    }
    
    public function testCallHandlers()
    {
        $this->markTestIncomplete();
    }
    
    /*public function testSetupHandlers()
    {
        // this is not possible to test with the agavi unit tests as this needs
        // a really clean env with no framework bootstrapped. Need to think about that.
        //$this->markTestIncomplete();
        TestingConfigCache::resetHandlers();
        $this->assertEquals(null, TestingConfigCache::getHandlers());
        TestingConfigCache::setUpHandlers();
        $handlers = TestingConfigCache::getHandlers();
        $this->assertNotEquals(null, $handlers);
    }*/
    
    public function testGetHandlerInfo()
    {
        $handlerInfo = TestingConfigCache::getHandlerInfo('notregistered');
        $this->assertEquals(null, $handlerInfo);
        
        $expected = array(
            'class' => 'Agavi\\Config\\ReturnArrayConfigHandler',
            'parameters' => array(),
            'transformations' => array(
                'single' => array('confighandler-testing.xsl',),
                'compilation' => array(),
            ),
            'validations' => array(
                'single' => array(
                    'transformations_before' => array(
                        'relax_ng' => array(),
                        'schematron' => array(),
                        'xml_schema' => array(),
                    ),
                    'transformations_after' => array(
                        'relax_ng' => array('confighandler-testing.rng'),
                        'schematron' => array(),
                        'xml_schema' => array(),
                    ),
                ),
                'compilation' => array(
                    'transformations_before' => array(
                        'relax_ng' => array(),
                        'schematron' => array(),
                        'xml_schema' => array(),
                    ),
                    'transformations_after' => array(
                        'relax_ng' => array(),
                        'schematron' => array(),
                        'xml_schema' => array(),
                    ),
                ),
            ),
        );
        $handlerInfo = TestingConfigCache::getHandlerInfo('confighandler-testing');
        $this->assertEquals($expected, $handlerInfo);
    }
    
    public function testTicket931()
    {
        $config = 'project/foo.xml';
        $context = 'with/slash';
        $cachename = ConfigCache::getCacheName($config, $context);
        
        $expected = Config::get('core.cache_dir').DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR;
        $expected .= 'foo.xml';
        $expected .= '_'.preg_replace('/[^\w-_]/i', '_', Config::get('core.environment'));
        $expected .= '_'.preg_replace('/[^\w-_]/i', '_', $context).'_';
        $expected .= sha1($config.'_'.Config::get('core.environment').'_'.$context).'.php';
        
        $this->assertEquals($expected, $cachename);
    }
    
    public function testTicket932()
    {
        $config1 = 'project/foo.xml';
        $config2 = 'project_foo.xml';
        
        $this->assertNotEquals(ConfigCache::getCacheName($config1), ConfigCache::getCacheName($config2));
    }
    
    public function testTicket941()
    {
        if (!extension_loaded('xdebug')) {
            $this->markTestSkipped('This test check for an infinite loop, you need xdebug as protection.');
        }
        
        $config = Config::get('core.module_dir').'/Default/config/config_handlers.xml';
        TestingConfigCache::addConfigHandlersFile($config);
        ConfigCache::checkConfig(Config::get('core.module_dir').'/Default/config/autoload.xml');

        // PHPUnit complains because there is no assertion in the test
        // TODO: Figure out what this test is supposed to do
        $this->assertTrue(true);
    }
}
