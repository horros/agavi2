<?php
namespace Agavi\Testing\Unit\Date;
use Agavi\Date\Calendar;
use Agavi\Date\DateDefinitions;
use Agavi\Date\DateFormat;
use Agavi\Date\GregorianCalendar;
use Agavi\Date\SimpleTimeZone;
use Agavi\Date\TimeZone;
use Agavi\Translation\Locale;
use Doctrine\Instantiator\Exception\InvalidArgumentException;

require_once(__DIR__ . '/BaseCalendarTest.php');

/**
 * Ported from ICU:
 *  icu/trunk/source/test/intltest/caltest.cpp r21087
 */
class AgaviCalendarTest extends BaseCalendarTest
{
	protected function fieldName($id)
	{
		switch($id) {
			case DateDefinitions::ERA:
				return 'UCAL_ERA';

			case DateDefinitions::YEAR:
				return 'UCAL_YEAR';

			case DateDefinitions::MONTH:
				return 'UCAL_MONTH';

			case DateDefinitions::WEEK_OF_YEAR:
				return 'UCAL_WEEK_OF_YEAR';

			case DateDefinitions::WEEK_OF_MONTH:
				return 'UCAL_WEEK_OF_MONTH';

			case DateDefinitions::DATE:
				return 'UCAL_DATE';

			case DateDefinitions::DAY_OF_YEAR:
				return 'UCAL_DAY_OF_YEAR';

			case DateDefinitions::DAY_OF_WEEK:
				return 'UCAL_DAY_OF_WEEK';

			case DateDefinitions::DAY_OF_WEEK_IN_MONTH:
				return 'UCAL_DAY_OF_WEEK_IN_MONTH';

			case DateDefinitions::AM_PM:
				return 'UCAL_AM_PM';

			case DateDefinitions::HOUR:
				return 'UCAL_HOUR';

			case DateDefinitions::HOUR_OF_DAY:
				return 'UCAL_HOUR_OF_DAY';

			case DateDefinitions::MINUTE:
				return 'UCAL_MINUTE';

			case DateDefinitions::SECOND:
				return 'UCAL_SECOND';

			case DateDefinitions::MILLISECOND:
				return 'UCAL_MILLISECOND';

			case DateDefinitions::ZONE_OFFSET:
				return 'UCAL_ZONE_OFFSET';

			case DateDefinitions::DST_OFFSET:
				return 'UCAL_DST_OFFSET';

			case DateDefinitions::YEAR_WOY:
				return 'UCAL_YEAR_WOY';

			case DateDefinitions::DOW_LOCAL:
				return 'UCAL_DOW_LOCAL';

			case DateDefinitions::EXTENDED_YEAR:
				return 'UCAL_EXTENDED_YEAR';

			case DateDefinitions::JULIAN_DAY:
				return 'UCAL_JULIAN_DAY';

			case DateDefinitions::MILLISECONDS_IN_DAY:
				return 'UCAL_MILLISECONDS_IN_DAY';

			case DateDefinitions::FIELD_COUNT:
				return 'UCAL_FIELD_COUNT';
		}

		return 'UNKNOWN_FIELD';
	}

	/**
	 * Test various API methods for API completeness.
	 */
	public function testGenericAPI()
	{
		/*
		UErrorCode status = U_ZERO_ERROR;
		UDate d;
		UnicodeString str;
		*/
		$eq = $b4 = $af = false;

		$when = $this->date(90, DateDefinitions::APRIL, 15);

		$tzid = "TestZone";
		$tzoffset = 123400;

		$zone = new SimpleTimeZone($this->tm, $tzoffset, $tzid);
		$cal = $this->tm->createCalendar($zone);

		$this->assertTrue($cal->getTimeZone()->__is_equal($zone), 'Calendar::getTimeZone failed');

		$cal2 = $this->tm->createCalendar($cal->getTimeZone());
		$cal->setTime($when);
		$cal2->setTime($when);

		$this->assertTrue($cal->__is_equal($cal2), 'Calendar::operator== failed');
		$this->assertFalse($cal->__is_not_equal($cal2), 'Calendar::operator!= failed');
		$this->assertFalse((!$cal->equals($cal2) || $cal->before($cal2) || $cal->after($cal2)), 'equals/before/after failed');

		$cal2->setTime($when + 1000);

		$b1 = $cal->equals($cal2);
		$b2 = $cal2->before($cal);
		$b3 = $cal->after($cal2);
		$this->assertFalse($cal->equals($cal2) || $cal2->before($cal) || $cal->after($cal2), 'equals/before/after failed after setTime(+1000)');

		$cal->roll(DateDefinitions::SECOND, true);

		$this->assertFalse(!($eq = $cal->equals($cal2)) || ($b4 = $cal->before($cal2)) || ($af = $cal->after($cal2)), sprintf("equals[%s]/before[%s]/after[%s] failed after roll 1 second [should be T/F/F]", $eq ? 'T' : 'F', $b4 ? 'T' : 'F', $af ? 'T' : 'F'));

		// Roll back to January
		$cal->roll(DateDefinitions::MONTH, (int)(1 + DateDefinitions::DECEMBER - $cal->get(DateDefinitions::MONTH)));

		$this->assertFalse($cal->equals($cal2) || $cal2->before($cal) || $cal->after($cal2), 'equals/before/after failed after rollback to January');

		for($i = 0; $i < 2; ++$i) {
			$lenient = ( $i > 0 );
			$cal->setLenient($lenient);
			$this->assertEquals($lenient, $cal->isLenient(), 'setLenient/isLenient failed');
			// Later: Check for lenient behavior
		}

		for($i = DateDefinitions::SUNDAY; $i <= DateDefinitions::SATURDAY; ++$i) {
			$cal->setFirstDayOfWeek($i);
			$this->assertEquals($i, $cal->getFirstDayOfWeek(), 'set/getFirstDayOfWeek failed');
		}

		for($i = 1; $i <= 7; ++$i) {
			$cal->setMinimalDaysInFirstWeek($i);
			$this->assertEquals($i, $cal->getMinimalDaysInFirstWeek(), 'set/getFirstDayOfWeek failed');
		}

		for($i = 0; $i < DateDefinitions::FIELD_COUNT; ++$i) {
			$this->assertEquals($cal->getMinimum($i), $cal->getGreatestMinimum($i), 'getMinimum doesn\'t match getGreatestMinimum for field ' . $i);
			$this->assertFalse($cal->getLeastMaximum($i) > $cal->getMaximum($i), 'getLeastMaximum larger than getMaximum for field ' . $i);
			$this->assertFalse($cal->getMinimum($i) >= $cal->getMaximum($i), 'getMinimum not less than getMaximum for field ' . $i);
		}

		$cal->setTimeZone($this->tm->getDefaultTimeZone());
		$cal->clear();
		$cal->set2(1984, 5, 24);
		$this->assertEquals($this->date(84, 5, 24), $cal->getTime(), 'CalendarCalendar::set(3 args) failed');

		$cal->clear();
		$cal->set3(1985, 3, 2, 11, 49);
		$this->assertEquals($this->date(85, 3, 2, 11, 49), $cal->getTime(), 'Calendar::set(5 args) failed');

		$cal->clear();
		$cal->set4(1995, 9, 12, 1, 39, 55);
		$this->assertEquals($this->date(95, 9, 12, 1, 39, 55), $cal->getTime(), 'Calendar::set(6 args) failed');

		$cal->getTime();

		for($i = 0; $i < DateDefinitions::FIELD_COUNT; ++$i) {
			switch($i) {
				case DateDefinitions::YEAR: case DateDefinitions::MONTH: case DateDefinitions::DATE:
				case DateDefinitions::HOUR_OF_DAY: case DateDefinitions::MINUTE: case DateDefinitions::SECOND:
				case DateDefinitions::EXTENDED_YEAR:
					$this->assertTrue($cal->_isSet($i), 'Calendar::isSet F, should be T ' . $this->fieldName($i));
					break;
				default:
					$this->assertFalse($cal->_isSet($i), 'Calendar::isSet = T, should be F  ' . $this->fieldName($i));
			}
			$cal->clear($i);
			$this->assertFalse($cal->_isSet($i), 'Calendar::clear/isSet failed ' . $this->fieldName($i));
		}

		return;
		// TODO: enable again
		// TODO: there is no api for this currently
		/*
		$cal = Calendar::createInstance(AgaviTimeZone::createDefault(), Locale::getEnglish());

		$cal = Calendar::createInstance($zone, Locale::getEnglish());

		$gc = $this->tm->createCalendar($zone);

		$gc = $this->tm->createCalendar(Locale::getEnglish());

		$gc = $this->tm->createCalendar(Locale::getEnglish());

		$gc = new GregorianCalendar($zone, Locale::getEnglish());

		$gc = $this->tm->createCalendar($zone);

		$gc = new GregorianCalendar(1998, 10, 14, 21, 43);
		$this->assertEquals($this->date(98, 10, 14, 21, 43), $gc->getTime());

		$gc = new GregorianCalendar(1998, 10, 14, 21, 43, 55);
		$this->assertEquals($this->date(98, 10, 14, 21, 43, 55), $gc->getTime());

		$gc2 = new GregorianCalendar(Locale::getEnglish());

		$gc2 = clone $gc;
		$this->assertFalse($gc2->__is_not_equal($gc) || !($gc2->__is_equal($gc)), 'AgaviGregorianCalendar assignment/operator==/operator!= failed');
		*/
	}

