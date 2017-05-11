<?php
namespace Agavi\Tests\Unit\Routing;

use Agavi\Config\Config;
use Agavi\Core\Context;
use Agavi\Exception\AgaviException;
use Agavi\Testing\PhpUnitTestCase;
use Agavi\Testing\Routing\TestingRouting;

/**
 * Class RoutingTest
 * @package Agavi\Tests\Unit\Routing
 *
 * @runInSeparateProcess true
 */
class RoutingTest extends PhpUnitTestCase
{
    /**
     * @var TestingRouting
     */
    protected $routing;
    protected $parameters = array('enabled' => true);
    
    /**
     * Constructs a test case with the given name.
     *
     * @param  string $name
     * @param  array  $data
     * @param  string $dataName
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }
    
    public function setUp()
    {
        $this->routing = new TestingRouting();
        $this->routing->initialize(Context::getInstance(null), $this->parameters);
        $this->routing->startup();
    }
    
    public function testExecuteDisabled()
    {
        $this->routing->setParameter('enabled', false);
        $container = $this->routing->execute();
        $this->assertEquals(null, $container->getControllerName());
        $this->assertEquals(null, $container->getModuleName());
    }
    
    public function testExecuteEmptyInput()
    {
        $this->routing->setInput('');
        $container = $this->routing->execute();
        $this->assertEquals(Config::get('controllers.error_404_controller'), $container->getControllerName());
        $this->assertEquals(Config::get('controllers.error_404_module'), $container->getModuleName());
        $this->assertEquals(array(), Context::getInstance(null)->getRequest()->getAttribute('matched_routes', 'org.agavi.routing'));
    }
    
    public function testExecuteSimpleInput()
    {
        $this->routing->setInput('/');
        $container = $this->routing->execute();
        $this->assertEquals(Config::get('controllers.default_controller'), $container->getControllerName());
        $this->assertEquals(Config::get('controllers.default_module'), $container->getModuleName());
        $this->assertEquals(array('index'), Context::getInstance(null)->getRequest()->getAttribute('matched_routes', 'org.agavi.routing'));
    }
    
    public function testExecuteUserAuthenticated()
    {
        $ctx = Context::getInstance(null);
        $ctx->getUser()->setAuthenticated(true);
        $this->routing->setInput('/');
        $container = $this->routing->execute();
        $this->assertEquals('LoggedIn', $container->getControllerName());
        $this->assertEquals('Auth', $container->getModuleName());
        $this->assertEquals(array('user_logged_in'), $ctx->getRequest()->getAttribute('matched_routes', 'org.agavi.routing'));
        $ctx->getUser()->setAuthenticated(false);
    }
    
    public function testExecuteServer()
    {
        $_SERVER['routing_test'] = 'foo';
        $ctx = Context::getInstance(null);
        $this->routing->setInput('/');
        $this->routing->setRoutingSource('_SERVER', $_SERVER);
        $container = $this->routing->execute();
        $this->assertEquals('Matched', $container->getControllerName());
        $this->assertEquals('Server', $container->getModuleName());
        $this->assertEquals(array('server'), $ctx->getRequest()->getAttribute('matched_routes', 'org.agavi.routing'));
    }
    
    public function testExecuteRandomSource()
    {
        $data = array();
        $data['bar'] = 'foo';
        $ctx = Context::getInstance(null);
        $this->routing->setInput('/');
        $this->routing->setRoutingSource('testingsource', $data);
        $container = $this->routing->execute();
        $this->assertEquals('Matched', $container->getControllerName());
        $this->assertEquals('TestingSource', $container->getModuleName());
        $this->assertEquals(array('testingsource'), $ctx->getRequest()->getAttribute('matched_routes', 'org.agavi.routing'));
    }
    
    /*
	public function testExecuteNonexistantSource()
	{	
		$ctx = Context::getInstance(null);
		$this->routing->setInput('/');
		$container = $this->routing->execute();
		$this->assertEquals('Matched', $container->getControllerName());
		$this->assertEquals('TestingSource', $container->getModuleName());
		$this->assertEquals(array('testingsource'), $ctx->getRequest()->getAttribute('matched_routes', 'org.agavi.routing'));
	}*/

    public function testMatchWithParam()
    {
        $ctx = Context::getInstance(null);
        $this->routing->setInput('/withparam/5');
        $container = $this->routing->execute();
        $this->assertEquals(array('with_param'), Context::getInstance(null)->getRequest()->getAttribute('matched_routes', 'org.agavi.routing'));
        $this->assertEquals(5, $ctx->getRequest()->getRequestData()->getParameter('number'));
        $this->assertEquals('MatchedParam', $container->getControllerName());
        $this->assertEquals('TestWithParam', $container->getModuleName());
    }
    
