<?php
namespace Agavi\Testing\Unit\Date;
use Agavi\Date\Calendar;
use Agavi\Date\GregorianCalendar;
use Agavi\Testing\UnitTestCase;

class GregorianCalendarTest extends UnitTestCase
{
	/** @var Calendar */
	private $cal;
	public function setUp()
	{
		$this->cal = new GregorianCalendar($this->getContext()->getTranslationManager()->createTimeZone('Europe/Berlin'));
		// 2009-02-21 12:30:20
		$this->cal->setUnixTimestamp(1235215820);
	}
	
	public function testGetNativeDateTime()
	{
		$dt = $this->cal->getNativeDateTime();
		$this->assertEquals('2009-02-21 12:30:20 Europe/Berlin', $dt->format('Y-m-d H:i:s e'));
	}

	public function testGetNativeDateTimeWithCustomTimeZone()
	{
		$this->cal->setTimeZone($this->getContext()->getTranslationManager()->createTimeZone('GMT+01:00'));
		$dt = $this->cal->getNativeDateTime();
		$this->assertEquals('2009-02-21 12:30:20 +01:00', $dt->format('Y-m-d H:i:s e'));
	}
}


?>