	/**
	 * This test confirms the correct behavior of add when incrementing
	 * through subsequent days.
	 */
	public function testRog()
	{
		$gc = $this->tm->createCalendar();
		
		$year = 1997;
		$month = DateDefinitions::APRIL;
		$date = 1;
		$gc->set($year, $month, $date);
		$gc->set(DateDefinitions::HOUR_OF_DAY, 23);
		$gc->set(DateDefinitions::MINUTE, 0);
		$gc->set(DateDefinitions::SECOND, 0);
		$gc->set(DateDefinitions::MILLISECOND, 0);
		for($i = 0; $i < 9; ++$i, $gc->add(DateDefinitions::DATE, 1)) {
			$this->assertEquals($year, $gc->get(DateDefinitions::YEAR));
			$this->assertEquals($month, $gc->get(DateDefinitions::MONTH));
			$this->assertEquals($date + $i, (int)$gc->get(DateDefinitions::DATE));
		}
	}


	/**
	 * Test the handling of the day of the week, checking for correctness and
	 * for correct minimum and maximum values.
	 */
	public function testDOW943()
	{
		$this->dowTest(false);
		$this->dowTest(true);
	}

	public function dowTest($lenient)
	{
		$cal = $this->tm->createCalendar();
		$cal->set(1997, DateDefinitions::AUGUST, 12);
		$cal->getTime();
		$cal->setLenient($lenient);
		
		$dow = (int)$cal->get(DateDefinitions::DAY_OF_WEEK);

		$cal->set(1996, DateDefinitions::DECEMBER, 1);
		// TODO: check why phpunit assertEquals doesn't think 1 and 1.0 are equal :s
		$dow = (int)$cal->get(DateDefinitions::DAY_OF_WEEK);

		$min = $cal->getMinimum(DateDefinitions::DAY_OF_WEEK);
		$max = $cal->getMaximum(DateDefinitions::DAY_OF_WEEK);
		$this->assertFalse($dow < $min || $dow > $max, 'Day of week ' . $dow . ' out of range');
		$this->assertEquals(DateDefinitions::SUNDAY, $dow, 'Day of week should be SUNDAY['.DateDefinitions::SUNDAY.'] not ' . $dow);
		$this->assertFalse($min != DateDefinitions::SUNDAY || $max != DateDefinitions::SATURDAY, 'Min/max bad');
	}







// -------------------------------------

	/** 
	 * Confirm that cloned Calendar objects do not inadvertently share substructures.
	 */
	public function testClonesUnique908()
	{
		$c = $this->tm->createCalendar();
		$d = clone $c;
		$c->set(DateDefinitions::MILLISECOND, 123);
		$d->set(DateDefinitions::MILLISECOND, 456);
		$this->assertFalse($c->get(DateDefinitions::MILLISECOND) != 123 || $d->get(DateDefinitions::MILLISECOND) != 456, 'Clones share fields');
	}

// -------------------------------------

	/**
	 * Confirm that the Gregorian cutoff value works as advertised.
	 */
	public function testGregorianChange768()
	{
		$c = $this->tm->createCalendar();
		$b = $c->isLeapYear(1800);
		$this->assertFalse($b);

		$c->setGregorianChange($this->date(0, 0, 1));
		$b = $c->isLeapYear(1800);
		$this->assertTrue($b);
	}

// -------------------------------------

