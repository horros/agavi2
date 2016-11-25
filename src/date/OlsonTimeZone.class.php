<?php
namespace Agavi\Date;
// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2005-2011 the Agavi Project.                                |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org/LICENSE.txt                   |
// |   vi: set noexpandtab:                                                    |
// |   Local Variables:                                                        |
// |   indent-tabs-mode: t                                                     |
// |   End:                                                                    |
// +---------------------------------------------------------------------------+
use Agavi\Exception\AgaviException;
use Agavi\Translation\TranslationManager;
use Agavi\Util\Toolkit;

/**
 * A time zone based on the Olson database. Olson time zones change behavior 
 * over time. The raw offset, rules, presence or absence of daylight savings 
 * time, and even the daylight savings amount can all vary.
 *
 * Ported from ICU:
 *  icu/trunk/source/i18n/olsontz.cpp         r19133
 *  icu/trunk/source/i18n/olsontz.h           r18762
 * 
 * @package    agavi
 * @subpackage date
 *
 * @author     Dominik del Bondio <ddb@bitxtender.com>
 * @author     The ICU Project
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.11.0
 *
 * @version    $Id$
 */
class OlsonTimeZone extends TimeZone
{
	/**
	 * The transitions
	 *
	 * @var        array
	 * @since      0.11.0
	 */
	protected $transitions;

	/**
	 * The types, 1..255
	 *
	 * @var        array
	 * @since      0.11.0
	 */
	protected $types;

	/**
	 * The last year for which the transitions data are to be used
	 * rather than the finalZone.  If there is no finalZone, then this
	 * is set to INT32_MAX.  NOTE: This corresponds to the year _before_
	 * the one indicated by finalMillis.
	 *
	 * @var        int
	 * @since      0.11.0
	 */
	protected $finalYear;

	/**
	 * The millis for the start of the first year for which finalZone
	 * is to be used, or DBL_MAX if finalZone is 0.  NOTE: This is
	 * 0:00 GMT Jan 1, <finalYear + 1> (not <finalMillis>).
	 *
	 * @var        float
	 * @since      0.11.0
	 */
	protected $finalMillis;

	/**
	 * A SimpleTimeZone that governs the behavior for years > finalYear.
	 * If and only if finalYear == INT32_MAX then finalZone == 0.
	 *
	 * @var        SimpleTimeZone
	 * @since      0.11.0
	 */
	protected $finalZone; // owned, may be NULL

	const MAX_INT = 2147483647;
	const MAX_DBL = Calendar::MAX_MILLIS;

	/**
	 * Constructor
	 *
	 * @see        AgaviOlsonTimeZone::constructor()
	 * @see        AgaviOlsonTimeZone::constructorOSA()
	 *
	 * @author     Dominik del Bondio <ddb@bitxtender.com>
	 * @author     The ICU Project
	 * @since      0.11.0
	 */
	public function __construct()
	{
		$arguments = func_get_args();
		if(count($arguments) == 1) {
			parent::__construct($arguments[0]);
			return;
		}
		$fName = Toolkit::overloadHelper(array(
			array('name' => 'constructorOSA',
						'parameters' => array('object', 'string', 'array')),
			),
			$arguments
		);
		call_user_func_array(array($this, $fName), $arguments);
	}

	/**
	 * Default constructor. Creates a time zone with an empty ID and
	 * a fixed GMT offset of zero.
	 *
	 * @author     Dominik del Bondio <ddb@bitxtender.com>
	 * @author     The ICU Project
	 * @since      0.11.0
	 */
	protected function constructor()
	{
		$this->finalYear = self::MAX_INT;
		$this->finalMillis = self::MAX_DBL;
		$this->finalZone = null;

		$this->constructEmpty();
	}

	/**
	 * Construct a GMT+0 zone with no transitions.  This is done when a
	 * constructor fails so the resultant object is well-behaved.
	 *
	 * @author     Dominik del Bondio <ddb@bitxtender.com>
	 * @author     The ICU Project
	 * @since      0.11.0
	 */
	protected function constructEmpty()
	{
		$this->transitionCount = 0;
		$this->transitions = array();
		// TODO: this should probably contain at least one item
		$this->types = array();
	}

