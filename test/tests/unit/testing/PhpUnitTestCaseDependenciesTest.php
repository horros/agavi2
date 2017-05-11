<?php
namespace Agavi\Tests\Unit\Testing;

use Agavi\Testing\PhpUnitTestCase;

class PhpUnitTestCaseDependenciesTestDummy extends \SandboxTestingChildClass
{

}

/**
 * @runTestsInSeparateProcesses
 */
class PhpUnitTestCaseDependenciesTest extends PhpUnitTestCase
{
    /**
     * @preserveGlobalState enabled
     */
    public function testDependenciesAreLoadedWithGlobalState()
    {
        // this test is successful as soon as the test runs.
        // It would fail way before if any of the dependencies
        // from SandboxTestingChildClass didn't load
        $this->assertTrue(true);
    }
    
    /**
     * @preserveGlobalState disabled
     */
    public function testDependenciesAreLoadedWithoutGlobalState()
    {
        // this test is successful as soon as the test runs.
        // It would fail way before if any of the dependencies
        // from SandboxTestingChildClass didn't load
        $this->assertTrue(true);
    }
}