	/**
	 * Confirm the functioning of the field disambiguation algorithm.
	 */
	public function testDisambiguation765()
	{
	// TODO: check how to do this properly ...
		$c = $this->tm->createCalendar($this->tm->getLocale('en_US'));
		$c->setLenient(false);
		$c->clear();
		$c->set(DateDefinitions::YEAR, 1997);
		$c->set(DateDefinitions::MONTH, DateDefinitions::JUNE);
		$c->set(DateDefinitions::DATE, 3);
		$this->verify765("1997 third day of June = ", $c, 1997, DateDefinitions::JUNE, 3);
		$c->clear();
		$c->set(DateDefinitions::YEAR, 1997);
		$c->set(DateDefinitions::DAY_OF_WEEK, DateDefinitions::TUESDAY);
		$c->set(DateDefinitions::MONTH, DateDefinitions::JUNE);
		$c->set(DateDefinitions::DAY_OF_WEEK_IN_MONTH, 1);
		$this->verify765("1997 first Tuesday in June = ", $c, 1997, DateDefinitions::JUNE, 3);
		$c->clear();
		$c->set(DateDefinitions::YEAR, 1997);
		$c->set(DateDefinitions::DAY_OF_WEEK, DateDefinitions::TUESDAY);
		$c->set(DateDefinitions::MONTH, DateDefinitions::JUNE);
		$c->set(DateDefinitions::DAY_OF_WEEK_IN_MONTH, - 1);
		$this->verify765("1997 last Tuesday in June = ", $c, 1997, DateDefinitions::JUNE, 24);

		$exceptionThrown = false;
		try {
				$c->clear();
				$c->set(DateDefinitions::YEAR, 1997);
				$c->set(DateDefinitions::DAY_OF_WEEK, DateDefinitions::TUESDAY);
				$c->set(DateDefinitions::MONTH, DateDefinitions::JUNE);
				$c->set(DateDefinitions::DAY_OF_WEEK_IN_MONTH, 0);
				$c->getTime();
		}
		catch(\InvalidArgumentException $ex /*IllegalArgumentException ex*/) {
			$exceptionThrown = true;
		}
		$this->assertTrue($exceptionThrown);
		//$this->verify765("1997 zero-th Tuesday in June = ");

		$c->clear();
		$c->set(DateDefinitions::YEAR, 1997);
		$c->set(DateDefinitions::DAY_OF_WEEK, DateDefinitions::TUESDAY);
		$c->set(DateDefinitions::MONTH, DateDefinitions::JUNE);
		$c->set(DateDefinitions::WEEK_OF_MONTH, 1);
		$this->verify765("1997 Tuesday in week 1 of June = ", $c, 1997, DateDefinitions::JUNE, 3);

		$c->clear();
		$c->set(DateDefinitions::YEAR, 1997);
		$c->set(DateDefinitions::DAY_OF_WEEK, DateDefinitions::TUESDAY);
		$c->set(DateDefinitions::MONTH, DateDefinitions::JUNE);
		$c->set(DateDefinitions::WEEK_OF_MONTH, 5);
		$this->verify765("1997 Tuesday in week 5 of June = ", $c, 1997, DateDefinitions::JULY, 1);

		$c->clear();
		$c->set(DateDefinitions::YEAR, 1997);
		$c->set(DateDefinitions::DAY_OF_WEEK, DateDefinitions::TUESDAY);
		$c->set(DateDefinitions::MONTH, DateDefinitions::JUNE);
		$c->set(DateDefinitions::WEEK_OF_MONTH, 0);
		$this->verify765("1997 Tuesday in week 0 of June = ", $c, 1997, DateDefinitions::MAY, 27);

		/* Note: The following test used to expect YEAR 1997, WOY 1 to
		 * resolve to a date in Dec 1996; that is, to behave as if
		 * YEAR_WOY were 1997.  With the addition of a new explicit
		 * YEAR_WOY field, YEAR_WOY must itself be set if that is what is
		 * desired.  Using YEAR in combination with WOY is ambiguous, and
		 * results in the first WOY/DOW day of the year satisfying the
		 * given fields (there may be up to two such days). In this case,
		 * it propertly resolves to Tue Dec 30 1997, which has a WOY value
		 * of 1 (for YEAR_WOY 1998) and a DOW of Tuesday, and falls in the
		 * _calendar_ year 1997, as specified. - aliu */
		$c->clear();
		$c->set(DateDefinitions::YEAR_WOY, 1997); // aliu
		$c->set(DateDefinitions::DAY_OF_WEEK, DateDefinitions::TUESDAY);
		$c->set(DateDefinitions::WEEK_OF_YEAR, 1);
		$this->verify765("1997 Tuesday in week 1 of yearWOY = ", $c, 1996, DateDefinitions::DECEMBER, 31);

		$c->clear(); // - add test for YEAR
		$c->set(DateDefinitions::YEAR, 1997);
		$c->set(DateDefinitions::DAY_OF_WEEK, DateDefinitions::TUESDAY);
		$c->set(DateDefinitions::WEEK_OF_YEAR, 1);
		$this->verify765("1997 Tuesday in week 1 of year = ", $c, 1997, DateDefinitions::DECEMBER, 30);

		$c->clear();
		$c->set(DateDefinitions::YEAR, 1997);
		$c->set(DateDefinitions::DAY_OF_WEEK, DateDefinitions::TUESDAY);
		$c->set(DateDefinitions::WEEK_OF_YEAR, 10);
		$this->verify765("1997 Tuesday in week 10 of year = ", $c, 1997, DateDefinitions::MARCH, 4);
	}

// -------------------------------------
 
	protected function verify765($msg, $c, $year, $month, $day)
	{
		$y = $c->get(DateDefinitions::YEAR);
		$m = $c->get(DateDefinitions::MONTH);
		$d = $c->get(DateDefinitions::DATE);

		$this->assertEquals($year, $y, $msg);
		$this->assertEquals($month, $m, $msg);
		$this->assertEquals($day, $d, $msg);
	}







	/**
	 * Confirm that the offset between local time and GMT behaves as expected.
	 */
	public function testGMTvsLocal4064654()
	{
		$this->myTest4064654(1997, 1, 1, 12, 0, 0);
		$this->myTest4064654(1997, 4, 16, 18, 30, 0);
	}