	/**
	 * Construct with info from an array.
	 *
	 * @param      TranslationManager $tm The translation manager.
	 * @param      string $id The id.
	 * @param      array  $zoneInfo The zone info data.
	 *
	 * @author     Dominik del Bondio <ddb@bitxtender.com>
	 * @author     The ICU Project
	 * @since      0.11.0
	 */
	protected function constructorOSA(TranslationManager $tm, $id, array $zoneInfo)
	{
		parent::__construct($tm, $id);

		$this->finalYear = self::MAX_INT;
		$this->finalMillis = self::MAX_DBL;
		$this->finalZone = null;

		foreach($zoneInfo['rules'] as $rule) {
			$this->transitions[] = $rule;
		}

		$this->types = $zoneInfo['types'];

		if(!isset($zoneInfo['finalRule'])) {
			throw new AgaviException($id);
		}

		// Subtract one from the actual final year; we actually store final year - 1,
		// and compare using > rather than >=.  This allows us to use INT32_MAX as 
		// an exclusive upper limit for all years, including INT32_MAX.
		$rawOffset = $zoneInfo['finalRule']['offset'] * DateDefinitions::MILLIS_PER_SECOND;
		$this->finalYear = $zoneInfo['finalRule']['startYear'] - 1;
		// Also compute the millis for Jan 1, 0:00 GMT of the finalYear.  This reduces runtime computations.
		$this->finalMillis = CalendarGrego::fieldsToDay($zoneInfo['finalRule']['startYear'], 0, 1) * DateDefinitions::MILLIS_PER_DAY;

		if($zoneInfo['finalRule']['type'] == 'dynamic') {
			$fr = $zoneInfo['finalRule'];
			$this->finalZone = new SimpleTimeZone(
				$tm, $rawOffset, $id, 
				$fr['start']['month'], $fr['start']['date'], $fr['start']['day_of_week'], $fr['start']['time'], $fr['start']['type'],
				$fr['end']['month'], $fr['end']['date'], $fr['end']['day_of_week'], $fr['end']['time'], $fr['end']['type'],
				$fr['save'] * DateDefinitions::MILLIS_PER_SECOND
				);
		} else {
			$this->finalZone = new SimpleTimeZone($tm, $rawOffset, $id);
		}
	}

	/**
	 * Returns true if the two TimeZone objects are equal.
	 *
	 * @param      TimeZone $that The timezone to compare against.
	 * 
	 * @author     Dominik del Bondio <ddb@bitxtender.com>
	 * @author     The ICU Project
	 * @since      0.11.0
	 */
	function __is_equal(TimeZone $that)
	{
		// TODO: we need to compare finalyear and the transitions and finalzone
		return ($this === $that ||
						(get_class($this) == get_class($that) &&
							TimeZone::__is_equal($that) 
						));
	}

	/**
	 * TimeZone API.
	 *
	 * @see        TimeZone::getOffsetIIIIII()
	 * 
	 * @author     Dominik del Bondio <ddb@bitxtender.com>
	 * @author     The ICU Project
	 * @since      0.11.0
	 */
	protected function getOffsetIIIIII($era, $year, $month, $dom, $dow, $millis)
	{
		if($month < DateDefinitions::JANUARY || $month > DateDefinitions::DECEMBER) {
			throw new \InvalidArgumentException('Month out of range');
		} else {
			return $this->getOffsetIIIIIII($era, $year, $month, $dom, $dow, $millis, CalendarGrego::monthLength($year, $month));
		}
	}

	/**
	 * TimeZone API.
	 *
	 * @see        TimeZone::getOffsetIIIIIII()
	 * 
	 * @author     Dominik del Bondio <ddb@bitxtender.com>
	 * @author     The ICU Project
	 * @since      0.11.0
	 */
	protected function getOffsetIIIIIII($era, $year, $month, $dom, $dow, $millis, $monthLength)
	{
		if(($era != GregorianCalendar::AD && $era != GregorianCalendar::BC)
				|| $month < DateDefinitions::JANUARY
				|| $month > DateDefinitions::DECEMBER
				|| $dom < 1
				|| $dom > $monthLength
				|| $dow < DateDefinitions::SUNDAY
				|| $dow > DateDefinitions::SATURDAY
				|| $millis < 0
				|| $millis >= DateDefinitions::MILLIS_PER_DAY
				|| $monthLength < 28
				|| $monthLength > 31) {
			throw new \InvalidArgumentException('One of the supplied parameters is out of range');
		}

		if($era == GregorianCalendar::BC) {
			$year = -$year;
		}

		if($year > $this->finalYear) { // [sic] >, not >=; see above
			return $this->finalZone->getOffset($era, $year, $month, $dom, $dow, $millis, $monthLength);
		}

		// Compute local epoch seconds from input fields
		$time = CalendarGrego::fieldsToDay($year, $month, $dom) * DateDefinitions::SECONDS_PER_DAY + floor($millis / DateDefinitions::MILLIS_PER_SECOND);

		$transition = $this->findTransition($time, true);
		return ($this->types[$transition['type']]['dstOffset'] + $this->types[$transition['type']]['rawOffset']) * DateDefinitions::MILLIS_PER_SECOND;
	}

