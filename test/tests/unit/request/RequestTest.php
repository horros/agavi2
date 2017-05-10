<?php
namespace Agavi\Tests\Unit\Request;

use Agavi\Request\Request;
use Agavi\Testing\UnitTestCase;

class SampleRequest extends Request
{
	public function shutdown() {}
}

class RequestTest extends UnitTestCase
{
	/**
	 * @var Request
	 */
	private $_r = null;

	public function setUp()
	{
		$this->_r = new SampleRequest();
		$this->_r->initialize($this->getContext());
	}

	public function testgetInstance()
	{
		$ctx = $this->getContext();
		$ctx_test = $this->_r->getContext();
		$this->assertSame($ctx, $ctx_test);
	}

	public function testSetGetMethod()
	{
		$this->assertNull($this->_r->getMethod());
		$this->_r->setMethod('Get');
		$this->assertEquals('Get', $this->_r->getMethod());
	}

	public function testGetModuleAccessor()
	{
		$this->assertEquals('module', $this->_r->getParameter('module_accessor'));
		$this->_r->initialize($this->getContext(), array('module_accessor' => 'moduleTest'));
		$this->assertEquals('moduleTest', $this->_r->getParameter('module_accessor'));
	}

	public function testGetControllerAccessor()
	{
		$this->assertEquals('controller', $this->_r->getParameter('controller_accessor'));
		$this->_r->initialize($this->getContext(), array('controller_accessor' => 'controllerTest'));
		$this->assertEquals('controllerTest', $this->_r->getParameter('controller_accessor'));
	}
}
?>