<?php
namespace Agavi\Tests\Unit\Config;
use Agavi\Config\ConfigHandler;
use Agavi\Testing\UnitTestCase;

class MyTestConfigHandler extends ConfigHandler
{
	public function execute($config, $context = null)
	{
		return '';
	}
}

class AgaviConfigHandlerTest extends UnitTestCase
{
	protected $ch = null;
	public function setUp()
	{
		$this->ch = new MyTestConfigHandler();
		$this->ch->initialize('MyValidationFile.mvf');
	}

	public function tearDown()
	{
		$this->ch = null;
	}

	public function testGetValidationFile()
	{
		$this->assertSame('MyValidationFile.mvf', $this->ch->getValidationFile());
	}

}
