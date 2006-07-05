<?php
require_once(dirname(__FILE__) . '/ConfigHandlerTestBase.php');

class TestLogger
{
	const INFO = 1;
	const ERROR = 2;
	public $appenders;
	public $priority;

	public function setAppender($name, $appender)
	{
		$this->appenders[$name] = $appender;
	}

	public function setPriority($priority)
	{
		$this->priority = $priority;
	}
}

class TestLogger1 extends TestLogger { }
class TestLogger2 extends TestLogger { }

class TestAppender
{
	public $params = null;
	public $layout = null;

	public function initialize($params)
	{
		$this->params = $params;
	}

	public function setLayout($layout)
	{
		$this->layout = $layout;
	}

}

class TestAppender1 extends TestAppender { }
class TestAppender2 extends TestAppender { }
class TestAppender3 extends TestAppender { }

class TestLayout
{
	public $params = null;

	public function initialize($params)
	{
		$this->params = $params;
	}
}

class TestLayout1 extends TestLayout { }
class TestLayout2 extends TestAppender { }


class LoggingConfigHandlerTest extends ConfigHandlerTestBase
{
	protected $context;

	public function setUp()
	{
		$this->context = $this;
	}

	public function testLoggingConfigHandler()
	{
		$LCH = new AgaviLoggingConfigHandler();

		$this->includeCode($LCH->execute(AgaviConfig::get('core.config_dir') . '/tests/logging.xml'));

		$test1 = AgaviLoggerManager::getLogger('test1');
		$test2 = AgaviLoggerManager::getLogger('test2');

		$this->assertType('TestLogger1', $test1);
		$this->assertSame(TestLogger::INFO, $test1->priority);
		$this->assertType('TestAppender1', $test1->appenders['appender1']);
		$this->assertType('TestAppender2', $test1->appenders['appender2']);
		$this->assertReference($test1->appenders['appender1'], $test2->appenders['appender1']);
		$this->assertReference($test1->appenders['appender2'], $test2->appenders['appender2']);


		$this->assertType('TestLogger2', $test2);
		$this->assertSame(TestLogger::ERROR, $test2->priority);
		$this->assertType('TestAppender1', $test2->appenders['appender1']);
		$this->assertType('TestAppender2', $test2->appenders['appender2']);
		$this->assertType('TestAppender3', $test2->appenders['appender3']);


		$a1 = $test2->appenders['appender1'];
		$a2 = $test2->appenders['appender2'];
		$a3 = $test2->appenders['appender3'];

		$this->assertType('TestLayout1', $a1->layout);
		$this->assertSame(array(
			'param1' => 'value1',
			'param2' => 'value2',
			),
			$a1->params
		);


		$this->assertType('TestLayout1', $a2->layout);
		$this->assertNull($a2->params);


		$this->assertType('TestLayout2', $a3->layout);
		$this->assertSame(array(
			'file' => AgaviConfig::get('core.webapp_dir') . '/log/myapp.log',
			),
			$a3->params
		);


		$this->assertReference($a1->layout, $a2->layout);

		$l1 = $a1->layout;
		$l2 = $a3->layout;

		$this->assertSame(array(
			'param1' => 'value1',
			'param2' => 'value2',
			),
			$l1->params
		);

		$this->assertNull($l2->params);

	}
}
?>