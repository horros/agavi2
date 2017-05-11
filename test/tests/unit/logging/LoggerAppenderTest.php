<?php
namespace Agavi\Tests\Unit\Logging;

use Agavi\Core\Context;
use Agavi\Logging\LoggerAppender;
use Agavi\Logging\LoggerLayout;
use Agavi\Logging\LoggerMessage;
use Agavi\Testing\UnitTestCase;

class Sample2Layout extends LoggerLayout
{
    public function format(LoggerMessage $message)
    {
    }
}

class SampleAppender extends LoggerAppender
{
    public function initialize(Context $context, array $params = array())
    {
    }
    public function shutdown()
    {
    }
    public function write(LoggerMessage $message)
    {
    }
}

class LoggerAppenderTest extends UnitTestCase
{
    public function testGetSetLayout()
    {
        $a = new SampleAppender();
        $this->assertNull($a->getLayout());
        $l = new Sample2Layout();
        $a_test = $a->setLayout($l);
        $this->assertSame($a, $a_test);
        $this->assertEquals($l, $a->getLayout());
    }
}