	protected function myTest4064654($yr, $mo, $dt, $hr, $mn, $sc)
	{
		$gmtcal = $this->tm->createCalendar($this->tm->createTimeZone("Africa/Casablanca"));
		$gmtcal->set($yr, $mo - 1, $dt, $hr, $mn, $sc);
		$gmtcal->set(DateDefinitions::MILLISECOND, 0);
		$date = $gmtcal->getTime();
		$cal = $this->tm->createCalendar();
		$cal->setTime($date);

		$offset = $cal->getTimeZone()->getOffset($cal->get(DateDefinitions::ERA),
																							$cal->get(DateDefinitions::YEAR),
																							$cal->get(DateDefinitions::MONTH),
																							$cal->get(DateDefinitions::DATE),
																							$cal->get(DateDefinitions::DAY_OF_WEEK),
																							$cal->get(DateDefinitions::MILLISECOND));

		$utc = (($cal->get(DateDefinitions::HOUR_OF_DAY) * 60 +
								$cal->get(DateDefinitions::MINUTE)) * 60 +
							$cal->get(DateDefinitions::SECOND)) * 1000 +
						$cal->get(DateDefinitions::MILLISECOND) - $offset;
		$expected = (($hr * 60 + $mn) * 60 + $sc) * 1000;
		$this->assertEquals((float) $expected, (float) $utc, 'Discrepancy of ' . ($utc - $expected) . ' millis = ' . (($utc - $expected) / 1000 / 60 / 60.0) . ' hr');
	}

// -------------------------------------

	/**
	 * The operations of adding and setting should not exhibit pathological
	 * dependence on the order of operations.  This test checks for this.
	 */
	public function testAddSetOrder621()
	{
		$d = $this->date(97, 4, 14, 13, 23, 45);
		$cal = $this->tm->createCalendar();
		$cal->setTime($d);
		$cal->add(DateDefinitions::DATE, -5);
		$cal->set(DateDefinitions::HOUR_OF_DAY, 0);
		$cal->set(DateDefinitions::MINUTE, 0);
		$cal->set(DateDefinitions::SECOND, 0);
		$s1 = $this->dateToString($cal->getTime());

		$cal = $this->tm->createCalendar();
		$cal->setTime($d);
		$cal->set(DateDefinitions::HOUR_OF_DAY, 0);
		$cal->set(DateDefinitions::MINUTE, 0);
		$cal->set(DateDefinitions::SECOND, 0);
		$cal->add(DateDefinitions::DATE, -5);

		$s2 = $this->dateToString($cal->getTime());
		$this->assertEquals($s1, $s2);
	}
 
// -------------------------------------
 
/**
 * Confirm that adding to various fields works.
 */
	public function testAdd520()
	{
		$y = 1997;
		$m = DateDefinitions::FEBRUARY;
		$d = 1;
		$temp = new GregorianCalendar($this->tm, $y, $m, $d);
		$this->check520($temp, $y, $m, $d);
		$temp->add(DateDefinitions::YEAR, 1);
		++$y;
		$this->check520($temp, $y, $m, $d);
		$temp->add(DateDefinitions::MONTH, 1);
		++$m;
		$this->check520($temp, $y, $m, $d);
		$temp->add(DateDefinitions::DATE, 1);
		++$d;
		$this->check520($temp, $y, $m, $d);
		$temp->add(DateDefinitions::DATE, 2);
		$d += 2;
		$this->check520($temp, $y, $m, $d);
		$temp->add(DateDefinitions::DATE, 28);
		$d = 1;
		++$m;
		$this->check520($temp, $y, $m, $d);
	}
 
// -------------------------------------
 
	/**
	 * Execute adding and rolling in GregorianCalendar extensively,
	 */
	public function testAddRollExtensive()
	{
		$maxlimit = 40;
		$y = 1997;
		$m = DateDefinitions::FEBRUARY;
		$d = 1;
		$hr = 1;
		$min = 1;
		$sec = 0;
		$ms = 0;

		$temp = new GregorianCalendar($this->tm, $y, $m, $d);

		$temp->set(DateDefinitions::HOUR, $hr);
		$temp->set(DateDefinitions::MINUTE, $min);
		$temp->set(DateDefinitions::SECOND, $sec);
		$temp->set(DateDefinitions::MILLISECOND, $ms);

		$e = DateDefinitions::YEAR;
		while($e < DateDefinitions::FIELD_COUNT) {
			$limit = $maxlimit;
			for($i = 0; $i < $limit; ++$i) {
				try {
					$temp->add($e, 1);
				// TODO: specify exact exception here
				} catch(\Exception $ex) {
					$limit = $i;
				}
			}
			for($i = 0; $i < $limit; ++$i) {
				$temp->add($e, -1);
			}
			$this->check520($temp, $y, $m, $d, $hr, $min, $sec, $ms, $e);

			++$e;
		}

		$e = DateDefinitions::YEAR;
		while($e < DateDefinitions::FIELD_COUNT) {
			$limit = $maxlimit;
			for($i = 0; $i < $limit; ++$i) {
				try {
					$temp->roll($e, 1);
				// TODO: specify exact exception here
				} catch(\Exception $ex) {
					$limit = $i;
				}
			}
			for($i = 0; $i < $limit; ++$i) {
					$temp->roll($e, -1);
			}
			$this->check520($temp, $y, $m, $d, $hr, $min, $sec, $ms, $e);

			++$e;
		}

	}
 
// -------------------------------------
	protected function check520($c, $y, $m, $d, $hr = -1, $min = -1, $sec = -1, $ms = -1, $field = -1)
	{
		if($hr == -1) {
			$this->assertEquals($y, $c->get(DateDefinitions::YEAR));
			$this->assertEquals($m, $c->get(DateDefinitions::MONTH));
			$this->assertEquals($d, $c->get(DateDefinitions::DATE));

			return;
		}


		$this->assertEquals($y, $c->get(DateDefinitions::YEAR));
		$this->assertEquals($m, $c->get(DateDefinitions::MONTH));
		$this->assertEquals($d, $c->get(DateDefinitions::DATE));
		$this->assertEquals($hr, $c->get(DateDefinitions::HOUR));
		$this->assertEquals($min, $c->get(DateDefinitions::MINUTE));
		$this->assertEquals($sec, $c->get(DateDefinitions::SECOND));
		$this->assertEquals($ms, $c->get(DateDefinitions::MILLISECOND));
	}

// -------------------------------------

