<?php
namespace Agavi\Tests\Unit\Logging;

use Agavi\Config\Config;
use Agavi\Logging\FileLoggerAppender;
use Agavi\Logging\LoggerMessage;
use Agavi\Logging\PassthruLoggerLayout;
use Agavi\Testing\UnitTestCase;

class FileLoggerAppenderTest extends UnitTestCase
{

    private $_file;
    /**
     * @var FileLoggerAppender;
     */
    private $_fa;

    public function setUp()
    {
        $this->_file = tempnam(Config::get('core.cache_dir'), 'AgaviFileLoggerAppenderTest');
        unlink($this->_file);
        $this->_fa = new FileLoggerAppender();
        $this->_fa->initialize($this->getContext(), array('file'=>$this->_file));
        $this->_fa->setLayout(new PassthruLoggerLayout());
    }

    public function tearDown()
    {
        @unlink($this->_file);
    }

    public function testInitialize()
    {
        $this->assertFalse(file_exists($this->_file));
        $this->_fa->write(new LoggerMessage('my message'));
        $this->assertTrue(file_exists($this->_file));
        $this->_fa->shutdown();
    }

    public function testWrite()
    {
        $this->_fa->write(new LoggerMessage('my message'));
        $this->assertRegexp('/my message/', file_get_contents($this->_file));
        $this->_fa->shutdown();
    }

    /*
	public function testshutdown()
	{
		// how do you test if the file is still open? - flock() and then attempt to remove it (??)
	}
	*/
}
