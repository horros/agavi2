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
use Agavi\Core\Context;
use Agavi\Exception\AgaviException;
use Agavi\Exception\UnreadableException;

/**
 * AgaviTimeZoneDataParser allows you to retrieve the contents of the olson
 * time zone database files parsed into the different definitions.
 *
 * @package    agavi
 * @subpackage date
 *
 * @author     Dominik del Bondio <ddb@bitxtender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.11.0
 *
 * @version    $Id$
 */
class TimeZoneDataParser
{
    /**
     * @var        Context An Context instance.
     */
    protected $context = null;

    /**
     * Retrieve the current application context.
     *
     * @return     Context An Context instance.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    final public function getContext()
    {
        return $this->context;
    }

    /**
     * Initialize this parser.
     *
     * @param      Context $context A Context instance.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public function initialize(Context $context)
    {
        $this->context = $context;
    }

    const MIN_GEN_YEAR   =  1900;
    const MAX_GEN_YEAR   =  2040;
    const MAX_YEAR_VALUE =  2147483647;
    const MIN_YEAR_VALUE = -2147483647;

    /**
     * @var        array The preprocessed rules array.
     */
    protected $rules = array();

    /**
     * @see        AgaviConfigParser::parse()
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public function parse($config)
    {
        if (!is_readable($config)) {
            $error = 'Configuration file "' . $config . '" does not exist or is unreadable';
            throw new UnreadableException($error);
        }

        return $this->parseFile($config);
    }

    /**
     * Parses the given file
     *
     * @param      string $file The full path to the file to parse.
     *
     * @return     array An array of zones and links.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    protected function parseFile($file)
    {
        $data = file_get_contents($file);


        if (file_exists(dirname($file) . '/version')) {
            $meta = [
                'filename' => realpath($file),
                'version' => file_get_contents(dirname($file) . '/version')
            ];
        } else {
            $meta = array(
                'filename' => '(unknown)',
                'version' => '(unknown)',
            );
        }


        $zoneLines = explode("\n", $data);
        // filter comments
        $zoneLines = array_filter($zoneLines, function ($line) {
            return !(strlen(trim($line)) == 0 || preg_match('!^\s*#!', $line));
        });

        $zones = array();
        $rules = array();
        $links = array();
        while (list($i, $line) = each($zoneLines)) { // for($i = 0, $c = count($zoneLines); $i < $c; ++$i) {
            $line = $zoneLines[$i];
            if (preg_match('!^\s*Rule\s*(.*)!', $line, $match)) {
                $cols = $this->splitLine($match[1], 9);
                $rule = $this->parseRule($cols);
                $rules[$rule['name']][] = $rule;
            } elseif (preg_match('!^\s*Zone\s*(.*)!', $line, $match)) {
                $colLines = array();
                $lineCols = $this->splitLine($match[1], 5);
                $colLines[] = $lineCols;
                // the until column exists so we need to fetch the continuation line
                if (isset($lineCols[4]) && list($i, $line) = each($zoneLines)) {
                    do {
                        $lineCols = $this->splitLine($line, 4);
                        $colLines[] = $lineCols;
                    } while (isset($lineCols[3]) && list($i, $line) = each($zoneLines));
                }

                $zone = $this->parseZone($colLines);
                $zone['source'] = $meta['filename'];
                $zone['version'] = $meta['version'];
                $zones[] = $zone;
            } elseif (preg_match('!^\s*Link\s+([^\s]+)\s+([^\s]+)!', $line, $match)) {
                // to - from
                $links[$match[2]] = $match[1];
            } elseif (preg_match('!^\s*Leap\s*(.*)!', $line, $match)) {
                // leap seconds are ignored
            } else {
                throw new AgaviException('Unknown line ' . $line . ' in file ' . $file);
            }
        }

        $this->prepareRules($rules);
        $zones = $this->generateDatatables($zones);
        return array('zones' => $zones, 'links' => $links, 'meta' => $meta);
    }

    /**
     * Prepares as much info for each internal rule as possible and set them in
     * $this->rules.
     *
     * @param      array $rules The rules.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    protected function prepareRules($rules)
    {
        $finalRules = array();

        foreach ($rules as $name => $ruleList) {
            $activeRules = array();
            $myRules = array();

            $cnt = count($ruleList);
            for ($i = 0; $i < $cnt; ++$i) {
                $last = ($i + 1 == $cnt);
                $myRule = $ruleList[$i];

                if ($myRule['startYear'] == self::MIN_YEAR_VALUE) {
                    $year = $myRule['endYear'];
                } else {
                    $year = $myRule['startYear'];
                }

                // while we have active rules and the next rule is more then 1 year
                // beyond we need to apply the active rules to all the missing years
                do {
                    $hasNonFinalRules = false;
                    // check if we have any active rules which are not final, so we need to process the final ones too
                    foreach ($activeRules as $activeRule) {
                        if ($activeRule['endYear'] != self::MAX_YEAR_VALUE) {
                            $hasNonFinalRules = true;
                            break;
                        }
                    }

                    // remove all (still) active rules which don't apply anymore
                    foreach ($activeRules as $activeRuleIdx => $activeRule) {
                        if (!is_numeric($activeRule['endYear'])) {
                            throw new AgaviException('endYear should be numeric but was: ' . $activeRule['endYear']);
                        }
                        if ($activeRule['endYear'] < $year) {
                            unset($activeRules[$activeRuleIdx]);
                        // protect against generating final rules, they are handled in the timezone implementation
                        } elseif ($year != $activeRule['startYear'] && ($hasNonFinalRules || !$last)) {
                            // if the year is the start year this rule has already been processed for this year
                            $time = $this->getOnDate($year, $activeRule['month'], $activeRule['on'], $myRule['at'], 0, 0);
                            $myRules[] = array('time' => $time, 'rule' => $activeRule);
                        }
                    }

                    if ($year == $myRule['startYear']) {
                        $time = $this->getOnDate($year, $myRule['month'], $myRule['on'], $myRule['at'], 0, 0);

                        if (($myRule['endYear'] != self::MAX_YEAR_VALUE || $year == $myRule['startYear']) || $hasNonFinalRules) {
                            $myRules[] = array('time' => $time, 'rule' => $myRule);
                        }

                        if ($myRule['startYear'] != $myRule['endYear']) {
                            $activeRules[] = $myRule;
                        }
                    }

                    ++$year;
                } while (count($activeRules) && ((!$last && $ruleList[$i + 1]['startYear'] > $year) || ($last && $year < self::MAX_GEN_YEAR)));
            }

            usort($myRules, array(__CLASS__, 'ruleCmp'));
            $finalRules[$name]['activeRules'] = $activeRules;
            $finalRules[$name]['rules'] = $myRules;
        }

        $this->rules = $finalRules;
    }

    /**
     * Comparison function for usort comparing the time of 2 rules.
     *
     * @param      array $a Parameter a
     * @param      array $b Parameter b
     *
     * @return     int 0 if the time equals -1 if a is smaller, 1 if b is smaller.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public static function ruleCmp($a, $b)
    {
        if ($a['time'] == $b['time']) {
            return 0;
        }
        
        return ($a['time'] < $b['time']) ? -1 : 1;
    }

    /**
     * Returns as rules with the given name within the given limits.
     *
     * @param      string $name The name of the ruleset.
     * @param      int    $from The lower time limit of the rules.
     * @param      string $until The upper time limit as string.
     * @param      int    $gmtOff The gmt offset to be used.
     * @param      string $format The dst format.
     *
     * @return     array  The rules which matched the criteria completely
     *                    processed.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    protected function getRules($name, $from, $until, $gmtOff, $format)
    {
        if (!isset($this->rules[$name])) {
            throw new \InvalidArgumentException('No rule with the name ' . $name . ' exists');
        }

        $lastDstOff = 0;

        $rules = array();
        $lastUntilTime = $untilTime = null;
        $firstHit = true;
        $lastRule = null;
        $lastSkippedRule = null;

        foreach ($this->rules[$name]['rules'] as $rule) {
            $time = $rule['time'];
            $dstOff = $rule['rule']['save'];
            $isEndless = $rule['rule']['endYear'] == self::MAX_YEAR_VALUE;

            if ($until !== null) {
                $untilDate = $this->dateStrToArray($until);
                $untilTime = $this->getOnDate($untilDate['year'], $untilDate['month'], array('type' => 'date', 'date' => $untilDate['day'], 'day' => null), array('secondsInDay' => $untilDate['time']['seconds'], 'type' => $untilDate['time']['type']), $gmtOff, $dstOff);
            }

            switch ($rule['rule']['at']['type']) {
                case 'wallclock':
                    $time -= $lastDstOff;
                    $time -= $gmtOff;
                    break;

                case 'standard':
                    $time -= $gmtOff;
                    break;
            }

            $lastDstOff = $dstOff;

            if ($from !== null && $time < $from) {
                $lastSkippedRule = $rule;
                // if we need to skip the first few items until we reached the desired from
                continue;
            } elseif ($firstHit) {
                if ($from != $time) {
                    $insertRuleName = sprintf(is_array($format) ? $format[0] : $format, $lastSkippedRule !== null ? $lastSkippedRule['rule']['variablePart'] : '');

                    $rules[] = array(
                        'time' => $from,
                        'rawOffset' => $gmtOff,
                        'dstOffset' => 0,
                        'name' => $insertRuleName,
                        'fromEndless' => false,
                    );
                }
                $firstHit = false;
            }

            if ($until !== null && $time >= $untilTime) {
                break;
            }

            $rules[] = array(
                'time' => $time,
                'rawOffset' => $gmtOff,
                'dstOffset' => $dstOff,
                'name' => sprintf(is_array($format) ? ($dstOff == 0 ? $format[0] : $format[1]) : $format, $rule['rule']['variablePart']),
                'fromEndless' => $isEndless,
            );

            $lastUntilTime = $untilTime;
            $lastRule = $rule;
        }

        return array('rules' => $rules, 'untilTime' => $lastUntilTime, 'activeRules' => $this->rules[$name]['activeRules']);
    }

    /**
     * Generates all the zone tables by processing their rules.
     *
     * @param      array $zones The input zones tables.
     *
     * @return     array The processed zones.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    protected function generateDatatables($zones)
    {
        $zoneTables = array();

        foreach ($zones as $zone) {
            $start = true;
            $myRules = array();
            $finalRule = array();
            $activeSubRules = null;
            $lastRuleEndTime = null;
            $lastDstOff = 0;
            $zoneRuleCnt = count($zone['rules']);
            for ($z = 0; $z < $zoneRuleCnt; ++$z) {
                $lastZoneRule = ($z + 1 == $zoneRuleCnt);
                $zoneRule = $zone['rules'][$z];

                $activeSubRules = null;

                $gmtOff = $zoneRule['gmtOff'];
                $rule = $zoneRule['rule'];
                $dstOff = is_int($rule) ? $rule : 0;
                $format = $zoneRule['format'];
                $until = $zoneRule['until'];

                // when the rule is a rule an not the dst save
                if (is_string($rule)) {
                    $rules = $this->getRules($rule, $lastRuleEndTime, $until, $gmtOff, $format);
                    $untilTime = $rules['untilTime'];
                    $activeSubRules = $rules['activeRules'];
                    $rules = $rules['rules'];

                    $myRules = array_merge($myRules, $rules);

                    $lastRuleEndTime = $untilTime;
                } else {
                    if ($until) {
                        $untilDate = $this->dateStrToArray($until);
                        $untilDateTime = $this->getOnDate($untilDate['year'], $untilDate['month'], array('type' => 'date', 'date' => $untilDate['day'], 'day' => null), array('secondsInDay' => $untilDate['time']['seconds'], 'type' => $untilDate['time']['type']), $gmtOff, $dstOff);
                    } else {
                        $untilDateTime = null;
                    }

                    if ($lastRuleEndTime !== null) {
                        $myRules[] = array('time' => $lastRuleEndTime, 'rawOffset' => $gmtOff, 'dstOffset' => $dstOff, 'name' => $format);
                    } else {
                        // TODO: we probably don't need to add the first rule at all, check this!
                    }

                    $lastRuleEndTime = $untilDateTime;
                }

                if ($lastZoneRule) {
                    if (count($myRules) == 0) {
                        // this should actually never happen!
                        $lastRuleStartYear = self::MIN_YEAR_VALUE;
                    } else {
                        $cal = $this->getContext()->getTranslationManager()->createCalendar();
                        $lastRuleStartYear = self::MIN_YEAR_VALUE;
                        for ($i = count($myRules) - 1; $i > 0; --$i) {
                            if (!isset($myRules[$i]['fromEndless']) || !$myRules[$i]['fromEndless']) {
                                break;
                            }
                        }

                        $cal->setTime($myRules[$i]['time'] * DateDefinitions::MILLIS_PER_SECOND);
                        // + 1 because this specifies the first year in which the final rule will apply
                        $lastRuleStartYear = $cal->get(DateDefinitions::YEAR) + 1;
                    }

                    if ($activeSubRules !== null) {
                        $cnt = count($activeSubRules);
                        if ($cnt != 0 && $cnt != 2) {
                            throw new AgaviException('unexpected active rule count ' . $cnt);
                        }
                        if ($cnt == 0) {
                            $finalRule = array('type' => 'none', 'offset' => $gmtOff, 'startYear' => $lastRuleStartYear);
                        } else {
                            // normalize the keys
                            $on = 0;
                            $off = 1;
                            $sr = array_values($activeSubRules);

                            if ($sr[1]['save'] > $sr[0]['save']) {
                                $on = 1;
                                $off = 0;
                            }

                            $finalRule = array(
                                'type' => 'dynamic',
                                'offset' => $gmtOff,
                                'name' => $format,
                                'save' => $sr[$on]['save'],
                                'start' => array(
                                    'month' => $sr[$on]['month'],
                                    'date' => null,
                                    'day_of_week' => null,
                                    'time' => $sr[$on]['at']['secondsInDay'] * DateDefinitions::MILLIS_PER_SECOND,
                                    'type' => SimpleTimeZone::WALL_TIME,
                                ),
                                'end' => array(
                                    'month' => $sr[$off]['month'],
                                    'date' => null,
                                    'day_of_week' => null,
                                    'time' => $sr[$off]['at']['secondsInDay'] * DateDefinitions::MILLIS_PER_SECOND,
                                    'type' => SimpleTimeZone::WALL_TIME,
                                ),
                                'startYear' => $lastRuleStartYear,
                            );

                            for ($i = 0; $i < count($sr); ++$i) {
                                if ($i == $on) {
                                    $frIdx = 'start';
                                } else {
                                    $frIdx = 'end';
                                }

                                if ($sr[$i]['at']['type'] == 'standard') {
                                    $finalRule[$frIdx]['type'] = SimpleTimeZone::STANDARD_TIME;
                                } elseif ($sr[$on]['at']['type'] == 'universal') {
                                    $finalRule[$frIdx]['type'] = SimpleTimeZone::UTC_TIME;
                                }

                                if ($sr[$i]['on']['type'] == 'date') {
                                    $finalRule[$frIdx]['date'] = $sr[$i]['on']['date'];
                                    $finalRule[$frIdx]['day_of_week'] = 0;
                                } elseif ($sr[$i]['on']['type'] == 'last') {
                                    $finalRule[$frIdx]['date'] = -1;
                                    $finalRule[$frIdx]['day_of_week'] = $sr[$i]['on']['day'];
                                } elseif ($sr[$i]['on']['type'] == '<=') {
                                    $finalRule[$frIdx]['date'] = -$sr[$i]['on']['date'];
                                    $finalRule[$frIdx]['day_of_week'] = -$sr[$i]['on']['day'];
                                } elseif ($sr[$i]['on']['type'] == '>=') {
                                    $finalRule[$frIdx]['date'] = $sr[$i]['on']['date'];
                                    $finalRule[$frIdx]['day_of_week'] = -$sr[$i]['on']['day'];
                                }
                            }
                        }
                    } else {
                        $finalRule = array('type' => 'static', 'name' => $format, 'offset' => $gmtOff, 'startYear' => $lastRuleStartYear);
                    }
                }
            }

            $myTypes = array();

            // compact the same (raw|dst)offset & name fields
            foreach ($myRules as $id => $rule) {
                if (is_array($rule['name'])) {
                    continue;
                }
                $key = sprintf('raw=%d&dst=%d&name=%s', $rule['rawOffset'], $rule['dstOffset'], $rule['name']);
                $myTypes[$key][] = $id;
            }

            $typeId = 0;
            $myFinalTypes = array();
            $myFinalRules = array();
            foreach ($myTypes as $key => $ids) {
                $firstRule = $myRules[$ids[0]];
                $myFinalTypes[$typeId] = array('rawOffset' => $firstRule['rawOffset'], 'dstOffset' => $firstRule['dstOffset'], 'name' => $firstRule['name']);
                foreach ($ids as $id) {
                    $myFinalRules[] = array('time' => $myRules[$id]['time'], 'type' => $typeId);
                }
                ++$typeId;
            }

            usort($myFinalRules, array(__CLASS__, 'ruleCmp'));

            $zoneTables[$zone['name']] = array('types' => $myFinalTypes, 'rules' => $myFinalRules, 'finalRule' => $finalRule, 'source' => $zone['source'], 'version' => $zone['version']);
        }

        return $zoneTables;
    }

    /**
     * Returns the time specified by the input arguments.
     *
     * @param      int $year The year.
     * @param      int $month The month.
     * @param      array $dateDef The date definition.
     * @param      array $atDef The at (time into the day) definition.
     * @param      int $gmtOff The gmt offset.
     * @param      int $dstOff The dst offset.
     *
     * @return     int The unix timestamp.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    protected function getOnDate($year, $month, $dateDef, $atDef, $gmtOff, $dstOff)
    {
        static $cal = null;
        if (!$cal) {
            $cal = $this->getContext()->getTranslationManager()->createCalendar();
        }

        $cal->clear();
        $cal->set(DateDefinitions::YEAR, $year);
        $cal->set(DateDefinitions::MONTH, $month);
        if ($dateDef['type'] == 'date') {
            $cal->set(DateDefinitions::DATE, $dateDef['date']);
        } elseif ($dateDef['type'] == 'last') {
            $daysInMonth = CalendarGrego::monthLength($year, $month);
            $cal->set(DateDefinitions::DATE, $daysInMonth);
            // loop backwards until we found the last occurrence of the day
            while ($cal->get(DateDefinitions::DAY_OF_WEEK) != $dateDef['day']) {
                $cal->roll(DateDefinitions::DATE, -1);
            }
        } elseif ($dateDef['type'] == '<=') {
            $cal->set(DateDefinitions::DATE, $dateDef['date']);
            while ($cal->get(DateDefinitions::DAY_OF_WEEK) != $dateDef['day']) {
                $cal->roll(DateDefinitions::DATE, -1);
            }
        } elseif ($dateDef['type'] == '>=') {
            $cal->set(DateDefinitions::DATE, $dateDef['date']);
            while ($cal->get(DateDefinitions::DAY_OF_WEEK) != $dateDef['day']) {
                $cal->roll(DateDefinitions::DATE, 1);
            }
        } else {
            throw new AgaviException('Unknown on type ' . $dateDef['type']);
        }
        $time = $cal->getTime() / 1000;

        $time += $atDef['secondsInDay'];
        if ($atDef['type'] == 'wallclock') {
            $time -= $dstOff;
            $time -= $gmtOff;
        } elseif ($atDef['type'] == 'standard') {
            $time -= $gmtOff;
        }

        return $time;
    }

    /**
     * Splits a line into the amount of items requested according to the
     * olson definition (which allows the last item to contain spaces)
     *
     * @param      string $line The line.
     * @param      int $itemCount The amount of items.
     *
     * @return     array The items.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    protected function splitLine($line, $itemCount)
    {
        $line = trim($line);

        $inQuote = false;
        $itemStr = '';
        $lastChar = false;
        $items = array();
        $itemPos = 0;
        for ($i = 0, $l = strlen($line); $i < $l; ++$i) {
            if ($i + 1 == $l) {
                $lastChar = true;
                $cNext = null;
            } else {
                $cNext = $line[$i+1];
            }
            $c = $line[$i];

            if (!$inQuote) {
                if ($c == '"') {
                    $inQuote = true;
                } elseif ($c == '#') {
                    // make char to space to trigger processing
                    $c = ' ';
                    $i = $l;
                } elseif (!ctype_space($c) || (($itemPos + 1 == $itemCount) && strlen($itemStr) > 0)) {
                    $itemStr .= $c;
                }
            } else {
                if ($c == '"' && $cNext == '"') {
                    $itemStr .= $c;
                    ++$i;
                } elseif ($c == '"') {
                    $inQuote = false;
                } else {
                    $itemStr .= $c;
                }
            }

            if (($lastChar || ctype_space($c)) && strlen($itemStr) > 0) {
                if (isset($items[$itemPos])) {
                    $itemStr = $items[$itemPos] . $itemStr;
                }
                $items[$itemPos] = $itemStr;
                if ($itemPos + 1 < $itemCount) {
                    ++$itemPos;
                }
                $itemStr = '';
            }
        }

        return array_map('trim', $items);
    }

    /**
     *            NAME  FROM  TO    TYPE  IN   ON       AT    SAVE  LETTER/S
     *
     * For example:
     *
     *      Rule  US    1967  1973  -     Apr  lastSun  2:00  1:00  D
     *
     * The fields that make up a rule line are:
     *
     *  NAME    Gives the (arbitrary) name of the set of rules this
     *          rule is part of.
     *
     *  FROM    Gives the first year in which the rule applies.  Any
     *          integer year can be supplied; the Gregorian calendar
     *          is assumed.  The word minimum (or an abbreviation)
     *          means the minimum year representable as an integer.
     *          The word maximum (or an abbreviation) means the
     *          maximum year representable as an integer.  Rules can
     *          describe times that are not representable as time
     *          values, with the unrepresentable times ignored; this
     *          allows rules to be portable among hosts with
     *          differing time value types.
     *
     *  TO      Gives the final year in which the rule applies.  In
     *          addition to minimum and maximum (as above), the word
     *          only (or an abbreviation) may be used to repeat the
     *          value of the FROM field.
     *
     *  TYPE    Gives the type of year in which the rule applies.
     *          If TYPE is - then the rule applies in all years
     *          between FROM and TO inclusive.  If TYPE is something
     *          else, then zic executes the command
     *               yearistype year type
     *          to check the type of a year:  an exit status of zero
     *          is taken to mean that the year is of the given type;
     *          an exit status of one is taken to mean that the year
     *          is not of the given type.
     *
     *  IN      Names the month in which the rule takes effect.
     *          Month names may be abbreviated.
     *
     *  ON      Gives the day on which the rule takes effect.
     *          Recognized forms include:
     *
     *               5        the fifth of the month
     *               lastSun  the last Sunday in the month
     *               lastMon  the last Monday in the month
     *               Sun>=8   first Sunday on or after the eighth
     *               Sun<=25  last Sunday on or before the 25th
     *
     *          Names of days of the week may be abbreviated or
     *          spelled out in full.  Note that there must be no
     *          spaces within the ON field.
     *
     *  AT      Gives the time of day at which the rule takes
     *          effect.  Recognized forms include:
     *
     *               2        time in hours
     *               2:00     time in hours and minutes
     *               15:00    24-hour format time (for times after noon)
     *               1:28:14  time in hours, minutes, and seconds
     *               -        equivalent to 0
     *
     *          where hour 0 is midnight at the start of the day,
     *          and hour 24 is midnight at the end of the day.  Any
     *          of these forms may be followed by the letter w if
     *          the given time is local "wall clock" time, s if the
     *          given time is local "standard" time, or u (or g or
     *          z) if the given time is universal time; in the
     *          absence of an indicator, wall clock time is assumed.
     *
     *  SAVE    Gives the amount of time to be added to local
     *          standard time when the rule is in effect.  This
     *          field has the same format as the AT field (although,
     *          of course, the w and s suffixes are not used).
     *
     *  LETTER/S
     *          Gives the "variable part" (for example, the "S" or
     *          "D" in "EST" or "EDT") of time zone abbreviations to
     *          be used when this rule is in effect.  If this field
     *          is -, the variable part is null.
     */
    /**
     * Parses a rule.
     *
     * @param      array $ruleColumns The columns of this rule.
     *
     * @return     array The parsed rule.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    protected function parseRule($ruleColumns)
    {

        $name = $ruleColumns[0];
        $startYear = $ruleColumns[1];
        if (substr_compare($startYear, 'mi', 0, 2, true) == 0) {
            $startYear = self::MIN_YEAR_VALUE;
        } elseif (substr_compare($startYear, 'ma', 0, 2, true) == 0) {
            $startYear = self::MAX_YEAR_VALUE;
        }
        $endYear = $ruleColumns[2];
        if (substr_compare($endYear, 'mi', 0, 2, true) == 0) {
            $endYear = self::MIN_YEAR_VALUE;
        } elseif (substr_compare($endYear, 'ma', 0, 2, true) == 0) {
            $endYear = self::MAX_YEAR_VALUE;
        } elseif (substr_compare($endYear, 'o', 0, 1, true) == 0) {
            $endYear = $startYear;
        }

        $type = $ruleColumns[3];
        if ($type != '-') {
            throw new AgaviException('Unknown type "' . $type . '" in rule ' . $name);
        }

        $month = $this->getMonthFromAbbr($ruleColumns[4]);
        if (!is_numeric($month)) {
            throw new AgaviException('Unknown month "'.$month.'" in rule ' . $name);
        }

        $on = $ruleColumns[5];
        if (is_numeric($on)) {
            $on = array('type' => 'date', 'date' => $on, 'day' => null);
        } elseif (preg_match('!^last(.*)$!', $on, $match)) {
            $day = $this->getDayFromAbbr($match[1]);
            if (!is_numeric($day)) {
                throw new AgaviException('Unknown day "'.$day.'" in rule ' . $name);
            }

            $on = array('type' => 'last', 'date' => null, 'day' => $day);
        } elseif (preg_match('!^([a-z]+)(\>\=|\<\=)([0-9]+)$!i', $on, $match)) {
            $day = $this->getDayFromAbbr($match[1]);
            if (!is_numeric($day)) {
                throw new AgaviException('Unknown day "'.$day.'" in rule ' . $name);
            }

            $on = array('type' => $match[2], 'date' => $match[3], 'day' => $day);
        } else {
            throw new AgaviException('unknown on column (' . $on . ') in rule ' . $name);
        }

        $at = $ruleColumns[6];
        $lastAtChar = substr($at, -1);
        $atType = 'wallclock';
        if ($lastAtChar == 'w') {
            $at = substr($at, 0, -1);
        } elseif ($lastAtChar == 's') {
            $atType = 'standard';
            $at = substr($at, 0, -1);
        } elseif ($lastAtChar == 'u' || $lastAtChar == 'z' || $lastAtChar == 'g') {
            $atType = 'universal';
            $at = substr($at, 0, -1);
        }

        if ($at == '-') {
            $at = 0;
        } else {
            $at = $this->timeStrToSeconds($at);
        }

        $at = array('type' => $atType, 'secondsInDay' => $at);

        $save = $this->timeStrToSeconds($ruleColumns[7]);

        $variablePart = $ruleColumns[8];
        if ($variablePart == '-') {
            $variablePart = '';
        }

        return array(
            'name' => $name,
            'startYear' => $startYear,
            'endYear' => $endYear,
            'type' => $type,
            'month' => $month,
            'on' => $on,
            'at' => $at,
            'save' => $save,
            'variablePart' => $variablePart
        );
    }

    /*
	 *       NAME                GMTOFF  RULES/SAVE  FORMAT  [UNTIL]
	 *
	 * For example:
	 *
	 *       Australia/Adelaide  9:30    Aus         CST     1971 Oct 31 2:00
	 *
	 * The fields that make up a zone line are:
	 *
	 *  NAME  The name of the time zone.  This is the name used in
	 *        creating the time conversion information file for the
	 *        zone.
	 *
	 *  GMTOFF
	 *        The amount of time to add to UTC to get standard time
	 *        in this zone.  This field has the same format as the
	 *        AT and SAVE fields of rule lines; begin the field with
	 *        a minus sign if time must be subtracted from UTC.
	 *
	 *  RULES/SAVE
	 *        The name of the rule(s) that apply in the time zone
	 *        or, alternately, an amount of time to add to local
	 *        standard time.  If this field is - then standard time
	 *        always applies in the time zone.
	 *
	 *  FORMAT
	 *        The format for time zone abbreviations in this time
	 *        zone.  The pair of characters %s is used to show where
	 *        the "variable part" of the time zone abbreviation
	 *        goes.  Alternately, a slash (/) separates standard and
	 *        daylight abbreviations.
	 *
	 *  UNTIL The time at which the UTC offset or the rule(s) change
	 *        for a location.  It is specified as a year, a month, a
	 *        day, and a time of day.  If this is specified, the
	 *        time zone information is generated from the given UTC
	 *        offset and rule change until the time specified.  The
	 *        month, day, and time of day have the same format as
	 *        the IN, ON, and AT columns of a rule; trailing columns
	 *        can be omitted, and default to the earliest possible
	 *        value for the missing columns.
	 *
	 *        The next line must be a "continuation" line; this has
	 *        the same form as a zone line except that the string
	 *        "Zone" and the name are omitted, as the continuation
	 *        line will place information starting at the time
	 *        specified as the UNTIL field in the previous line in
	 *        the file used by the previous line.  Continuation
	 *        lines may contain an UNTIL field, just as zone lines
	 *        do, indicating that the next line is a further
	 *        continuation.
	 */
    /**
     * Parses a zone.
     *
     * @param      array $zoneLines The lines of this zone.
     *
     * @return     array The parsed zone.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    protected function parseZone($zoneLines)
    {
        $indexBase = 0;
        $i = 0;
        $c = count($zoneLines);

        $name = $zoneLines[$i][0];

        $rules = array();

        do {
            $zoneColumns = $zoneLines[$i];
            $gmtOff = $zoneColumns[$indexBase + 1];
            if ($gmtOff[0] == '-') {
                $gmtOff = - $this->timeStrToSeconds(substr($gmtOff, 1));
            } else {
                $gmtOff = $this->timeStrToSeconds($gmtOff);
            }

            $rule = $zoneColumns[$indexBase + 2];
            if ($rule == '-') {
                $rule = null;
            } elseif (preg_match('!^[^\s0-9][^\s]+$!', $rule)) {
            } elseif (preg_match('!^([0-9]+):([0-9]+)!', $rule, $match)) {
                $rule = $match[1] * 3600 + $match[2] * 60;
            } else {
                throw new AgaviException('Unknown rule column "' . $rule . '" in zone ' . $name);
            }

            $format = $zoneColumns[$indexBase + 3];
            if (strpos($format, '/') !== false) {
                $format = explode('/', $format);
            }

            $until = null;
            if (isset($zoneColumns[$indexBase + 4])) {
                $until = $zoneColumns[$indexBase + 4];
            }

            $rules[] = array('gmtOff' => $gmtOff, 'rule' => $rule, 'format' => $format, 'until' => $until);

            $indexBase = -1;
            ++$i;
        } while ($i < $c);

        return array('name' => $name, 'rules' => $rules);
    }

    /**
     * Determines the month definition from an abbreviation.
     *
     * @param      string $month The abbreviated month.
     *
     * @return     int The definition of this month from AgaviDateDefinitions.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    protected function getMonthFromAbbr($month)
    {
        static $months = array(DateDefinitions::JANUARY => 'january', DateDefinitions::FEBRUARY => 'february', DateDefinitions::MARCH => 'march', DateDefinitions::APRIL => 'april', DateDefinitions::MAY => 'may', DateDefinitions::JUNE => 'june', DateDefinitions::JULY => 'july', DateDefinitions::AUGUST => 'august', DateDefinitions::SEPTEMBER => 'september', DateDefinitions::OCTOBER => 'october', DateDefinitions::NOVEMBER => 'november', DateDefinitions::DECEMBER => 'december');

        foreach ($months as $i => $m) {
            if (substr_compare($m, $month, 0, strlen($month), true) == 0) {
                $month = $i;
                break;
            }
        }

        return $month;
    }

    /**
     * Determines the day definition from an abbreviation.
     *
     * @param      string $day The abbreviated day.
     *
     * @return     int The definition of this day from AgaviDateDefinitions.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    protected function getDayFromAbbr($day)
    {
        static $days = array(DateDefinitions::SUNDAY => 'sunday', DateDefinitions::MONDAY => 'monday', DateDefinitions::TUESDAY => 'tuesday', DateDefinitions::WEDNESDAY => 'wednesday', DateDefinitions::THURSDAY => 'thursday', DateDefinitions::FRIDAY => 'friday', DateDefinitions::SATURDAY => 'saturday');

        foreach ($days as $i => $d) {
            if (substr_compare($d, $day, 0, strlen($day), true) == 0) {
                $day = $i;
                break;
            }
        }

        return $day;
    }

    /**
     * Returns the seconds from a string in the hh:mm:ss format.
     *
     * @param      string $time The time as string.
     *
     * @return     int The seconds into the day defined by the input.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    protected function timeStrToSeconds($time)
    {
        if (preg_match('!^(-?)([0-9]{1,2})(\:[0-9]{1,2})?(\:[0-9]{1,2})?$!', $time, $match)) {
            $seconds = 0;
            if (isset($match[4])) {
                $seconds += substr($match[4], 1);
            }
            if (isset($match[3])) {
                $seconds += substr($match[3], 1) * 60;
            }
            $seconds += $match[2] * 60 * 60;
            if ($match[1] == '-') {
                $seconds = -$seconds;
            }
        } elseif ($time == '-') {
            $seconds = 0;
        } else {
            throw new AgaviException('unknown time format "' . $time . '"');
        }

        return $seconds;
    }

    /**
     * Parses a date string and returns its parts as array.
     *
     * @param      string $date The date as string.
     *
     * @return     array The parts of the date.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    protected function dateStrToArray($date)
    {
        $array = array('year' => 0, 'month' => 0, 'day' => 1, 'time' => array('type' => 'wallclock', 'seconds' => 0));
        if (preg_match('!(\d{4})(\s+[a-z0-9]+)?(\s+\d+)?(\s+\d[^\s]*)?!i', $date, $match)) {
            $match = array_map('trim', $match);
            $array['year'] = $match[1];
            if (isset($match[2])) {
                $array['month'] = $this->getMonthFromAbbr($match[2]);
            }
            if (isset($match[3])) {
                $array['day'] = $match[3];
            }
            if (isset($match[4])) {
                $type = 'wallclock';
                $time = $match[4];
                $lastChar = substr($time, -1);
                if ($lastChar == 'w') {
                    $time = substr($time, 0, -1);
                } elseif ($lastChar == 's') {
                    $type = 'standard';
                    $time = substr($time, 0, -1);
                } elseif ($lastChar == 'u' || $lastChar == 'z' || $lastChar == 'g') {
                    $type = 'universal';
                    $time = substr($time, 0, -1);
                }
                $array['time'] = array('type' => $type, 'seconds' => $this->timeStrToSeconds($time));
            }
        } else {
            throw new AgaviException('unknown date format: "' . $date . '"');
        }

        return $array;
    }
}