	/**
	 * Verify that the seconds of a Calendar can be zeroed out through the
	 * expected sequence of operations.
	 */ 
	public function testSecondsZero121()
	{
		$cal = $this->tm->createCalendar();
		$cal->setTime(Calendar::getNow());
		$cal->set(DateDefinitions::SECOND, 0);
		$d = $cal->getTime();

		$s = $this->dateToString($d);
		$this->assertTrue(strpos($s, ':00 ') !== false, 'Expected to see :00 in ' . $s);
	}

// -------------------------------------

	/**
	 * Verify that a specific sequence of adding and setting works as expected;
	 * it should not vary depending on when and whether the get method is
	 * called.
	 */
	public function testAddSetGet0610()
	{
		$EXPECTED_0610 = "1993/0/5";
		{
				$calendar = $this->tm->createCalendar();
				$calendar->set(1993, DateDefinitions::JANUARY, 4);
				$calendar->add(DateDefinitions::DATE, 1);
				$v = $this->value($calendar);
				$this->assertEquals($EXPECTED_0610, $v);
		}
		{
				$calendar = new GregorianCalendar($this->tm, 1993, DateDefinitions::JANUARY, 4);
				$calendar->add(DateDefinitions::DATE, 1);
				$v = $this->value($calendar);
				$this->assertEquals($EXPECTED_0610, $v);
		}
		{
				$calendar = new GregorianCalendar($this->tm, 1993, DateDefinitions::JANUARY, 4);
				$calendar->getTime();
				$calendar->add(DateDefinitions::DATE, 1);
				$v = $this->value($calendar);
				$this->assertEquals($EXPECTED_0610, $v);
		}
	}

// -------------------------------------

	/**
	 * @param $cal Calendar
	 * @return string
	 */
	protected function value($cal)
	{
		return $cal->get(DateDefinitions::YEAR) . '/' . $cal->get(DateDefinitions::MONTH) .  '/' . $cal->get(DateDefinitions::DATE);
	}

// -------------------------------------

	/**
	 * Verify that various fields on a known date are set correctly.
	 */
	public function testFields060()
	{
		$year = 1997;
		$month = DateDefinitions::OCTOBER;
		$dDate = 22;
		$calendar = new GregorianCalendar($this->tm, $year, $month, $dDate);
		$expectedFields = array(
			DateDefinitions::YEAR => 1997,
			DateDefinitions::MONTH => DateDefinitions::OCTOBER,
			DateDefinitions::DATE => 22,
			DateDefinitions::DAY_OF_WEEK => DateDefinitions::WEDNESDAY,
			DateDefinitions::DAY_OF_WEEK_IN_MONTH => 4,
			DateDefinitions::DAY_OF_YEAR => 295,
		);
		foreach($expectedFields as $field => $value) {
			$fieldValue = $calendar->get($field);
			$this->assertEquals($value, (int) $fieldValue);
		}
	}

// -------------------------------------
 
	/**
	 * Verify that various fields on a known date are set correctly.  In this
	 * case, the start of the epoch (January 1 1970).
	 */
	public function testEpochStartFields()
	{
		$EPOCH_FIELDS = array(1, 1970, 0, 1, 1, 1, 1, 5, 1, 0, 0, 0, 0, 0, 0, - 28800000, 0);
		$z = $this->tm->getDefaultTimeZone();
		$c = $this->tm->createCalendar();
		$d = - $z->getRawOffset();
		$gc = $this->tm->createCalendar();
		$gc->setTimeZone($z);
		$gc->setTime($d);
		$idt = $gc->inDaylightTime();
		if($idt) {
			//logln("Warning: Skipping test because " + dateToString(d, str) + " is in DST.");
		} else {
			$c->setTime($d);
			for($i = 0; $i < DateDefinitions::ZONE_OFFSET; ++$i) {
				$this->assertEquals($EPOCH_FIELDS[$i], (int) $c->get($i));
				$this->assertEquals($z->getRawOffset(), $c->get(DateDefinitions::ZONE_OFFSET));
				$this->assertEquals(0, (int) $c->get(DateDefinitions::DST_OFFSET));
			}
		}
	}

// -------------------------------------
 
	/**
	 * Test that the days of the week progress properly when add is called repeatedly
	 * for increments of 24 days.
	 */
	public function testDOWProgression()
	{
		$cal = new GregorianCalendar($this->tm, 1972, DateDefinitions::OCTOBER, 26);
		$this->marchByDelta($cal, 24);
	}

// -------------------------------------

	public function testDOW_LOCALandYEAR_WOY()
	{
		// TODO: enable this test;
		return;

		/* Note: I've commented out the loop_addroll tests for YEAR and
		 * YEAR_WOY below because these two fields should NOT behave
		 * identically when adding.  YEAR should keep the month/dom
		 * invariant.  YEAR_WOY should keep the woy/dow invariant.  I've
		 * added a new test that checks for this in place of the old call
		 * to loop_addroll. - aliu */
		$times = 20;
		$cal = $this->tm->createCalendar($this->tm->getLocale('de_DE'));
		$sdf = new DateFormat("YYYY'-W'ww-ee", Locale::getGermany());
		// ICU no longer use localized date-time pattern characters by default (ticket#5597)
		/*
		$sdf->applyLocalizedPattern("JJJJ'-W'ww-ee");
		*/
		$cal->clear();
		$cal->set(1997, DateDefinitions::DECEMBER, 25);
		$this->doYEAR_WOYLoop($cal, $sdf, $times);
		//loop_addroll(cal, /*sdf,*/ times, UCAL_YEAR_WOY, UCAL_YEAR,  status);
		$this->yearAddTest($cal); // aliu
		$this->loop_addroll($cal, /*sdf,*/ $times, DateDefinitions::DOW_LOCAL, DateDefinitions::DAY_OF_WEEK);

		$cal->clear();
		$cal->set(1998, DateDefinitions::DECEMBER, 25);
		$this->doYEAR_WOYLoop($cal, $sdf, $times);
		//loop_addroll(cal, /*sdf,*/ times, UCAL_YEAR_WOY, UCAL_YEAR,  status);
		$this->yearAddTest($cal); // aliu
		$this->loop_addroll($cal, /*sdf,*/ $times, DateDefinitions::DOW_LOCAL, DateDefinitions::DAY_OF_WEEK);

		$cal->clear();
		$cal->set(1582, DateDefinitions::OCTOBER, 1);
		$this->doYEAR_WOYLoop($cal, $sdf, $times);
		//loop_addroll(cal, /*sdf,*/ times, Calendar::YEAR_WOY, Calendar::YEAR,  status);
		$this->yearAddTest($cal); // aliu
		$this->loop_addroll($cal, /*sdf,*/ $times, DateDefinitions::DOW_LOCAL, DateDefinitions::DAY_OF_WEEK);
	}

