<?php
namespace Agavi\Testing\Unit\Date;
use Agavi\Date\TimeZone;
use Agavi\Testing\UnitTestCase;


class Ticket958Test extends UnitTestCase
{
	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testTicket958()
	{
		$tm = $this->getContext()->getTranslationManager();
		$tz = TimeZone::createCustomTimeZone($tm, '+01:00');
	}
}

?>