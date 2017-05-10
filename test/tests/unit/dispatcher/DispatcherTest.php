<?php
namespace Agavi\Testing\Unit\Dispatcher;

use Agavi\Config\Config;
use Agavi\Core\Context;
use Agavi\Dispatcher\Dispatcher;
use Agavi\Exception\AgaviException;
//use Agavi\Exception\FileNotFoundException;
use Agavi\Testing\UnitTestCase;

class TestDispatcher extends Dispatcher
{
	public function redirect($to)
	{
		throw new \Exception('N/A');
	}
}

/**
 * runTestsInSeparateProcesses
 */
class DispatcherTest extends UnitTestCase
{
	/** @var Dispatcher */
	protected $_dispatcher = null;

	/** @var Context */
	protected $_context = null;
	public function setUp()
	{
		// ReInitialize the Context between tests to start fresh
		$this->_context = $this->getContext();
		$this->_dispatcher = $this->_context->getDispatcher();
		$this->_dispatcher->initialize($this->_context, array());
	}

	public function testNewController()
	{
		$controller = $this->_dispatcher;
		$this->assertInstanceOf('Agavi\Dispatcher\Dispatcher', $controller);
		$this->assertInstanceOf('Agavi\Core\Context', $controller->getContext());
		$ctx1 = $controller->getContext();
		$ctx2 = $this->getContext();
		$this->assertSame($ctx1, $ctx2);
	}

	public function testControllerFileExists()
	{
		// controllerExists actually checks the filesystem, 
		$this->assertTrue(file_exists(Config::get('core.app_dir') . '/modules/ControllerTests/controllers/ControllerTestController.class.php'));
		$this->assertFalse(file_exists(Config::get('core.app_dir') . '/modules/ControllerTests/controllers/BunkController.class.php'));
		$this->assertFalse(file_exists(Config::get('core.app_dir') . '/modules/Bunk/controllers/BunkController.class.php'));
		$dispatcher = $this->_dispatcher;
		$this->assertEquals(Config::get('core.app_dir') . '/modules/ControllerTests/controllers/ControllerTestController.class.php',$dispatcher->checkControllerFile('ControllerTests', 'ControllerTest'));
		$this->assertFalse($dispatcher->checkControllerFile('ControllerTests', 'Bunk'), 'controllerFileExists did not return false for non-existing controller in existing module');
		$this->assertFalse($dispatcher->checkControllerFile('Bunk', 'Bunk'), 'controllerFileExists did not return false for non-existing controller in non-existing module');
	}

	public function testGetControllerFromModule()
	{
		// TODO: check all other existing naming schemes for controllers

		$controller = $this->_dispatcher->createControllerInstance('ControllerTests', 'ControllerTest');
		$this->assertInstanceOf('Sandbox\Modules\ControllerTests\Controllers\ControllerTestController', $controller);
		$this->assertInstanceOf('Agavi\Controller\Controller', $controller);

	}

	/**
	 * @expectedException \Agavi\Exception\FileNotFoundException
	 */
	public function testGetInvalidControllerFromModule() {
		$this->_dispatcher->createControllerInstance('ControllerTests', 'NonExistent');
	}

	public function testGetContext()
	{
		$this->assertSame($this->getContext(), $this->getContext()->getDispatcher()->getContext());
	}

	public function testCreateViewInstance()
	{
		$controller = $this->_dispatcher;
		$this->assertInstanceOf(
			'Sandbox\Modules\ControllerTests\Views\ControllerTestSuccessView',
			$controller->createViewInstance('ControllerTests', 'ControllerTestSuccess')
		);
		$this->assertInstanceOf(
			'Sandbox\Modules\ControllerTests\Views\ControllerTestErrorView',
			$controller->createViewInstance('ControllerTests', 'ControllerTestError')
		);
	}

	public function testModelExists()
	{
		$controller = $this->_dispatcher;
		$this->assertTrue($controller->modelExists('ControllerTests', 'ControllerTest'));
		$this->assertFalse($controller->modelExists('Test', 'Bunk'));
		$this->assertFalse($controller->modelExists('Bunk', 'Bunk'));
	}

	public function testModuleExists()
	{
		$controller = $this->_dispatcher;
		$this->assertTrue($controller->moduleExists('ControllerTests'));
		$this->assertFalse($controller->moduleExists('Bunk'));
	}

