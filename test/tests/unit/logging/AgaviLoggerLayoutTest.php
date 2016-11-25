<?php
namespace Agavi\Tests\Unit\Logging;
use Agavi\Logging\LoggerLayout;
use Agavi\Logging\LoggerMessage;
use Agavi\Testing\UnitTestCase;

class SampleLayout extends LoggerLayout
{
	public function format(LoggerMessage $message)
	{
	}
}

class AgaviLoggerLayoutTest extends UnitTestCase
{
	public function testGetSetLayout()
	{
		$layout = new SampleLayout;
		$this->assertNull($layout->getLayout());
		$layout->setLayout('something');
		$this->assertEquals('something', $layout->getLayout());
	}
}

?>