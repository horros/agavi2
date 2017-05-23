<?php
namespace Agavi\Tests\Unit\Config;

use Agavi\Config\Config;
use Agavi\Config\LoggingConfigHandler;
use Agavi\Core\Context;
use Agavi\Logging\Logger;
use Agavi\Logging\LoggerAppender;
use Agavi\Logging\LoggerInterface;
use Agavi\Logging\LoggerLayout;
use Agavi\Logging\LoggerMessage;

require_once(__DIR__ . '/ConfigHandlerTestBase.php');

class TestLogger extends Logger
{
    public $appenders;
    public $level;

    public function setAppender($name, LoggerAppender $appender)
    {
        $this->appenders[$name] = $appender;
    }

    public function setLevel($level)
    {
        $this->level = $level;
    }
}

class TestLogger1 extends TestLogger
{

}
class TestLogger2 extends TestLogger
{

}
class TestLogger3 extends TestLogger
{

}

class TestAppender extends LoggerAppender
{
    public $params = null;
    public $layout = null;

    public function initialize(Context $context, array $params = array())
    {
        $this->params = $params;
    }

    public function setLayout(LoggerLayout $layout)
    {
        $this->layout = $layout;
    }

    public function shutdown()
    {
    }
    public function write(LoggerMessage $message)
    {
    }
}

class TestAppender1 extends TestAppender
{

}
class TestAppender2 extends TestAppender
{

}
class TestAppender3 extends TestAppender
{

}

class TestLayout extends LoggerLayout
{
    public $params = null;

    public function initialize(Context $context, array $params = array())
    {
        $this->params = $params;
    }
    public function format(LoggerMessage $message)
    {
    }
}

class TestLayout1 extends TestLayout
{

}
class TestLayout2 extends TestLayout
{

}


class LoggingConfigHandlerTest extends ConfigHandlerTestBase
{
    /** @var Context */
    protected $context;

    public function setUp()
    {
        $this->context = Context::getInstance();
    }

    /**
     * Proxied because we include a compiled config that assumes it runs in the LM
     *
     * @see      AgaviLoggerManager::setLogger()
     */
    protected function setLogger($name, LoggerInterface $logger)
    {
         $this->context->getLoggerManager()->setLogger($name, $logger);
    }
    
    /**
     * Proxied because we include a compiled config that assumes it runs in the LM
     *
     * @see      AgaviLoggerManager::setDefaultLoggerName()
     */
    public function setDefaultLoggerName($name)
    {
        $this->context->getLoggerManager()->setDefaultLoggerName($name);
    }

    /**
     * @runInSeparateProcess
     */
    public function testLoggingConfigHandler()
    {
        $document = $this->parseConfiguration(
            Config::get('core.config_dir') . '/tests/logging.xml',
            Config::get('core.agavi_dir') . '/config/xsl/logging.xsl'
        );

        $LCH = new LoggingConfigHandler();
        $cfg = $this->includeCode($LCH->execute($document));

        $test1 = $this->context->getLoggerManager()->getLogger('test1');
        $test2 = $this->context->getLoggerManager()->getLogger('test2');
        $test3 = $this->context->getLoggerManager()->getLogger('test3');

        $t1appenders = $test1->getAppenders();
        $t2appenders = $test2->getAppenders();

        $t1lvl = $test1->getLevel();
        $t2lvl = $test2->getLevel();
        $t3lvl = $test3->getLevel();

        $this->assertInstanceOf('Agavi\\Tests\\Unit\\Config\\TestLogger1', $test1);
        $this->assertSame(TestLogger::INFO, $t1lvl);
        $this->assertInstanceOf('Agavi\\Tests\\Unit\\Config\\TestAppender1', $t1appenders['appender1']);
        $this->assertInstanceOf('Agavi\\Tests\\Unit\\Config\\TestAppender2', $t1appenders['appender2']);
        $this->assertSame($t1appenders['appender1'], $t2appenders['appender1']);
        $this->assertSame($t1appenders['appender2'], $t2appenders['appender2']);


        $this->assertInstanceOf('Agavi\\Tests\\Unit\\Config\\TestLogger2', $test2);
        $this->assertSame(TestLogger::ERROR, $t2lvl);
        $this->assertInstanceOf('Agavi\\Tests\\Unit\\Config\\TestAppender1', $t2appenders['appender1']);
        $this->assertInstanceOf('Agavi\\Tests\\Unit\\Config\\TestAppender2', $t2appenders['appender2']);
        $this->assertInstanceOf('Agavi\\Tests\\Unit\\Config\\TestAppender3', $t2appenders['appender3']);

        $this->assertInstanceOf('Agavi\\Tests\\Unit\\Config\\TestLogger3', $test3);
        $this->assertSame(TestLogger::INFO | TestLogger::ERROR, $t3lvl);

        $a1 = $t2appenders['appender1'];
        $a2 = $t2appenders['appender2'];
        $a3 = $t2appenders['appender3'];

        $this->assertInstanceOf('Agavi\\Tests\\Unit\\Config\\TestLayout1', $a1->layout);
        $this->assertSame(array(
            'param1' => 'value1',
            'param2' => 'value2',
            ),
            $a1->params
        );


        $this->assertInstanceOf('Agavi\\Tests\\Unit\\Config\\TestLayout1', $a2->layout);
        $this->assertEquals(array(), $a2->params);


        $this->assertInstanceOf('Agavi\\Tests\\Unit\\Config\\TestLayout2', $a3->layout);
        $this->assertSame(array(
            'file' => Config::get('core.app_dir') . '/log/myapp.log',
            ),
            $a3->params
        );


        $this->assertSame($a1->layout, $a2->layout);

        $l1 = $a1->layout;
        $l2 = $a3->layout;

        $this->assertSame(array(
            'param1' => 'value1',
            'param2' => 'value2',
            ),
            $l1->params
        );

        $this->assertSame(array(), $l2->params);
    }
}
