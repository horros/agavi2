<?php
namespace Agavi\Tests\Unit\Config;
use Agavi\Config\Config;
use Agavi\Config\FilterConfigHandler;
use Agavi\Core\Context;
use Agavi\Controller\ExecutionContainer;
use Agavi\Filter\FilterChain;
use Agavi\Filter\FilterInterface;

require_once(__DIR__ . '/ConfigHandlerTestBase.php');

class FCHTestFilter1 implements FilterInterface
{
	public $context;
	public $params;

	public function initialize(Context $ctx, array $params = array())
	{
		$this->context = $ctx;
		$this->params = $params;
	}

	public function executeOnce(FilterChain $filterChain, ExecutionContainer $container) {}
	public function execute(FilterChain $filterChain, ExecutionContainer $container) {}
	public final function getContext() {}
}

class FCHTestFilter2 extends FCHTestFilter1
{
}

class AgaviFilterConfigHandlerTest extends ConfigHandlerTestBase
{
	protected $context;

	public function setUp()
	{
		$this->context = $this->getContext();
	}

	public function testFilterConfigHandler()
	{
		$ctx = $this->getContext();
		
		$FCH = new FilterConfigHandler();
		
		$document = $this->parseConfiguration(
			Config::get('core.config_dir') . '/tests/filters.xml',
			Config::get('core.agavi_dir') . '/config/xsl/filters.xsl'
		);

		$filters = array();

		$file = $this->getIncludeFile($FCH->execute($document));
		include($file);
		unlink($file);

		$this->assertCount(2, $filters);

		$this->assertInstanceOf('Agavi\\Tests\\Unit\\Config\\FCHTestFilter1', $filters['filter1']);
		$this->assertSame(array('comment' => true), $filters['filter1']->params);
		$this->assertSame($ctx, $filters['filter1']->context);

		$this->assertInstanceOf('Agavi\\Tests\\Unit\\Config\\FCHTestFilter2', $filters['filter2']);
		$this->assertSame(array(), $filters['filter2']->params);
		$this->assertSame($ctx, $filters['filter2']->context);
	}
}
?>