	/**
	 * TimeZone API.
	 *
	 * @see        TimeZone::getOffsetRef()
	 * 
	 * @author     Dominik del Bondio <ddb@bitxtender.com>
	 * @author     The ICU Project
	 * @since      0.11.0
	 */
	public function getOffsetRef($date, $local, &$rawoff, &$dstoff)
	{
		// The check against finalMillis will suffice most of the time, except
		// for the case in which finalMillis == DBL_MAX, date == DBL_MAX,
		// and finalZone == 0.  For this case we add "&& finalZone != 0".
		if($date >= $this->finalMillis && $this->finalZone !== null) {
			$millis = 0;
			$days = Toolkit::floorDivide($date, DateDefinitions::MILLIS_PER_DAY, $millis);

			$year = 0; $month = 0; $dom = 0; $dow = 0;

			CalendarGrego::dayToFields($days, $year, $month, $dom, $dow);

			$rawoff = $this->finalZone->getRawOffset();

			if(!$local) {
				// Adjust from GMT to local
				$date += $rawoff;
				$days2 = Toolkit::floorDivide($date, DateDefinitions::MILLIS_PER_DAY, $millis);
				if($days2 != $days) {
					CalendarGrego::dayToFields($days2, $year, $month, $dom, $dow);
				}
			}

			$dstoff = $this->finalZone->getOffset(GregorianCalendar::AD, $year, $month, $dom, $dow, $millis) - $rawoff;
			return;
		}

		$secs = floor($date / DateDefinitions::MILLIS_PER_SECOND);
		$transition = $this->findTransition($secs, $local);
		$rawoff = $this->types[$transition['type']]['rawOffset'] * DateDefinitions::MILLIS_PER_SECOND;
		$dstoff = $this->types[$transition['type']]['dstOffset'] * DateDefinitions::MILLIS_PER_SECOND;
	}

	/**
	 * TimeZone API.
	 *
	 * @see        TimeZone::setRawOffset()
	 * 
	 * @author     Dominik del Bondio <ddb@bitxtender.com>
	 * @author     The ICU Project
	 * @since      0.11.0
	 */
	public function setRawOffset($offsetMillis)
	{
		// We don't support this operation, since OlsonTimeZones are
		// immutable (except for the ID, which is in the base class).

		// Nothing to do!
	}

	/**
	 * TimeZone API.
	 *
	 * @see        TimeZone::getRawOffset()
	 * 
	 * @author     Dominik del Bondio <ddb@bitxtender.com>
	 * @author     The ICU Project
	 * @since      0.11.0
	 */
	public function getRawOffset()
	{
		$raw = 0;
		$dst = 0;
		$this->getOffsetRef(Calendar::getNow(), false, $raw, $dst);
		return $raw;
	}

