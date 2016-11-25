<?php
namespace Agavi\Tests\Unit\Logging;

use Agavi\Logging\LoggerMessage;
use Agavi\Logging\PassthruLoggerLayout;
use Agavi\Testing\UnitTestCase;

class AgaviPassthruLoggerLayoutTest extends UnitTestCase
{
	public function testFormat()
	{
		$layout = new PassthruLoggerLayout();
		$message = new LoggerMessage('something');
		$this->assertEquals('something', $layout->format($message));
	}
}

?>