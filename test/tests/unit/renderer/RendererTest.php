<?php
namespace Agavi\Tests\Unit\Renderer;

use Agavi\Renderer\Renderer;
use Agavi\Testing\UnitTestCase;
use Agavi\View\TemplateLayer;

class TRTestSampleRenderer extends Renderer
{
	public function render(TemplateLayer $layer, array &$attributes = array(), array &$slots = array(), array &$moreAssigns = array())
	{
	}
}

class RendererTest extends UnitTestCase
{
	/**
	 * @var Renderer
	 */
	protected $_r = null;

	protected $_v = null;

	public function setUp()
	{
		$this->_r = new TRTestSampleRenderer();
		$this->_r->initialize($this->getContext());
	}

	public function testGetContext()
	{
		$c1 = $this->getContext();
		$c2 = $this->_r->getContext();
		$this->assertSame($c1, $c2);
	}
}
?>