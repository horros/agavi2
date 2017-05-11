<?php
namespace Agavi\Tests\Unit\Config;

use Agavi\Config\Config;
use Agavi\Config\DatabaseConfigHandler;
use Agavi\Exception\ConfigurationException;

require_once(__DIR__ . '/ConfigHandlerTestBase.php');

class DCHTestDatabase
{
    public $params;

    public function initialize($dbm, $params)
    {
        $this->params = $params;
    }
}

class DatabaseConfigHandlerTest extends ConfigHandlerTestBase
{
    protected $databases;
    protected $defaultDatabaseName;

    public function setUp()
    {
        $this->databases = array();
    }
    
    protected function loadTestConfig($env = null)
    {
        $DBCH = new DatabaseConfigHandler();
        
        $document = $this->parseConfiguration(
            Config::get('core.config_dir') . '/tests/databases.xml',
            Config::get('core.agavi_dir') . '/config/xsl/databases.xsl',
            $env
        );

        $this->includeCode($DBCH->execute($document));
    }

    public function testDatabaseConfigHandler()
    {
        $this->loadTestConfig();

        $this->assertInstanceOf('Agavi\\Tests\\Unit\\Config\\DCHTestDatabase', $this->databases['test1']);
        $paramsExpected = array(
            'host' => 'localhost1',
            'user' => 'username1',
            'config' => Config::get('core.app_dir') . '/config/project-conf.php',
        );
        $this->assertSame($paramsExpected, $this->databases['test1']->params);

        $this->assertSame($this->databases['test1'], $this->databases[$this->defaultDatabaseName]);
    }

    public function testOverwrite()
    {
        $this->loadTestConfig('env2');

        $this->assertInstanceOf('Agavi\\Tests\\Unit\\Config\\DCHTestDatabase', $this->databases['test1']);
        $paramsExpected = array(
            'host' => 'localhost1',
            'user' => 'testuser1',
            'config' => Config::get('core.app_dir') . '/config/project-conf.php',
        );
        $this->assertSame($paramsExpected, $this->databases['test1']->params);

        $this->assertSame($this->databases['test2'], $this->databases[$this->defaultDatabaseName]);
    }
    
    public function testMissingDefaultDoesNotReset()
    {
        // see https://github.com/agavi/agavi/issues/1533
        $this->loadTestConfig('missing-default-does-not-reset');

        $this->assertSame('test1', $this->defaultDatabaseName);
    }

    public function testDefaultDatabase()
    {
        $this->loadTestConfig('test-default');
        
        $this->assertSame('test2', $this->defaultDatabaseName);
    }

    public function testDefaultDatabase1_0()
    {
        $this->loadTestConfig('test-default-1.0');
        
        $this->assertSame('test1', $this->defaultDatabaseName);
    }
    
    /**
     * @expectedException Agavi\Exception\ConfigurationException
     */
    public function testNonExistentDefault()
    {
        $this->loadTestConfig('nonexistent-default');
    }
}
