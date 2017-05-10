<?php
namespace Agavi\Testing\Unit\Database;
use Agavi\Testing\UnitTestCase;

/**
 * @runTestsInSeparateProcesses
 * @agaviIsolationEnvironment testing-use_database_on
 */
class DatabaseManagerTest extends UnitTestCase
{
	private $_dbm = null;
	
	public function setUp()
	{
		$this->_dbm = $this->getContext()->getDatabaseManager();
	}

	public function tearDown()
	{
		$this->_dbm = null;
	}

	public function testInitialization()
	{
		$this->assertInstanceOf('Agavi\\Database\\DatabaseManager', $this->_dbm);
	}

}
?>