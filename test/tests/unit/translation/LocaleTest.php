<?php
namespace Agavi\Tests\Unit\Translation;

use Agavi\Testing\UnitTestCase;

class LocaleTest extends UnitTestCase
{
    
    
    public function testGetCalendarDayWide()
    {
        $locale = $this->getContext()->getTranslationManager()->getLocale('en_US');
        
        $expected = array(
            1 => 'Sunday',
            2 => 'Monday',
            3 => 'Tuesday',
            4 => 'Wednesday',
            5 => 'Thursday',
            6 => 'Friday',
            7 => 'Saturday',
        );
        $this->assertEquals($expected, $locale->getCalendarDaysWide('gregorian'));
    }
}