	/**
	 * Confirm that adding a YEAR and adding a YEAR_WOY work properly for
	 * the given Calendar at its current setting.
	 *
	 * @param $cal Calendar
	 */
	protected function yearAddTest(&$cal)
	{
		/**
		 * When adding the YEAR, the month and day should remain constant.
		 * When adding the YEAR_WOY, the WOY and DOW should remain constant. - aliu
		 * Examples:
		 *  Wed Jan 14 1998 / 1998-W03-03 Add(YEAR_WOY, 1) -> Wed Jan 20 1999 / 1999-W03-03
		 *                                Add(YEAR, 1)     -> Thu Jan 14 1999 / 1999-W02-04
		 *  Thu Jan 14 1999 / 1999-W02-04 Add(YEAR_WOY, 1) -> Thu Jan 13 2000 / 2000-W02-04
		 *                                Add(YEAR, 1)     -> Fri Jan 14 2000 / 2000-W02-05
		 *  Sun Oct 31 1582 / 1582-W42-07 Add(YEAR_WOY, 1) -> Sun Oct 23 1583 / 1583-W42-07
		 *                                Add(YEAR, 1)     -> Mon Oct 31 1583 / 1583-W44-01
		 */
		$y   = $cal->get(DateDefinitions::YEAR);
		$mon = $cal->get(DateDefinitions::MONTH);
		$day = $cal->get(DateDefinitions::DATE);
		$ywy = $cal->get(DateDefinitions::YEAR_WOY);
		$woy = $cal->get(DateDefinitions::WEEK_OF_YEAR);
		$dow = $cal->get(DateDefinitions::DOW_LOCAL);
		$t = $cal->getTime();

		$cal->add(DateDefinitions::YEAR, 1);
		$y2   = $cal->get(DateDefinitions::YEAR);
		$mon2 = $cal->get(DateDefinitions::MONTH);
		$day2 = $cal->get(DateDefinitions::DATE);
		$this->assertEquals($y + 1, $y2);
		$this->assertEquals($mon, $mon2);
		$this->assertEquals($day, $day2);

		$cal->setTime($t);
		$cal->add(DateDefinitions::YEAR_WOY, 1);
		$ywy2 = $cal->get(DateDefinitions::YEAR_WOY);
		$woy2 = $cal->get(DateDefinitions::WEEK_OF_YEAR);
		$dow2 = $cal->get(DateDefinitions::DOW_LOCAL);
		$this->assertEquals($ywy + 1, $ywy2);
		$this->assertEquals($woy, $woy2);
		$this->assertEquals($dow, $dow2);
	}

// -------------------------------------

	/**
	 * @param $cal Calendar
	 * @param $times
	 * @param $field
	 * @param $field2
	 */
	protected function loop_addroll($cal, $times, $field, $field2)
	{
		for($i = 0; $i < $times; ++$i) {
			$calclone = clone $cal;
			$start = $cal->getTime();
			$cal->add($field, 1);
			$calclone->add($field2, 1);
			$this->assertEquals($cal->getTime(), $calclone->getTime());
		}

		for($i = 0; $i < $times; ++$i) {
			$calclone = clone $cal;
			$cal->roll($field, 1);
			$calclone->roll($field2, 1);
			$this->assertEquals($cal->getTime(), $calclone->getTime());
		}
	}

// -------------------------------------

	/**
	 * @param $cal Calendar
	 * @param $sdf
	 * @param $times
	 */
	protected function doYEAR_WOYLoop($cal, $sdf, $times)
	{
		for($i = 0; $i < $times; ++$i) {
			$tstres = clone($cal);
			$tstres->clear();
			$tstres->set(DateDefinitions::YEAR_WOY, $cal->get(DateDefinitions::YEAR_WOY));
			$tstres->set(DateDefinitions::WEEK_OF_YEAR, $cal->get(DateDefinitions::WEEK_OF_YEAR));
			$tstres->set(DateDefinitions::DOW_LOCAL, $cal->get(DateDefinitions::DOW_LOCAL));
			$this->assertEquals($cal->get(DateDefinitions::YEAR), $tstres->get(DateDefinitions::YEAR));
			$this->assertEquals($cal->get(DateDefinitions::DAY_OF_YEAR), $tstres->get(DateDefinitions::DAY_OF_YEAR));

			$cal->add(DateDefinitions::DATE, 1, errorCode);
		}
	}

// -------------------------------------

	/**
	 * @param $cal Calendar
	 * @param $delta
	 */
	protected function marchByDelta($cal, $delta)
	{
		$cur = clone $cal;
		$initialDOW = $cur->get(DateDefinitions::DAY_OF_WEEK);
		$DOW = 0;
		$newDOW = $initialDOW;
		do {
			$DOW = $newDOW;
			$cur->add(DateDefinitions::DAY_OF_WEEK, $delta);
			$newDOW = $cur->get(DateDefinitions::DAY_OF_WEEK);
			$expectedDOW = 1 + ($DOW + $delta - 1) % 7;
			$this->assertEquals($expectedDOW, (int)$newDOW);
		} while ($newDOW != $initialDOW);
	}

