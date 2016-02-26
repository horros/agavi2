<?php
namespace Agavi\Tests\Unit\Config;

use Agavi\Config\Config;
use Agavi\Config\XmlConfigParser;
use Agavi\Testing\UnitTestCase;

abstract class ConfigHandlerTestBase extends UnitTestCase
{
	protected function getIncludeFile($code)
	{
		$file = tempnam(\Agavi\Config\Config::get('core.cache_dir'), 'cht');
		file_put_contents($file, $code);
		return $file;
	}

	protected function includeCode($code, $env = array())
	{
		extract($env);
		$file = $this->getIncludeFile($code);
		$ret = include($file);
		unlink($file);
		return $ret;
	}
	
	protected function parseConfiguration($configFile, $xslFile = null, $environment = null) {
		return XmlConfigParser::run(
			$configFile,
			$environment ? $environment : Config::get('core.environment'),
			'',
			array(
				XmlConfigParser::STAGE_SINGLE => $xslFile ? array($xslFile) : array(),
				XmlConfigParser::STAGE_COMPILATION => array(),
			),
			array(
				XmlConfigParser::STAGE_SINGLE => array(
					XmlConfigParser::STEP_TRANSFORMATIONS_BEFORE => array(),
					XmlConfigParser::STEP_TRANSFORMATIONS_AFTER => array(),
				),
				XmlConfigParser::STAGE_COMPILATION => array(
					XmlConfigParser::STEP_TRANSFORMATIONS_BEFORE => array(),
					XmlConfigParser::STEP_TRANSFORMATIONS_AFTER => array()
				),
			)
		);
		
	}
}
