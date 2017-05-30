<?php
namespace Agavi\Testing\Unit\Context;

use Agavi\Config\Config;
use Agavi\Core\Context;
use Agavi\Testing\PhpUnitTestCase;

class ContextTest extends PhpUnitTestCase
{
    public function testGetInstance()
    {
        $instance = Context::getInstance('foo');
        $this->assertNotNull($instance);
        $this->assertInstanceOf('Agavi\\Core\\Context', $instance);
    }
    
    public function testSameInstanceForSameProfile()
    {
        $instance1 = Context::getInstance('foo');
        $instance2 = Context::getInstance('foo');
        $this->assertSame($instance1, $instance2);
    }
    
    public function testDifferentInstanceForDifferentProfile()
    {
        $instance1 = Context::getInstance('foo');
        $instance2 = Context::getInstance('bar');
        $this->assertNotSame($instance1, $instance2);
    }
    
    public function testGetName()
    {
        $this->assertSame(Config::get('core.default_context'), Context::getInstance()->getName());
        $this->assertSame('test1', Context::getInstance('test1')->getName());
    }
    
    /**
     * @dataProvider dataGetModel
     */
    public function testGetModel($modelName, $className, $isSingleton, $module = null)
    {
        $ctx = Context::getInstance();
        $model1 = $ctx->getModel($modelName, $module);
        $model2 = $ctx->getModel($modelName, $module);
        $this->assertInstanceOf($className, $model1);
        $this->assertInstanceOf($className, $model2);
        if ($isSingleton) {
            $this->assertSame($model1, $model2);
        } else {
            $this->assertNotSame($model1, $model2);
        }
    }
    
    public function dataGetModel()
    {
        return array(
            'global normal model' => array('ContextTest', 'Sandbox\Models\ContextTestModel', false),
            'global singleton model' => array('ContextTestSingleton', 'Sandbox\Models\ContextTestSingletonModel', true),
            'global model in child path' => array('ContextTest.Child.Test', 'Sandbox\Models\ContextTest\Child\TestModel', false),
            'module normal model' => array('Test', 'Sandbox\Modules\ContextTest\Models\TestModel', false, 'ContextTest'),
            'module singleton model' => array('TestSingleton', 'Sandbox\Modules\ContextTest\Models\TestSingletonModel', true, 'ContextTest'),
            'module model in child path' => array('Parent.Child.Test', 'Sandbox\Modules\ContextTest\Models\Parent\Child\TestModel', false, 'ContextTest'),
        );
    }
    


    public function testGetFactoryInfo()
    {
        $ctx = Context::getInstance('test');
        $expected = array('class' => 'Agavi\\Response\\WebResponse', 'parameters' => array());
        $this->assertSame($expected, $ctx->getFactoryInfo('response'));
    }

    public function testGetDispatcher()
    {
        $this->assertInstanceOf('Agavi\\Dispatcher\\Dispatcher', Context::getInstance()->getDispatcher());
    }

    /**
     * @runInSeparateProcess
     * @agaviIsolationEnvironment testing-use_database_off
     */
    public function testGetDatabaseManagerOff()
    {
        $this->assertNull(Context::getInstance()->getDatabaseManager());
    }

    /**
     * @runInSeparateProcess
     * @agaviIsolationEnvironment testing-use_database_on
     */
    public function testGetDatabaseManagerOn()
    {
        $this->assertInstanceOf('Agavi\\Database\\DatabaseManager', Context::getInstance()->getDatabaseManager());
    }
    
    /**
     * @runInSeparateProcess
     * @agaviIsolationEnvironment testing-use_security_off
     */
    public function testGetUserSecurityOff()
    {
        $this->assertInstanceOf('Agavi\User\User', Context::getInstance()->getUser());
        $this->assertNotInstanceOf('Agavi\User\SecurityUser', Context::getInstance()->getUser());
    }

    /**
     * @runInSeparateProcess
     * @agaviIsolationEnvironment testing-use_security_on
     */
    public function testGetUserSecurityOn()
    {
        $this->assertInstanceOf('Agavi\\User\\SecurityUser', Context::getInstance()->getUser());
    }

    /**
     * @runInSeparateProcess
     * @agaviIsolationEnvironment testing-use_translation_off
     */
    public function testGetTranslationManagerOff()
    {
        $this->assertNull(Context::getInstance()->getTranslationManager());
    }

    /**
     * @runInSeparateProcess
     * @agaviIsolationEnvironment testing-use_logging_on
     */
    public function testGetTranslationManagerOn()
    {
        $this->assertInstanceOf('Agavi\\Translation\\TranslationManager', Context::getInstance()->getTranslationManager());
    }

    public function testGetLoggerManager()
    {
        $this->assertInstanceOf('Agavi\\Logging\\LoggerManager', Context::getInstance()->getLoggerManager());
    }

    public function testGetRequest()
    {
        $ctx = Context::getInstance();
        $this->assertInstanceOf('Agavi\\Request\\Request', $ctx->getRequest());
    }

    public function testGetRouting()
    {
        $ctx = Context::getInstance();
        $this->assertInstanceOf('Agavi\\Routing\\Routing', $ctx->getRouting());
    }

    public function testGetStorage()
    {
        $ctx = Context::getInstance();
        $this->assertInstanceOf('Agavi\\Storage\\Storage', $ctx->getStorage());
    }
}
