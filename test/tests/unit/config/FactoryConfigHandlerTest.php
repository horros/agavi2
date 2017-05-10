<?php
namespace Agavi\Tests\Unit\Config;
use Agavi\Config\Config;
use Agavi\Config\FactoryConfigHandler;
use Agavi\Core\Context;
use Agavi\Dispatcher\ExecutionContainer;
use Agavi\Filter\ControllerFilterInterface;
use Agavi\Filter\FilterChain;
use Agavi\Filter\GlobalFilterInterface;
use Agavi\Filter\SecurityFilterInterface;
use Agavi\User\SecurityUserInterface;

require_once(__DIR__ . '/ConfigHandlerTestBase.php');

class FCHTestBase
{
	public $context,
	       $params,
	       $suCalled;
	public function initialize($ctx, array $params = array())
	{
		$this->context = $ctx;
		$this->params = $params;
	}
	public final function getContext()
	{
		return $this->context;
	}
	public function startup()
	{
		$this->suCalled = true;
	}
}

class FCHTestExecutionContainer extends FCHTestBase {}
class FCHTestDispatcher         extends FCHTestBase {}
	
class FCHTestDispatchFilter     implements GlobalFilterInterface {
	public function executeOnce(FilterChain $filterChain, ExecutionContainer $container) {}
	public function execute(FilterChain $filterChain, ExecutionContainer $container) {}
	public final function getContext() {}
	public function initialize(Context $context, array $parameters = array()) {}
}

class FCHTestExecutionFilter    implements ControllerFilterInterface {
	public function executeOnce(FilterChain $filterChain, ExecutionContainer $container) {}
	public function execute(FilterChain $filterChain, ExecutionContainer $container) {}
	public final function getContext() {}
	public function initialize(Context $context, array $parameters = array()) {}
}

class FCHTestFilterChain        extends FCHTestBase {}
class FCHTestLoggerManager      extends FCHTestBase {}
class FCHTestRequest            extends FCHTestBase {}
class FCHTestResponse           extends FCHTestBase {}
class FCHTestRouting            extends FCHTestBase {}
class FCHTestStorage            extends FCHTestBase {}
class FCHTestTranslationManager extends FCHTestBase {}
class FCHTestValidationManager  extends FCHTestBase {}
class FCHTestDBManager          extends FCHTestBase {}

class FCHTestSecurityFilter     implements ControllerFilterInterface, SecurityFilterInterface {
	public function executeOnce(FilterChain $filterChain, ExecutionContainer $container) {}
	public function execute(FilterChain $filterChain, ExecutionContainer $container) {}
	public final function getContext() {}
	public function initialize(Context $context, array $parameters = array()) {}
}
class FCHTestUser               extends FCHTestBase implements SecurityUserInterface
{
	public function addCredential($credential) {}
	public function clearCredentials() {}
	public function hasCredentials($credential) {}
	public function isAuthenticated() {}
	public function removeCredential($credential) {}
	public function setAuthenticated($authenticated) {}
}

class FactoryConfigHandlerTest extends ConfigHandlerTestBase
{
	protected		$conf;

	protected		$factories;

	protected		$databaseManager,
							$request,
							$storage,
							$translationManager,
							$user,
							$loggerManager,
							$dispatcher,
							$routing,
							$response;

	public function setUp()
	{
		$this->conf = Config::toArray();
		$this->factories = array();
	}

	public function tearDown()
	{
		Config::clear();
		Config::fromArray($this->conf);
	}

