<?php
namespace Agavi\Testing\Unit\Date;

use Agavi\Core\Context;
use Agavi\Testing\PhpUnitTestCase;
use Agavi\Translation\DateFormatter;
use Agavi\Translation\TranslationManager;

abstract class BaseCalendarTest extends PhpUnitTestCase
{
    /** @var TranslationManager */
    protected $tm;

    protected function date($y, $m, $d, $hr = 0, $min = 0, $sec = 0)
    {
        $cal = $this->tm->createCalendar();
        $cal->clear();
        $cal->set(1900 + $y, $m, $d, $hr, $min, $sec); // Add 1900 to follow java.util.Date protocol
        $dt = $cal->getTime();
        return $dt;
    }

    // TODO: implement this stuff
    protected function dateToString($time)
    {
        if (is_numeric($time)) {
            $cal = $this->tm->createCalendar();
            $cal->setTime($time);
            $time = $cal;
        }
        $time->getTime();
        $format = new DateFormatter('EEE MMM dd HH:mm:ss zzz yyyy');
        return $format->format($time, 'gregorian', $this->tm->getCurrentLocale());
    }

    public function setUp()
    {
        $this->tm = Context::getInstance()->getTranslationManager();
    }
}
