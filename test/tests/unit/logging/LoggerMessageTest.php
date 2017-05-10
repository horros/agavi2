<?php
namespace Agavi\Tests\Unit\Logging;
use Agavi\Logging\LoggerInterface;
use Agavi\Logging\LoggerMessage;
use Agavi\Testing\UnitTestCase;

class LoggerMessageTest extends UnitTestCase
{
	public function testConstructor()
	{
		$message = new LoggerMessage();
		$this->assertNull($message->getMessage());
		$this->assertEquals(LoggerInterface::INFO, $message->getLevel());
		$message = new LoggerMessage('test');
		$this->assertEquals('test', $message->getMessage());
		$this->assertEquals(LoggerInterface::INFO, $message->getLevel());
		$message = new LoggerMessage('test', LoggerInterface::DEBUG);
		$this->assertEquals('test', $message->getMessage());
		$this->assertEquals(LoggerInterface::DEBUG, $message->getLevel());
	}

	public function testGetsetappendMessage()
	{
		$message = new LoggerMessage();
		$message->setMessage('my message');
		$this->assertEquals('my message', $message->getMessage());
		$message->setMessage('my message 2');
		$this->assertEquals('my message 2', $message->getMessage());
		$message->appendMessage('my message 3');
		$this->assertEquals(array('my message 2', 'my message 3'), $message->getMessage());
	}

	public function test__toString()
	{
		$message = new LoggerMessage('test message', LoggerInterface::INFO);
		$this->assertEquals('test message', $message->__toString());
		$message->appendMessage('another line');
		$this->assertEquals("test message\nanother line", $message->__toString());
	}

	public function testGetsetLevel()
	{
		$message = new LoggerMessage;
		$message->setLevel(LoggerInterface::DEBUG);
		$this->assertEquals(LoggerInterface::DEBUG, $message->getLevel());
		$message->setLevel(LoggerInterface::INFO);
		$this->assertEquals(LoggerInterface::INFO, $message->getLevel());
	}

}

?>