    public function testMatchWithMultipleParams()
    {
        $ctx = Context::getInstance(null);
        $this->routing->setInput('/withmultipleparams/5/foo');
        $container = $this->routing->execute();
        $this->assertEquals(array('with_two_params'), Context::getInstance(null)->getRequest()->getAttribute('matched_routes', 'org.agavi.routing'));
        $this->assertEquals(5, $ctx->getRequest()->getRequestData()->getParameter('number'));
        $this->assertEquals('foo', $ctx->getRequest()->getRequestData()->getParameter('string'));
        $this->assertEquals('MatchedMultipleParams', $container->getControllerName());
        $this->assertEquals('TestWithParam', $container->getModuleName());
    }
    
    public function testOnNotMatched()
    {
        $this->routing->setInput('/callbacks/on_not_matched/callback_stopper');
        try {
            $container = $this->routing->execute();
        } catch (AgaviException $e) {
            $this->assertEquals('Not Matched', $e->getMessage());
        }
    }
    
    public function testNonMatchingCallback()
    {
        $this->routing->setInput('/callbacks/nonmatching_callback');
        $container = $this->routing->execute();
        $this->assertEquals(array('callbacks'), Context::getInstance(null)->getRequest()->getAttribute('matched_routes', 'org.agavi.routing'));
        $this->assertEquals(Config::get('controllers.error_404_module'), $container->getModuleName());
        $this->assertEquals(Config::get('controllers.error_404_controller'), $container->getControllerName());
    }
    
    public function testMatchingCallback()
    {
        $ctx = Context::getInstance(null);
        $this->routing->setInput('/callbacks/matching_callback');
        $container = $this->routing->execute();
        $this->assertEquals(array('callbacks', 'callbacks.matching_callback'), $ctx->getRequest()->getAttribute('matched_routes', 'org.agavi.routing'));
        $this->assertEquals('Callback', $container->getModuleName());
        $this->assertEquals('Matching', $container->getControllerName());
        $this->assertEquals('set', $ctx->getRequest()->getRequestData()->getParameter('callback'));
    }
    
    public function testOnNotMatchedStopper()
    {
        $this->routing->setInput('/callbacks/stopper');
        try {
            $container = $this->routing->execute();
        } catch (AgaviException $e) {
            $this->fail('The onNotMatched callback of the childroute should not get called');
        }
    }
    
    /**
     * @dataProvider dataParseRouteString
     */
    public function testParseRouteString($routeString, $expected)
    {
        $parsed = $this->routing->parseRouteString($routeString);
        $this->assertEquals($expected, $parsed);
    }
    
    public function dataParseRouteString()
    {
        return array(
            'escaped_balanced' => array(
                'static\(text(prefix{foo:1\(2\{3\}4\)5}postfix)',
                array(
                    '#static\(text(prefix(?P<foo>1(2{3}4)5)postfix)#',
                    'static(text(:foo:)',
                    array('foo' => array(
                        'pre'  => 'prefix',
                        'val'  => '',
                        'post' => 'postfix',
                        'is_optional' => false,
                    )),
                    0,
                )
            ),
            '#789' => array(
                '#static#with#quote',
                array(
                    '#\#static\#with\#quote#',
                    '#static#with#quote',
                    array(),
                    0,
                )
            ),
        );
    }
    
    public function testTicket263()
    {
        try {
            $this->routing->addRoute('rxp', array('name' => 'foo'));
            $this->routing->addRoute('rxp', array('name' => 'foo'), 'foo');
            $this->fail('succeeded in adding a route with the same name as a child');
        } catch (AgaviException $e) {
            $this->assertEquals('You are trying to overwrite a route but are not staying in the same hierarchy', $e->getMessage());
        }
    }
    
    public function testTicket764()
    {
        $this->routing->setInput('/test_ticket_764/dummy/child');
        $container = $this->routing->execute();
        $this->assertEquals('Default', $container->getModuleName());
        $this->assertEquals('Foo/Bar', $container->getControllerName());
    }
    
    public function testEmptyDefaultValue()
    {
        $this->routing->setInput('/empty_default_value');
        $container = $this->routing->execute();
        $rd = Context::getInstance(null)->getRequest()->getRequestData();
        $this->assertSame('0', $rd->getParameter('value'));
    }
}