	public function testWOY()
	{
		/*
			FDW = Mon, MDFW = 4:
				 Sun Dec 26 1999, WOY 51
				 Mon Dec 27 1999, WOY 52
				 Tue Dec 28 1999, WOY 52
				 Wed Dec 29 1999, WOY 52
				 Thu Dec 30 1999, WOY 52
				 Fri Dec 31 1999, WOY 52
				 Sat Jan 01 2000, WOY 52 ***
				 Sun Jan 02 2000, WOY 52 ***
				 Mon Jan 03 2000, WOY 1
				 Tue Jan 04 2000, WOY 1
				 Wed Jan 05 2000, WOY 1
				 Thu Jan 06 2000, WOY 1
				 Fri Jan 07 2000, WOY 1
				 Sat Jan 08 2000, WOY 1
				 Sun Jan 09 2000, WOY 1
				 Mon Jan 10 2000, WOY 2

			FDW = Mon, MDFW = 2:
				 Sun Dec 26 1999, WOY 52
				 Mon Dec 27 1999, WOY 1  ***
				 Tue Dec 28 1999, WOY 1  ***
				 Wed Dec 29 1999, WOY 1  ***
				 Thu Dec 30 1999, WOY 1  ***
				 Fri Dec 31 1999, WOY 1  ***
				 Sat Jan 01 2000, WOY 1
				 Sun Jan 02 2000, WOY 1
				 Mon Jan 03 2000, WOY 2
				 Tue Jan 04 2000, WOY 2
				 Wed Jan 05 2000, WOY 2
				 Thu Jan 06 2000, WOY 2
				 Fri Jan 07 2000, WOY 2
				 Sat Jan 08 2000, WOY 2
				 Sun Jan 09 2000, WOY 2
				 Mon Jan 10 2000, WOY 3
		*/
 
		$cal = $this->tm->createCalendar();

		$fdw = 0;

		//for (int8_t pass=2; pass<=2; ++pass) {
		for($pass = 1; $pass <= 2; ++$pass) {
			switch($pass) {
				case 1:
					$fdw = DateDefinitions::MONDAY;
					$cal->setFirstDayOfWeek($fdw);
					$cal->setMinimalDaysInFirstWeek(4);
					break;
				case 2:
					$fdw = DateDefinitions::MONDAY;
					$cal->setFirstDayOfWeek($fdw);
					$cal->setMinimalDaysInFirstWeek(2);
					break;
			}

			//for (i=2; i<=6; ++i) {
			for($i = 0; $i < 16; ++$i) {
				$cal->clear();
				$cal->set(1999, DateDefinitions::DECEMBER, 26 + $i);
				$t = $cal->getTime();
				$dow = $cal->get(DateDefinitions::DAY_OF_WEEK);
				$woy = $cal->get(DateDefinitions::WEEK_OF_YEAR);
				$year = $cal->get(DateDefinitions::YEAR);
				$mon = $cal->get(DateDefinitions::MONTH);
				$dowLocal = $dow - $fdw;
				if($dowLocal < 0) {
					$dowLocal += 7;
				}
				++$dowLocal;
				$yearWoy = $year;
				if($mon == DateDefinitions::JANUARY) {
					if($woy >= 52) {
						--$yearWoy;
					}
				} else {
					if($woy == 1) {
						++$yearWoy;
					}
				}

				// Basic fields->time check y/woy/dow
				// Since Y/WOY is ambiguous, we do a check of the fields,
				// not of the specific time.
				$cal->clear();
				$cal->set(DateDefinitions::YEAR, $year);
				$cal->set(DateDefinitions::WEEK_OF_YEAR, $woy);
				$cal->set(DateDefinitions::DAY_OF_WEEK, $dow);
				$t_y = $cal->get(DateDefinitions::YEAR);
				$t_woy = $cal->get(DateDefinitions::WEEK_OF_YEAR);
				$t_dow = $cal->get(DateDefinitions::DAY_OF_WEEK);
				$this->assertEquals($year, $t_y);
				$this->assertEquals($woy, $t_woy);
				$this->assertEquals($dow, $t_dow);

				// Basic fields->time check y/woy/dow_local
				// Since Y/WOY is ambiguous, we do a check of the fields,
				// not of the specific time.
				$cal->clear();
				$cal->set(DateDefinitions::YEAR, $year);
				$cal->set(DateDefinitions::WEEK_OF_YEAR, $woy);
				$cal->set(DateDefinitions::DOW_LOCAL, $dowLocal);
				$t_y = $cal->get(DateDefinitions::YEAR);
				$t_woy = $cal->get(DateDefinitions::WEEK_OF_YEAR);
				$t_dow = $cal->get(DateDefinitions::DOW_LOCAL);
				$this->assertEquals($year, $t_y);
				$this->assertEquals($woy, $t_woy);
				$this->assertEquals($dowLocal, $t_dow);

				// Basic fields->time check y_woy/woy/dow
				$cal->clear();
				$cal->set(DateDefinitions::YEAR_WOY, $yearWoy);
				$cal->set(DateDefinitions::WEEK_OF_YEAR, $woy);
				$cal->set(DateDefinitions::DAY_OF_WEEK, $dow);
				$t2 = $cal->getTime();
				$this->assertEquals($t, $t2);

				// Basic fields->time check y_woy/woy/dow_local
				$cal->clear();
				$cal->set(DateDefinitions::YEAR_WOY, $yearWoy);
				$cal->set(DateDefinitions::WEEK_OF_YEAR, $woy);
				$cal->set(DateDefinitions::DOW_LOCAL, $dowLocal);
				$t2 = $cal->getTime();
				$this->assertEquals($t, $t2);

				// Make sure DOW_LOCAL disambiguates over DOW
				$wrongDow = $dow - 3;
				if($wrongDow < 1) {
					$wrongDow += 7;
				}
				$cal->setTime($t);
				$cal->set(DateDefinitions::DAY_OF_WEEK, $wrongDow);
				$cal->set(DateDefinitions::DOW_LOCAL, $dowLocal);
				$t2 = $cal->getTime();
				$this->assertEquals($t, $t2);

				// Make sure DOW disambiguates over DOW_LOCAL
				$wrongDowLocal = $dowLocal - 3;
				if($wrongDowLocal < 1) {
					$wrongDowLocal += 7;
				}
				$cal->setTime($t);
				$cal->set(DateDefinitions::DOW_LOCAL, $wrongDowLocal);
				$cal->set(DateDefinitions::DAY_OF_WEEK, $dow);
				$t2 = $cal->getTime();
				$this->assertEquals($t, $t2);

				// Make sure YEAR_WOY disambiguates over YEAR
				$cal->setTime($t);
				$cal->set(DateDefinitions::YEAR, $year - 2);
				$cal->set(DateDefinitions::YEAR_WOY, $yearWoy);
				$t2 = $cal->getTime();
				$this->assertEquals($t, $t2);

				// Make sure YEAR disambiguates over YEAR_WOY
				$cal->setTime($t);
				$cal->set(DateDefinitions::YEAR_WOY, $yearWoy - 2);
				$cal->set(DateDefinitions::YEAR, $year);
				$t2 = $cal->getTime();
				$this->assertEquals($t, $t2);
			}
		}

		/*
			FDW = Mon, MDFW = 4:
				 Sun Dec 26 1999, WOY 51
				 Mon Dec 27 1999, WOY 52
				 Tue Dec 28 1999, WOY 52
				 Wed Dec 29 1999, WOY 52
				 Thu Dec 30 1999, WOY 52
				 Fri Dec 31 1999, WOY 52
				 Sat Jan 01 2000, WOY 52
				 Sun Jan 02 2000, WOY 52
		*/

		// Roll the DOW_LOCAL within week 52
		for($i = 27; $i <= 33; ++$i) {
			for($amount = -7; $amount <= 7; ++$amount) {
				$str = "roll(";
				$cal->set(1999, DateDefinitions::DECEMBER, $i);

				$cal->roll(DateDefinitions::DOW_LOCAL, $amount);

				$t = $cal->getTime();
				$newDom = $i + $amount;
				while($newDom < 27) {
					$newDom += 7;
				}
				while($newDom > 33) {
					$newDom -= 7;
				}
				$cal->set(1999, DateDefinitions::DECEMBER, $newDom);
				$t2 = $cal->getTime();

				$this->assertEquals($t, $t2);
			}
		}
	}

