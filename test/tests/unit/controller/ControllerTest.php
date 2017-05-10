<?php
namespace Agavi\Tests\Unit\Controller;

use Agavi\Controller\Controller;
use Agavi\Request\RequestDataHolder;
use Agavi\Testing\UnitTestCase;
use Agavi\Util\ParameterHolder;

class SampleController extends Controller {
	public function execute(ParameterHolder $parameters)
	{
	}
}

class ControllerTest extends UnitTestCase
{
	private $_controller = null;

	public function setUp()
	{
		$this->_controller = new SampleController();
		$this->_controller->initialize($this->getContext()->getDispatcher()->createExecutionContainer('Foo', 'Bar'));
	}

	public function tearDown()
	{
		$this->_controller = null;
	}

	public function testgetContext()
	{
		$context = $this->getContext();
		$controllerContext = $this->_controller->getContext();
		$this->assertSame($context, $controllerContext);
	}

	public function testCredentials()
	{
		$this->assertNull($this->_controller->getCredentials());
	}

	public function testgetDefaultViewName()
	{
		$this->assertEquals('Input', $this->_controller->getDefaultViewName());
	}

	public function testhandleError()
	{
		$this->assertEquals('Error', $this->_controller->handleError(new RequestDataHolder()));
	}

	public function testisSecure()
	{
		$this->assertFalse($this->_controller->isSecure());
	}

	public function testvalidate()
	{
		$this->assertTrue($this->_controller->validate(new RequestDataHolder()));
	}
}
?>