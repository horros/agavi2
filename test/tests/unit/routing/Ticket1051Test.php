<?php
namespace Agavi\Tests\Unit\Routing;

use Agavi\Core\Context;
use Agavi\Testing\PhpUnitTestCase;
use Agavi\Testing\Routing\TestingWebRouting;


class Ticket1051Test extends PhpUnitTestCase
{
	/**
	 * @var TestingWebRouting
	 */
	protected $routing;
	protected $parameters = array('enabled' => true);
	
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
	}
	
	public function setUp()
	{
		// otherwise, the full URI wouldn't work
		$_SERVER['REQUEST_URI'] = '/index.php';
		$_SERVER['SCRIPT_NAME'] = '/index.php';
		
		$this->routing = new TestingWebRouting();
		$this->routing->initialize(Context::getInstance(null), $this->parameters);
		$this->routing->startup();
	}
	
	public function testCallbackOnGenerateCanSetOptions()
	{
		$this->assertEquals('http://www.agavi.org/index.php/ticket_1051', $this->routing->gen('ticket_1051'));
	}
}


?>