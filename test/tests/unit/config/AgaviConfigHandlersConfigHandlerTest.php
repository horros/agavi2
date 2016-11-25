<?php
namespace Agavi\Tests\Unit\Config;

use Agavi\Config\Config;
use Agavi\Config\ConfigHandler;
use Agavi\Config\ConfigHandlersConfigHandler;
use Agavi\Tests\Unit\Config\ConfigHandlerTestBase;
use Agavi\Util\Toolkit;

require_once(__DIR__ . '/ConfigHandlerTestBase.php');

class CHCHTestHandler extends ConfigHandler
{
	public	$validationFile,
					$parser,
					$parameters;

	public function initialize($vf = null, $parser = null, $params = array())
	{
		$this->validationFile = $vf;
		$this->parser = $parser;
		$this->parameters = $params;
	}

	public function execute($config, $context = null)
	{
	}
}

class AgaviConfigHandlersConfigHandlerTest extends ConfigHandlerTestBase
{

	public function testConfigHandlersConfigHandler()
	{
		$hf = Toolkit::normalizePath(Config::get('core.config_dir') . '/routing.xml');
		$CHCH = new ConfigHandlersConfigHandler();

		$document = $this->parseConfiguration(
			Config::get('core.config_dir') . '/tests/config_handlers.xml',
			Config::get('core.agavi_dir') . '/config/xsl/config_handlers.xsl'
		);

		$file = $this->getIncludeFile($CHCH->execute($document));
		$handlers = include($file);
		unlink($file);

		$this->assertCount(1, $handlers);
		$this->assertTrue(isset($handlers[$hf]));
		$this->assertSame('CHCHTestHandler', $handlers[$hf]['class']);
		$this->assertSame(Config::get('core.agavi_dir') . '/config/xsd/routing.xsd', $handlers[$hf]['validations']['single']['transformations_after']['xml_schema'][0]);
		$this->assertSame(array('foo' => 'bar', 'dir' => Config::get('core.agavi_dir')) , $handlers[$hf]['parameters']);
	}

}
?>