	public function testYWOY()
	{
		$cal = $this->tm->createCalendar();

		$cal->setFirstDayOfWeek(DateDefinitions::SUNDAY);
		$cal->setMinimalDaysInFirstWeek(1);

		$cal->clear();
		$cal->set(DateDefinitions::YEAR_WOY, 2004);
		$cal->set(DateDefinitions::WEEK_OF_YEAR, 1);
		$cal->set(DateDefinitions::DAY_OF_WEEK, DateDefinitions::MONDAY);

		$this->assertEquals(2003, $cal->get(DateDefinitions::YEAR));

		$cal->clear();
		$cal->set(DateDefinitions::YEAR_WOY, 2004);
		$cal->set(DateDefinitions::WEEK_OF_YEAR, 1);
		$cal->set(DateDefinitions::DAY_OF_WEEK, DateDefinitions::THURSDAY);

		$this->assertEquals(2004, $cal->get(DateDefinitions::YEAR));

		$cal->clear();
		$cal->set(DateDefinitions::YEAR_WOY, 2004);
		$cal->set(DateDefinitions::WEEK_OF_YEAR, 1);
		$cal->set(DateDefinitions::DAY_OF_WEEK, DateDefinitions::THURSDAY);
		$cal->set(DateDefinitions::DOW_LOCAL, 1);

		$this->assertEquals(2003, $cal->get(DateDefinitions::YEAR));

		$cal->setFirstDayOfWeek(DateDefinitions::MONDAY);
		$cal->setMinimalDaysInFirstWeek(4);
		$t = 946713600000.0;
		$cal->setTime($t);
		$cal->set(DateDefinitions::DAY_OF_WEEK, 4);
		$cal->set(DateDefinitions::DOW_LOCAL, 6);
		$this->assertEquals($t, $cal->getTime());
	}

	public function testJD()
	{
		$kEpochStartAsJulianDay = 2440588;
		$cal = $this->tm->createCalendar();
		$cal->setTimeZone(TimeZone::getGMT($this->tm));
		$cal->clear();
		$jd = $cal->get(DateDefinitions::JULIAN_DAY);
		$this->assertEquals($kEpochStartAsJulianDay, (int)$jd);
		
		$cal->setTime(Calendar::getNow());
		$cal->clear();
		$cal->set(DateDefinitions::JULIAN_DAY, $kEpochStartAsJulianDay);
		$epochTime = $cal->getTime();
		$this->assertEquals(0, (int)$epochTime);
	}

/*
// List of interesting locales
const char *CalendarTest::testLocaleID(int32_t i)
{
  switch(i) {
  case 0: return "he_IL@calendar=hebrew";
  case 1: return "en_US@calendar=hebrew";
  case 2: return "fr_FR@calendar=hebrew";
  case 3: return "fi_FI@calendar=hebrew";
  case 4: return "nl_NL@calendar=hebrew";
  case 5: return "hu_HU@calendar=hebrew";
  case 6: return "nl_BE@currency=MTL;calendar=islamic";
  case 7: return "th_TH_TRADITIONAL@calendar=gregorian";
  case 8: return "ar_JO@calendar=islamic-civil";
  case 9: return "fi_FI@calendar=islamic";
  case 10: return "fr_CH@calendar=islamic-civil";
  case 11: return "he_IL@calendar=islamic-civil";
  case 12: return "hu_HU@calendar=buddhist";
  case 13: return "hu_HU@calendar=islamic";
  case 14: return "en_US@calendar=japanese";
  default: return NULL;
  }
}

int32_t CalendarTest::testLocaleCount()
{
  static int32_t gLocaleCount = -1;
  if(gLocaleCount < 0) {
    int32_t i;
    for(i=0;testLocaleID(i) != NULL;i++) {
      ;
    }
    gLocaleCount = i;
  }
  return gLocaleCount;
}

static UDate doMinDateOfCalendar(Calendar* adopt, UBool &isGregorian, UErrorCode& status) {
  if(U_FAILURE(status)) return 0.0;
  
  adopt->clear();
  adopt->set(UCAL_EXTENDED_YEAR, adopt->getActualMinimum(UCAL_EXTENDED_YEAR));
  UDate ret = adopt->getTime(status);
  isGregorian = (adopt->getDynamicClassID() == GregorianCalendar::getStaticClassID());
  delete adopt;
  return ret;
}

UDate CalendarTest::minDateOfCalendar(const Locale& locale, UBool &isGregorian, UErrorCode& status) {
  if(U_FAILURE(status)) return 0.0;
  return doMinDateOfCalendar(Calendar::createInstance(locale), isGregorian);
}

UDate CalendarTest::minDateOfCalendar(const Calendar& cal, UBool &isGregorian, UErrorCode& status) {
  if(U_FAILURE(status)) return 0.0;
  return doMinDateOfCalendar(cal.clone(), isGregorian);
}
*/

}


?>