	public function testViewExists()
	{
		$controller = $this->_dispatcher;
		$this->assertTrue($controller->viewExists('ControllerTests', 'ControllerTestSuccess'));
		$this->assertFalse($controller->viewExists('Test', 'Bunk'));
		$this->assertFalse($controller->viewExists('Bunk', 'Bunk'));
	}



	public function testGetOutputTypeInfo()
	{
		$controller = $this->_dispatcher;

		$info_ex = array(
			'http_headers' => array(
				'Content-Type' => 'text/html; charset=UTF-8',
			),
		);

		$info = $controller->getOutputType();
		$this->assertSame($info_ex, $info->getParameters());

		$info_ex = array(
		);
		$info = $controller->getOutputType('controllerTest');
		$this->assertSame($info_ex, $info->getParameters());

		try {
			$controller->getOutputType('nonexistant');
			$this->fail('Expected AgaviException not thrown!');
		} catch(AgaviException $e) {
		}
	}


/* 
	// TODO: moved to AgaviResponse
	public function testsetContentType()
	{
		$controller = Context::getInstance('test')->getController();
		$ctype = $controller->getContentType();
		$controller->setContentType('image/jpeg');
		$this->assertEquals($controller->getContentType(), 'image/jpeg');
		$controller->setContentType($ctype);
	}
	
	public function testclearHTTPHeaders()
	{
		$controller = Context::getInstance('test')->getController();
		$controller->clearHTTPHeaders();
		$this->assertEquals($controller->getHTTPHeaders(), array());
	}
	
	public function testgetHTTPHeader()
	{
		$controller = Context::getInstance('test')->getController();
		$this->assertEquals($controller->getHTTPHeader('unset'), null);
	}

	public function testhasHTTPHeader()
	{
		$controller = Context::getInstance('test')->getController();
		$controller->clearHTTPHeaders();
		$controller->setHTTPHeader('testme', 'whatever');
		$this->assertTrue($controller->hasHTTPHeader('testme'));
		$this->assertFalse($controller->hasHTTPHeader('iamnotset'));
	}
	
	public function testsetHTTPHeader()
	{
		$controller = Context::getInstance('test')->getController();
		$controller->setHTTPHeader('sometest', 'fubar');
		$this->assertEquals($controller->getHTTPHeader('sometest'), array('fubar'));
		$controller->setHTTPHeader('sometest', 'foo');
		$this->assertEquals($controller->getHTTPHeader('sometest'), array('foo'));
		$controller->setHTTPHeader('sometest', 'bar', false);
		$this->assertEquals($controller->getHTTPHeader('sometest'), array('foo', 'bar'));
		$controller->setHTTPHeader('multiple', array('first', 'second'));
		$this->assertEquals($controller->getHTTPHeader('multiple'), array('first', 'second'));
	}
	
	public function testgetHTTPStatusCode()
	{
		$controller = Context::getInstance('test')->getController();
		$this->assertEquals($controller->getHTTPStatusCode(), null);
	}
	
	public function testsetHTTPStatusCode()
	{
		$controller = Context::getInstance('test')->getController();
		$controller->setHTTPStatusCode('404');
		$this->assertEquals($controller->getHTTPStatusCode(), '404');
		$controller->setHTTPStatusCode(403);
		$this->assertEquals($controller->getHTTPStatusCode(), '403');
		$controller->setHTTPStatusCode('123');
		$this->assertEquals($controller->getHTTPStatusCode(), '403');
		$controller->setHTTPStatusCode(123);
		$this->assertEquals($controller->getHTTPStatusCode(), '403');
	}
	
	// TODO: moved to routing
	function testgenURL()
	{
		$routing = Context::getInstance('test')->getRouting();
		$this->assertEquals($controller->genURL('index.php', array('foo' =>'bar')), 'index.php?foo=bar');
		$this->assertEquals($controller->genURL(null, array('foo' =>'bar')), $_SERVER['SCRIPT_NAME'] . '?foo=bar');
		$this->assertEquals($controller->genURL(array('foo' =>'bar'), 'index.php'), 'index.php?foo=bar');
		$this->assertEquals($controller->genURL(array('foo' =>'bar')), $_SERVER['SCRIPT_NAME'] . '?foo=bar');
	}
*/
}

?>