	public function testFactoryConfigHandler()
	{
		$FCH = new FactoryConfigHandler();

		$paramsExpected = array('p1' => 'v1', 'p2' => 'v2');

		Config::set('core.use_database', true);
		Config::set('core.use_logging', true);
		Config::set('core.use_security', true);
		$document = $this->parseConfiguration(
			Config::get('core.config_dir') . '/tests/factories.xml',
			Config::get('core.agavi_dir') . '/config/xsl/factories.xsl'
		);
		$this->includeCode($FCH->execute($document));


		// Execution container
		$this->assertSame(
			array(
				'class' => 'Agavi\\Tests\\Unit\\Config\\FCHTestExecutionContainer',
				'parameters' => $paramsExpected,
			),
			$this->factories['execution_container']
		);

		// Dispatch filter
		$this->assertSame(
			array(
				'class' => 'Agavi\\Tests\\Unit\\Config\\FCHTestDispatchFilter',
				'parameters' => $paramsExpected,
			),
			$this->factories['dispatch_filter']
		);

		// Execution filter
		$this->assertSame(
			array(
				'class' => 'Agavi\\Tests\\Unit\\Config\\FCHTestExecutionFilter',
				'parameters' => $paramsExpected,
			),
			$this->factories['execution_filter']
		);

		// Filter chain
		$this->assertSame(
			array(
				'class' => 'Agavi\\Tests\\Unit\\Config\\FCHTestFilterChain',
				'parameters' => $paramsExpected,
			),
			$this->factories['filter_chain']
		);

		// Security filter
		$this->assertSame(
			array(
				'class' => 'Agavi\\Tests\\Unit\\Config\\FCHTestSecurityFilter',
				'parameters' => $paramsExpected,
			),
			$this->factories['security_filter']
		);

		// Response
		$this->assertSame(
			array(
				'class' => 'Agavi\\Tests\\Unit\\Config\\FCHTestResponse',
				'parameters' => $paramsExpected,
			),
			$this->factories['response']
		);
		

		// Validation Manager
		$this->assertSame(
			array(
				'class' => 'Agavi\\Tests\\Unit\\Config\\FCHTestValidationManager',
				'parameters' => $paramsExpected,
			),
			$this->factories['validation_manager']
		);

		$this->assertInstanceOf('Agavi\\Tests\\Unit\\Config\\FCHTestDBManager', $this->databaseManager);
		$this->assertSame($this, $this->databaseManager->context);
		$this->assertSame($paramsExpected, $this->databaseManager->params);
		$this->assertTrue($this->databaseManager->suCalled);

		$this->assertInstanceOf('Agavi\\Tests\\Unit\\Config\\FCHTestRequest', $this->request);
		$this->assertSame($this, $this->request->context);
		$this->assertSame($paramsExpected, $this->request->params);
		$this->assertTrue($this->request->suCalled);

		$this->assertInstanceOf('Agavi\\Tests\\Unit\\Config\\FCHTestStorage', $this->storage);
		$this->assertSame($this, $this->storage->context);
		$this->assertSame($paramsExpected, $this->storage->params);
		$this->assertTrue($this->storage->suCalled);

		$this->assertInstanceOf('Agavi\\Tests\\Unit\\Config\\FCHTestTranslationManager', $this->translationManager);
		$this->assertSame($this, $this->translationManager->context);
		$this->assertSame($paramsExpected, $this->translationManager->params);
		$this->assertTrue($this->translationManager->suCalled);

		$this->assertInstanceOf('Agavi\\Tests\\Unit\\Config\\FCHTestUser', $this->user);
		$this->assertSame($this, $this->user->context);
		$this->assertSame($paramsExpected, $this->user->params);
		$this->assertTrue($this->user->suCalled);

		$this->assertInstanceOf('Agavi\\Tests\\Unit\\Config\\FCHTestLoggerManager', $this->loggerManager);
		$this->assertSame($this, $this->loggerManager->context);
		$this->assertSame($paramsExpected, $this->loggerManager->params);
		$this->assertTrue($this->loggerManager->suCalled);

		$this->assertInstanceOf('Agavi\\Tests\\Unit\\Config\\FCHTestDispatcher', $this->dispatcher);
		$this->assertSame($this, $this->dispatcher->context);
		$this->assertSame($paramsExpected, $this->dispatcher->params);

		$this->assertInstanceOf('Agavi\\Tests\\Unit\\Config\\FCHTestRouting', $this->routing);
		$this->assertSame($this, $this->routing->context);
		$this->assertSame($paramsExpected, $this->routing->params);
		$this->assertTrue($this->routing->suCalled);
	}

}
?>