	/**
	 * Find the smallest i (in 0..transitionCount-1) such that time >=
	 * transition(i), where transition(i) is either the GMT or the local
	 * transition time, as specified by `local'.
	 *
	 * @param      float $time epoch seconds, either GMT or local wall
	 * @param      bool  $local if TRUE, `time' is in local wall units, otherwise it
	 *                   is GMT
	 *
	 * @return     int   an index i, where 0 <= i < transitionCount, and
	 *                   transition(i) <= time < transition(i+1), or i == 0 if
	 *                   transitionCount == 0 or time < transition(0).
	 *
	 * @author     Dominik del Bondio <ddb@bitxtender.com>
	 * @author     The ICU Project
	 * @since      0.11.0
	 */
	protected function findTransition($time, $local)
	{
		$i = 0;

		if(count($this->transitions) > 0) {
			// Linear search from the end is the fastest approach, since
			// most lookups will happen at/near the end.
			for($i = count($this->transitions) - 1; $i > 0; --$i) {
				$transition = $this->transitions[$i];
				if($local) {
					$prevType = $this->transitions[$i - 1]['type'];
					$zoneOffsetPrev = $this->types[$prevType]['dstOffset'] + $this->types[$prevType]['rawOffset'];
					$currType = $transition['type'];
					$zoneOffsetCurr = $this->types[$currType]['dstOffset'] + $this->types[$currType]['rawOffset'];
					
					// use the lowest offset ( == standard time ). as per tzregts.cpp which says:

							/**
							 * @bug 4084933
							 * The expected behavior of TimeZone around the boundaries is:
							 * (Assume transition time of 2:00 AM)
							 *    day of onset 1:59 AM STD  = display name 1:59 AM ST
							 *                 2:00 AM STD  = display name 3:00 AM DT
							 *    day of end   0:59 AM STD  = display name 1:59 AM DT
							 *                 1:00 AM STD  = display name 1:00 AM ST
							 */
					if($zoneOffsetPrev < $zoneOffsetCurr) {
						$transition['time'] += $zoneOffsetPrev;
					} else {
						$transition['time'] += $zoneOffsetCurr;
					}
				}

				if($time >= $transition['time']) {
					break;
				}
			}
		}

		return $this->transitions[$i];
	}

	/**
	 * TimeZone API.
	 * 
	 * @see        TimeZone::useDaylightTime()
	 *
	 * @author     Dominik del Bondio <ddb@bitxtender.com>
	 * @author     The ICU Project
	 * @since      0.11.0
	 */
	public function useDaylightTime()
	{
		// If DST was observed in 1942 (for example) but has never been
		// observed from 1943 to the present, most clients will expect
		// this method to return FALSE.  This method determines whether
		// DST is in use in the current year (at any point in the year)
		// and returns TRUE if so.

		$days = floor(Calendar::getNow() / DateDefinitions::MILLIS_PER_DAY); // epoch days

		$year = 0; $month = 0; $dom = 0; $dow = 0;

		CalendarGrego::dayToFields($days, $year, $month, $dom, $dow);

		if($year > $this->finalYear) { // [sic] >, not >=; see above
			if($this->finalZone) {
				return $this->finalZone->useDaylightTime();
			} else {
				return true;
			}
		}

		// Find start of this year, and start of next year
		$start = (int) CalendarGrego::fieldsToDay($year, 0, 1) * DateDefinitions::SECONDS_PER_DAY;
		$limit = (int) CalendarGrego::fieldsToDay($year + 1, 0, 1) * DateDefinitions::SECONDS_PER_DAY;

		// Return TRUE if DST is observed at any time during the current year.
		for($i = 0, $transitionCount = count($this->transitions); $i < $transitionCount; ++$i) {
			if($this->transitions[$i]['time'] >= $limit) {
				break;
			}
			if(($this->transitions[$i]['time'] >= $start && $this->types[$this->transitions[$i]['type']]['dstOffset'] != 0) || ($this->transitions[$i]['time'] > $start && $i > 0 && $this->types[$this->transitions[$i-1]['type']]['dstOffset'] != 0)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * TimeZone API.
	 *
	 * @see        TimeZone::getDSTSavings()
	 * 
	 * @author     Dominik del Bondio <ddb@bitxtender.com>
	 * @author     The ICU Project
	 * @since      0.11.0
	 */
	public function getDSTSavings()
	{
		if($this->finalZone !== null) {
			return $this->finalZone->getDSTSavings();
		}
		return parent::getDSTSavings();
	}

	/**
	 * TimeZone API.
	 *
	 * @see        TimeZone::inDaylightTime()
	 * 
	 * @author     Dominik del Bondio <ddb@bitxtender.com>
	 * @author     The ICU Project
	 * @since      0.11.0
	 */
	public function inDaylightTime($date)
	{
		$raw = 0;
		$dst = 0;
		$this->getOffsetRef($date, false, $raw, $dst);
		return $dst != 0;
	}

}

?>