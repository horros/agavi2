<?php
namespace Agavi\Tests\Unit\Logging;
use Agavi\Logging\FileLoggerAppender;
use Agavi\Logging\Logger;
use Agavi\Logging\LoggerInterface;
use Agavi\Logging\LoggerManager;
use Agavi\Logging\LoggerMessage;
use Agavi\Logging\PassthruLoggerLayout;
use Agavi\Testing\UnitTestCase;

class AgaviLoggerManagerTest extends UnitTestCase
{

	private
		$_context = null,
		$_logfile = '',
		$_logfile2 = '',
		$_pl = null,

		$_fa2 = null,
		$_l = null,
		$_l2 = null;
	/**
	 * @var LoggerManager
	 */
	private $_lm = null;

	/**
	 * @var FileLoggerAppender;
	 */
	private $_fa = null;

	public function setUp()
	{
		$this->_context = $this->getContext();
		$this->_lm = $this->_context->getLoggerManager();
		$this->_logfile = tempnam('','logtest');
		$this->_logfile2 = tempnam('', 'logtest2');
		@unlink($this->_logfile);
		@unlink($this->_logfile2);
		$this->_pl = new PassthruLoggerLayout();
		$this->_fa = new FileLoggerAppender();
		$this->_fa->initialize($this->_context, array('file' => $this->_logfile));
		$this->_fa->setLayout($this->_pl);
		$this->_fa2 = new FileLoggerAppender();
		$this->_fa2->initialize($this->_context, array('file' => $this->_logfile2));
		$this->_fa2->setLayout($this->_pl);
		$this->_l = new Logger();
		$this->_l->setLevel(LoggerInterface::INFO);
		$this->_l->setAppender('fa', $this->_fa);
		$this->_l2 = new Logger();
		$this->_l2->setLevel(LoggerInterface::DEBUG | LoggerInterface::INFO);
		$this->_l2->setAppender('fa2', $this->_fa2);
	}

	public function tearDown()
	{
		$this->_lm->shutdown();
		@unlink($this->_logfile);
		@unlink($this->_logfile2);
		$this->_lm = null;
		$this->_context = null;
	}

	public function testGetLoggerNames()
	{
		$this->assertEquals(array(), $this->_lm->getLoggerNames());
		$this->_lm->setLogger('logfile', $this->_l);
		$this->assertEquals(array('logfile'), $this->_lm->getLoggerNames());
		$this->_lm->setLogger('logfile2', $this->_l2);
		$this->assertEquals(array('logfile', 'logfile2'), $this->_lm->getLoggerNames());
	}

	public function testGetLogger()
	{
		$this->_lm->setLogger($this->_lm->getDefaultLoggerName(), $this->_l);
		$this->assertEquals($this->_l, $this->_lm->getLogger());
		$this->_lm->setLogger('logfile2', $this->_l2);
		$this->assertEquals($this->_l, $this->_lm->getLogger('default'));
		$this->assertEquals($this->_l2, $this->_lm->getLogger('logfile2'));
	}

	public function testLoggerLogLevel()
	{
		$this->assertEquals(LoggerInterface::INFO, $this->_l->getLevel());
		$this->assertEquals(LoggerInterface::DEBUG | LoggerInterface::INFO, $this->_l2->getLevel());
	}

	public function testLog()
	{
		$this->_lm->setLogger('logfile', $this->_l);
		$this->_lm->setLogger('logfile2', $this->_l2);
		$this->assertFalse(file_exists($this->_logfile));
		$this->assertFalse(file_exists($this->_logfile2));

		//this should be logged by both
		$this->_lm->log(new LoggerMessage('simple info message', LoggerInterface::INFO));
		$this->assertRegexp('/simple info message/', file_get_contents($this->_logfile));
		$this->assertRegexp('/simple info message/', file_get_contents($this->_logfile2));

		//this should be logged only by l2
		$this->_lm->log(new LoggerMessage('simple debug message', LoggerInterface::DEBUG));
		$this->assertNotRegexp('/simple debug message/', file_get_contents($this->_logfile));
		$this->assertRegexp('/simple debug message/', file_get_contents($this->_logfile2));

		//this should be logged only by l2
		$this->_lm->log('simple debug message two', LoggerInterface::DEBUG);
		$this->assertNotRegexp('/simple debug message two/', file_get_contents($this->_logfile));
		$this->assertRegexp('/simple debug message two/', file_get_contents($this->_logfile2));

		//this should be logged only by l
		$this->_lm->log('simple debug message three', $this->_l);
		$this->assertRegexp('/simple debug message three/', file_get_contents($this->_logfile));
		$this->assertNotRegexp('/simple debug message three/', file_get_contents($this->_logfile2));

		//this should be logged only by l
		$this->_lm->log(new LoggerMessage('simple info message four', LoggerInterface::INFO), $this->_l);
		$this->assertRegexp('/simple info message four/', file_get_contents($this->_logfile));
		$this->assertNotRegexp('/simple info message four/', file_get_contents($this->_logfile2));
	}

}

?>