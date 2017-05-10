<?php
namespace Agavi\Tests\Unit\Validator;
use Agavi\Request\RequestDataHolder;
use Agavi\Testing\UnitTestCase;
use Agavi\View\View;

class SampleView extends View
{
	public function execute(RequestDataHolder $rd) {}
}

class ViewTest extends UnitTestCase
{
	private
		$_v = null, 
		$_r = null;

	public function setUp()
	{
		$ctx = $this->getContext();
		$ctx->initialize();
		$request = $ctx->getRequest();

		$this->_v = new SampleView();
		$this->_v->initialize($ct = $ctx->getDispatcher()->createExecutionContainer('Test', 'Test'));
		$this->_r = $ct->getResponse();
	}

	public function testInitialize()
	{
		$ctx = $this->getContext();
		$v = $this->_v;

		$ctx_test = $v->getContext();
		$this->assertSame($ctx, $ctx_test);
	}


}
?>