<?php
namespace Agavi\Tests\Unit\Testing;
use Agavi\Config\Config;
use Agavi\Testing\PhpUnitTestCase;

/**
 * @runTestsInSeparateProcesses
 * @agaviIsolationEnvironment testing.testIsolation	
 * @agaviIsolationDefaultContext web-isolated
 */
class PhpUnitTestCaseTest extends PhpUnitTestCase
{
	/**
	 * Constructs a test case with the given name.
	 *
	 * @param  string $name
	 * @param  array  $data
	 * @param  string $dataName
	 */
	public function __construct($name = NULL, array $data = array(), $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->setIsolationEnvironment('testing.testIsolation'); // equivalent to the annotation @AgaviIsolationEnvironment on the testcase class
	}
	
	public function testIsolationEnvironment()
	{
		$this->assertEquals('testing.testIsolation', Config::get('testing.environment'));
	}
	
	/**
	 * @agaviIsolationEnvironment testing.testIsolationAnnotated
	 */
	public function testIsolationEnvironmentAnnotated()
	{
		$this->assertEquals('testing.testIsolationAnnotated', Config::get('testing.environment'));
	}
	
	public function testIsolationDefaultContext()
	{
		$this->assertEquals('web-isolated', Config::get('core.default_context'));
	}
	
	/**
	 * @agaviIsolationDefaultContext web-isolated-annotated-method
	 */
	public function testIsolationDefaultContextAnnotated()
	{
		$this->assertEquals('web-isolated-annotated-method', Config::get('core.default_context'));
	}
	
	/**
	 * @preserveGlobalState enabled
	 */
	public function testPreserveGlobalStateOnWorks() {
		// this test just needs to run to signal success
		$this->assertTrue(true);
	}

	/**
	 * @preserveGlobalState disabled
	 */
	public function testPreserveGlobalStateOffWorks() {
		// this test just needs to run to signal success
		$this->assertTrue(true);
	}
	
}

?>