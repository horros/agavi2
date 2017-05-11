<?php
namespace Agavi\Tests\Unit\Validator;

use Agavi\Testing\UnitTestCase;
use Agavi\Validator\ValidationReport;

class ValidationReportTest extends UnitTestCase
{
    private $_context = null;
    /** @var ValidationReport */
    private $_report = null;
    
    public function setUp()
    {
        $this->_context = $this->getContext();
        $this->_report = new ValidationReport();
    }

    public function tearDown()
    {
        $this->_context = null;
    }
    
    public function testDependTokensInitiallyEmpty()
    {
        $this->assertEquals(array(), $this->_report->getDependTokens());
    }
    
    public function testSetGetDependTokens()
    {
        $tokens = array('token1' => true, 'token2' => true);
        $this->_report->setDependTokens($tokens);
        $this->assertEquals($tokens, $this->_report->getDependTokens());
    }
    
    public function testHasDependToken()
    {
        $tokens = array('token1' => true, 'token2' => true);
        $this->_report->setDependTokens($tokens);
        $this->assertTrue($this->_report->hasDependToken('token1'));
        $this->assertTrue($this->_report->hasDependToken('token2'));
        $this->assertFalse($this->_report->hasDependToken('token3'));
    }
}
