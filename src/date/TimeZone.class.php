<?php

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

namespace Agavi\Date;

use Agavi\Translation\Locale;
use Agavi\Translation\TranslationManager;
use Agavi\Util\Toolkit;

/**
 * Ported from ICU:
 *  icu/trunk/source/i18n/timezone.cpp        r22069
 *  icu/trunk/source/i18n/unicode/timezone.h  r18762
 *
 * Skipped methods:
 *  getTZDataVersion() - not supported [22063,22069]
 *
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
abstract class TimeZone
{
    /**
     * The translation manager instance.
     *
     * @var        TranslationManager
     */
    protected $translationManager = null;

    /**
     * The id of this time zone.
     *
     * @var        string
     */
    protected $id;

    /**
     * @var        string The "resolved" id. This means if the original id pointed
     *                    to a link timezone this will contain the id of the
     *                    timezone the link resolved to.
     */
    protected $resolvedId = null;

    /**
     * Returns the translation manager for this TimeZone.
     *
     * @return     TranslationManager The translation manager.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public function getTranslationManager()
    {
        return $this->translationManager;
    }

    /**
     * The GMT time zone has a raw offset of zero and does not use daylight
     * savings time. This is a commonly used time zone.
     *
     * @param      TranslationManager $tm The translation manager
     *
     * @return     TimeZone The GMT time zone.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @author     The ICU Project
     * @since      0.11.0
     */
    public static function getGMT(TranslationManager $tm)
    {
        return new SimpleTimeZone($tm, 0, 'GMT');
    }

    /**
     * Overloaded.
     *
     * @see        TimeZone::getOffsetIIIIII()
     * @see        TimeZone::getOffsetIIIIIII()
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public function getOffset()
    {
        $arguments = func_get_args();
        $fName = Toolkit::overloadHelper(array(
            array('name' => 'getOffsetIIIIII',
                        'parameters' => array('int', 'int', 'int', 'int', 'int', 'int')),
            array('name' => 'getOffsetIIIIIII',
                        'parameters' => array('int', 'int', 'int', 'int', 'int', 'int', 'int')),
            ),
            $arguments
        );

        return call_user_func_array(array($this, $fName), $arguments);
    }

    /**
     * Returns the time zone raw and GMT offset for the given moment
     * in time.  Upon return, local-millis = GMT-millis + rawOffset +
     * dstOffset.  All computations are performed in the proleptic
     * Gregorian calendar.  The default implementation in the TimeZone
     * class delegates to the 8-argument getOffset().
     *
     * @param      float $date      Moment in time for which to return offsets, in units of
     *                              milliseconds from January 1, 1970 0:00 GMT, either GMT
     *                              time or local wall time, depending on `local'.
     * @param      bool  $local     If true, `date' is local wall time; otherwise it
     *                              is in GMT time.
     * @param      int   $rawOffset Output parameter to receive the raw offset, that is, the
     *                              offset not including DST adjustments
     * @param      int   $dstOffset Output parameter to receive the DST offset, that is, the
     *                              offset to be added to `rawOffset' to obtain the total
     *                              offset between local and GMT time. If DST is not in
     *                              effect, this value is zero; otherwise it is a positive
     *                              value, typically one hour.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @author     The ICU Project
     * @since      0.11.0
     */
    public function getOffsetRef($date, $local, &$rawOffset, &$dstOffset)
    {
        $rawOffset = $this->getRawOffset();

        // Convert to local wall millis if necessary
        if (!$local) {
            $date += $rawOffset; // now in local standard millis
        }

        // When local==FALSE, we might have to recompute. This loop is
        // executed once, unless a recomputation is required; then it is
        // executed twice.
        for ($pass = 0; true; ++$pass) {
            $year = $month = $dom = $dow = 0;
            $day = floor($date / DateDefinitions::MILLIS_PER_DAY);
            $millis = (int) ($date - $day * DateDefinitions::MILLIS_PER_DAY);
            
            CalendarGrego::dayToFields($day, $year, $month, $dom, $dow);
            
            $dstOffset = $this->getOffsetIIIIIII(GregorianCalendar::AD, $year, $month, $dom, $dow, $millis, CalendarGrego::monthLength($year, $month)) - $rawOffset;

            // Recompute if local==FALSE, dstOffset!=0, and addition of
            // the dstOffset puts us in a different day.
            if ($pass != 0 || $local || $dstOffset == 0) {
                break;
            }
            $date += $dstOffset;
            if (floor($date / DateDefinitions::MILLIS_PER_DAY) == $day) {
                break;
            }
        }
    }

    /**
     * Sets the TimeZone's raw GMT offset (i.e., the number of milliseconds to
     * add to GMT to get local time, before taking daylight savings time into
     * account).
     *
     * @param      int $offsetMillis The new raw GMT offset for this time zone.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @author     The ICU Project
     * @since      0.11.0
     */
    abstract public function setRawOffset($offsetMillis);

    /**
     * Returns the TimeZone's raw GMT offset (i.e., the number of milliseconds to
     * add to GMT to get local time, before taking daylight savings time into
     * account).
     *
     * @return     int The TimeZone's raw GMT offset.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @author     The ICU Project
     * @since      0.11.0
     */
    abstract public function getRawOffset();

    /**
     * Returns the TimeZone's ID.
     *
     * @return     string This TimeZone's ID.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @author     The ICU Project
     * @since      0.11.0
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the TimeZone's ID to the specified value.  This doesn't affect any
     * other fields (for example, if you say
     * <code>
     *   $foo = $tm->createTimeZone('America/New_York');
     *   $foo->setId('America/Los_Angeles');
     * </code>
     * the time zone's GMT offset and daylight-savings rules don't change to those
     * for Los Angeles. They're still those for New York. Only the ID has
     * changed.)
     *
     * @param      string $id The new timezone ID.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @author     The ICU Project
     * @since      0.11.0
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Returns the resolved TimeZone's ID.
     *
     * @return     string This TimeZone's ID.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @author     The ICU Project
     * @since      0.11.0
     */
    public function getResolvedId()
    {
        if ($this->resolvedId === null) {
            return $this->id;
        }

        return $this->resolvedId;
    }

    /**
     * Sets the resolved TimeZone's ID.
     *
     * @param      string $id The resolved timezone ID.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @author     The ICU Project
     * @since      0.11.0
     */
    public function setResolvedId($id)
    {
        $this->resolvedId = $id;
    }

    /**
     * Enum for use with getDisplayName
     * @stable ICU 2.4
     */
    /**
     * Selector for short display name
     * @stable ICU 2.4
     */
    const SHORT = 1;
    /**
     * Selector for long display name
     * @stable ICU 2.4
     */
    const LONG = 2;

    /**
     * Returns a name of this time zone suitable for presentation to the user
     * in the specified locale.
     * If the display name is not available for the locale,
     * then this method returns a string in the format
     * <code>GMT[+-]hh:mm</code>.
     *
     * @param      bool   $daylight If true, return the daylight savings name.
     * @param      int    $style    Either <code>self::LONG</code> or <code>self::SHORT</code>
     * @param      Locale $locale   The locale in which to supply the display name.
     *
     * @return     string the human-readable name of this time zone in the given
     *                    locale or in the default locale if the given locale is
     *                    not recognized.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @author     The ICU Project
     * @since      0.11.0
     */
    public function getDisplayName($daylight = null, $style = null, Locale $locale = null)
    {
        if ($daylight === null) {
            $daylight = false;
            $style = self::LONG;
            $locale = $this->translationManager->getCurrentLocale();
        } elseif ($daylight instanceof Locale) {
            $locale = $daylight;
            $daylight = false;
            $style = self::LONG;
        } elseif (is_bool($daylight) && $style !== null) {
            if ($locale === null) {
                $locale = $this->translationManager->getCurrentLocale();
            }
        } else {
            throw new \InvalidArgumentException('Illegal arguments for TimeZone::getDisplayName');
        }

        $displayString = null;

        if ($daylight && $this->useDaylightTime()) {
            if ($style == self::LONG) {
                $displayString = $locale->getTimeZoneLongDaylightName($this->getId());
            } else {
                $displayString = $locale->getTimeZoneShortDaylightName($this->getId());
            }
        } else {
            if ($style == self::LONG) {
                $displayString = $locale->getTimeZoneLongStandardName($this->getId());
            } else {
                $displayString = $locale->getTimeZoneShortStandardName($this->getId());
            }
        }

        if (!$displayString) {
            $displayString = $this->formatOffset($daylight);
        }

        return $displayString;
    }

    /**
     * Returns the GMT+-hh:mm representation of this timezone.
     *
     * @param      bool   $daylight  Whether dst is active.
     * @param      string $separator The hour/minute and minute/second separator.
     * @param      string $prefix    A prefix to be added in front of the string.
     *
     * @return     string The formatted representation.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      1.0.2
     */
    public function formatOffset($daylight, $separator = ':', $prefix = 'GMT')
    {
        $value = $this->getRawOffset() + ($daylight ? $this->getDSTSavings() : 0);

        if ($value < 0) {
            $str = sprintf('%s-', $prefix);
            $value = -$value; // suppress the '-' sign for text display.
        } else {
            $str = sprintf('%s+', $prefix);
        }

        $str .=     str_pad((int) ($value / DateDefinitions::MILLIS_PER_HOUR), 2, '0', STR_PAD_LEFT)
                        . $separator
                        . str_pad((int) (($value % DateDefinitions::MILLIS_PER_HOUR) / DateDefinitions::MILLIS_PER_MINUTE), 2, '0', STR_PAD_LEFT);
        $offsetSeconds = ((int) ($value / DateDefinitions::MILLIS_PER_SECOND) % 60);
        if ($offsetSeconds) {
            $str .= $separator . str_pad($offsetSeconds, 2, '0', STR_PAD_LEFT);
        }
        return $str;
    }

    /**
     * Queries if this time zone uses daylight savings time.
     *
     * @return     bool If this time zone uses daylight savings time,
     *                  false, otherwise.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @author     The ICU Project
     * @since      0.11.0
     */
    abstract public function useDaylightTime();

    /**
     * Returns true if this zone has the same rule and offset as another zone.
     * That is, if this zone differs only in ID, if at all.
     *
     * @param      TimeZone $other The object to be compared with
     *
     * @return     bool True if the given zone is the same as this one,
     *                  with the possible exception of the ID
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @author     The ICU Project
     * @since      0.11.0
     */
    public function hasSameRules(TimeZone $other)
    {
        return ($this->getRawOffset() == $other->getRawOffset() &&
                        $this->useDaylightTime() == $other->useDaylightTime());
    }

    /**
     * Returns the amount of time to be added to local standard time
     * to get local wall clock time.
     * <p>
     * The default implementation always returns 3600000 milliseconds
     * (i.e., one hour) if this time zone observes Daylight Saving
     * Time. Otherwise, 0 (zero) is returned.
     * <p>
     * If an underlying TimeZone implementation subclass supports
     * historical Daylight Saving Time changes, this method returns
     * the known latest daylight saving value.
     *
     * @return     int The amount of saving time in milliseconds
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @author     The ICU Project
     * @since      0.11.0
     */
    public function getDSTSavings()
    {
        if ($this->useDaylightTime()) {
            return 3600000;
        }
        return 0;
    }

    /**
     * Construct a timezone with a given ID.
     *
     * @param      TranslationManager $tm The translation Manager
     * @param      string             $id A system time zone ID
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @author     The ICU Project
     * @since      0.11.0
     */
    protected function __construct(TranslationManager $tm, $id = '')
    {
        $this->translationManager = $tm;
        $this->id = $id;
    }

    /**
     * Returns the TimeZone's adjusted GMT offset (i.e., the number of
     * milliseconds to add to GMT to get local time in this time zone, taking
     * daylight savings time into account) as of a particular reference date.
     * The reference date is used to determine whether daylight savings time is
     * in effect and needs to be figured into the offset that is returned (in
     * other words, what is the adjusted GMT offset in this time zone at this
     * particular date and time?).  For the time zones produced by
     * createTimeZone(), the reference data is specified according to the
     * Gregorian calendar, and the date and time fields are local standard time.
     *
     * <p>Note: Don't call this method. Instead, call the getOffsetRef() which
     * returns both the raw and the DST offset for a given time. This method
     * is retained only for backward compatibility.
     *
     * @param      int $era       The reference date's era
     * @param      int $year      The reference date's year
     * @param      int $month     The reference date's month (0-based; 0 is January)
     * @param      int $day       The reference date's day-in-month (1-based)
     * @param      int $dayOfWeek The reference date's day-of-week (1-based; 1 is Sunday)
     * @param      int $millis    The reference date's milliseconds in day, local standard
     *                 time
     *
     * @return     int The offset in milliseconds to add to GMT to get local time.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @author     The ICU Project
     * @since      0.11.0
     */
    abstract protected function getOffsetIIIIII($era, $year, $month, $day, $dayOfWeek, $millis);

    /**
     * Gets the time zone offset, for current date, modified in case of
     * daylight savings. This is the offset to add *to* UTC to get local time.
     *
     * <p>Note: Don't call this method. Instead, call the getOffsetRef(), which
     * returns both the raw and the DST offset for a given time. This method
     * is retained only for backward compatibility.
     *
     * @param      int $era          The era of the given date.
     * @param      int $year         The year in the given date.
     * @param      int $month        The month in the given date.
     *                               Month is 0-based. e.g., 0 for January.
     * @param      int $day          The day-in-month of the given date.
     * @param      int $dayOfWeek    The day-of-week of the given date.
     * @param      int $milliseconds The millis in day in <em>standard</em> local time.
     * @param      int $monthLength  The length of the given month in days.
     *
     * @return     int The offset to add *to* GMT to get local time.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @author     The ICU Project
     * @since      0.11.0
     */
    abstract protected function getOffsetIIIIIII($era, $year, $month, $day, $dayOfWeek, $milliseconds, $monthLength);

    /**
     * Parse a custom time zone identifier and return a corresponding zone.
     *
     * @param      TranslationManager $tm The translation manager
     * @param      string             $id A string of the form GMT[+-]hh:mm, GMT[+-]hhmm, or
     *                                    GMT[+-]hh.
     *
     * @return     TimeZone A newly created SimpleTimeZone with the
     *                           given offset and no Daylight Savings Time, or
     *                           null if the id cannot be parsed.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @author     The ICU Project
     * @since      0.11.0
     */
    public static function createCustomTimeZone(TranslationManager $tm, $id)
    {
        $maxCustomHour = 23;
        $maxCustomMin = 59;
        $maxCustomSec = 59;
        
        $hours = 0;
        $minutes = 0;
        $seconds = 0;
        $negative = false;
        if (preg_match('#^GMT([+-])(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?$#', $id, $match)) {
            $negative = $match[1] == '-';
            $hours = $match[2];
            $minutes = $match[3];
            $seconds = isset($match[4]) ? $match[4] : 0;
        } elseif (preg_match('#^GMT([+-])(\d{1,6})$#', $id, $match)) {
            $negative = $match[1] == '-';
            // Supported formats are below -
            //
            // HHmmss
            // Hmmss
            // HHmm
            // Hmm
            // HH
            // H
            $hours = $match[2];
            switch (strlen($hours)) {
                case 1:
                case 2:
                    // already set to hour
                    break;
                case 3:
                case 4:
                    $minutes = $hours % 100;
                    $hours = (int) ($hours / 100);
                    break;
                case 5:
                case 6:
                    $seconds = $hours % 100;
                    $minutes = ((int)($hours / 100)) % 100;
                    $hours = (int) ($hours / 10000);
                    break;
            }
        } else {
            throw new \InvalidArgumentException('Zone identifier is not parseable');
        }
        
        if ($hours > $maxCustomHour || $minutes > $maxCustomMin || $seconds > $maxCustomSec) {
            throw new \InvalidArgumentException('Zone identifier is not parseable');
        }
        
        $offset = $hours * 3600 + $minutes * 60 + $seconds;
        
        if ($negative) {
            $offset = -$offset;
        }
        
        // create the timezone with an empty id and set it afterwards
        $tz = new SimpleTimeZone($tm, $offset * 1000.0);
        $tz->setId($tz->formatOffset(false, ''));
        return $tz;
    }

    /**
     * Returns true if the two TimeZones are equal. (The TimeZone version
     * only compares IDs, but subclasses are expected to also compare the fields
     * they add.)
     *
     * @param      TimeZone $that The object to be compared with.
     *
     * @return     bool          True if the given TimeZone is equal to this
     *                           TimeZone; false otherwise.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @author     The ICU Project
     * @since      0.11.0
     */
    public function __is_equal(TimeZone $that)
    {
        return get_class($this) == get_class($that) && $this->getId() == $that->getId();
    }

    /**
     * Returns true if the two TimeZones are NOT equal; that is, if operator==()
     * returns false.
     *
     * @param      TimeZone $that The object to be compared with.
     *
     * @return     bool          True if the given TimeZone is not equal to this
     *                           TimeZone; false otherwise.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @author     The ICU Project
     * @since      0.11.0
     */
    public function __is_not_equal(TimeZone $that)
    {
        return get_class($this) != get_class($that) || $this->getId() != $that->getId();
    }

    /**
     * Queries if the given date is in daylight savings time in
     * this time zone.
     * This method is wasteful since it creates a new GregorianCalendar and
     * deletes it each time it is called.
     *
     * @param      float $date The given time
     *
     * @return     bool  True if the given date is in daylight savings time,
     *                   false, otherwise.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @author     The ICU Project
     * @since      0.11.0
     */
    public function inDaylightTime($date)
    {
        $cal = new GregorianCalendar($this);
        $cal->setTime($date);
        return $cal->inDaylightTime();
    }
}
