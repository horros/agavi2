<?php
namespace Agavi\Tests\Unit\Filter;

use Agavi\Core\Context;
use Agavi\Filter\FilterChain;
use Agavi\Filter\FormPopulationFilter;
use Agavi\Request\RequestDataHolder;
use Agavi\Testing\UnitTestCase;
use Agavi\Validator\ValidationManager;

class FormPopulationFilterTest extends UnitTestCase
{
	
	/**
	 * @var Context
	 */
	private $_context;
	
	
	public function setUp()
	{
		$this->_context = Context::getInstance('test');
	}
	
	public function tearDown()
	{
		$this->_context = null;
	}
	
	public function testTextValuePopulation()
	{
		$html = '<!DOCTYPE html><html><body><form action="/"><input type="text" name="foo"></form></body></html>';
		$parameters = array(
			'foo' => 'bar',
		);
		
		$content = $this->executeFormPopulationFilter($html, $parameters);
		$xpath = $this->loadXpath($content);
		
		$this->assertEquals(1, $xpath->query('//input[@value="bar"]')->length);
	}
	
	public function testCheckboxValuePopulation()
	{
		$html = '<!DOCTYPE html><html><body><form action="/"><input type="checkbox" name="foo" value="1"></form></body></html>';
		$parameters = array(
			'foo' => '1',
		);
		
		$content = $this->executeFormPopulationFilter($html, $parameters);
		$xpath = $this->loadXpath($content);
		
		$this->assertEquals(1, $xpath->query('//input[@checked]')->length);
	}
	
	public function testSelectValuePopulation()
	{
		$html = '<!DOCTYPE html><html><body><form action="/"><select name="foo"><option value="bar">bar</option></select></form></body></html>';
		$parameters = array(
			'foo' => 'bar',
		);
		
		$content = $this->executeFormPopulationFilter($html, $parameters);
		$xpath = $this->loadXpath($content);
		
		$this->assertEquals(1, $xpath->query('//option[@value="bar" and @selected]')->length);
	}
	
	public function testFieldErrorMessage()
	{
		$html = '<!DOCTYPE html><html><body><form action="/"><input type="text" name="foo"></form></body></html>';
		$parameters = array(
			'foo' => 'bar',
		);
		
		$vm = $this->_context->createInstanceFor('validation_manager'); /** @var $vm \ValidationManager */
		$val1 = $vm->createValidator('Agavi\\Test\\Validator\\DummyValidator', array('foo'), array('' => 'My error message'));
		$val1->val_result = false;
		
		$config = array(
			'field_error_messages' => array(
				'self::*' =>  array(
					'location'  => 'after',
					'container' => '<ul>${errorMessages}</ul>',
					'markup'    => '<li>${errorMessage}</li>',
				),
			),
		);
		
		$content = $this->executeFormPopulationFilter($html, $parameters, $vm, $config);
		$xpath = $this->loadXpath($content);
		
		$this->assertEquals(1, $xpath->query('//input/following-sibling::ul')->length);
	}
	
	public function testErrorMessage()
	{
		$html = '<!DOCTYPE html><html><body><form action="/"><input type="text" name="foo"></form></body></html>';
		$parameters = array(
			'foo' => 'bar',
		);
		
		$vm = $this->_context->createInstanceFor('validation_manager'); /** @var $vm \ValidationManager */
		$val1 = $vm->createValidator('Agavi\\Test\\Validator\\DummyValidator', array('foo'), array('' => 'My error message'));
		$val1->val_result = false;
		
		$config = array(
			'error_messages' => array(
				'self::*' =>  array(
					'location'  => 'before',
					'container' => '<ul>${errorMessages}</ul>',
					'markup'    => '<li>${errorMessage}</li>',
				),
			),
		);
		
		$content = $this->executeFormPopulationFilter($html, $parameters, $vm, $config);
		$xpath = $this->loadXpath($content);
		
		$this->assertEquals('ul', $xpath->query('//form/*[1]')->item(0)->nodeName);
	}
	
	public function testFormsXpathSetting()
	{
		$html = '<!DOCTYPE html><html><body><input type="text" name="foo"></body></html>';
		$parameters = array(
			'foo' => 'bar',
		);
		
		$config = array(
			'forms_xpath' => '//${htmlnsPrefix}body',
		);
		
		$content = $this->executeFormPopulationFilter($html, $parameters, null, $config);
		$xpath = $this->loadXpath($content);
		
		$this->assertEquals(1, $xpath->query('//input[@value="bar"]')->length);
	}
	
	public function testErrorCallbacksClosureHtml()
	{
		$html = '<!DOCTYPE html><html><body><form action="/"><input type="text" name="foo"></form></body></html>';
		$parameters = array(
			'foo' => 'bar',
		);
		/** @var $vm ValidationManager */
		$vm = $this->_context->createInstanceFor('validation_manager');
		$val1 = $vm->createValidator('Agavi\\Test\\Validator\\DummyValidator', array('foo'), array('' => 'My error message'));
		$val1->val_result = false;
		
		$config = array(
			'error_messages' => array(
				'self::*' =>  array(
					'location'  => 'before',
					'container' => function($element, array $errorStrings, array $errors) {
						$html = '<ul>';
						foreach($errors as $error) {
							$html .= '<li>' . htmlspecialchars($error->getMessage()) . '</li>';
						}
						$html .= '</ul>';
						return $html;
					},
				),
			),
		);
		
		$content = $this->executeFormPopulationFilter($html, $parameters, $vm, $config);
		$xpath = $this->loadXpath($content);
		
		$this->assertEquals(1, $xpath->query('//ul/li')->length);
	}
	
	public function testErrorCallbacksCallableDomelement()
	{
		$html = '<!DOCTYPE html><html><body><form action="/"><input type="text" name="foo"></form></body></html>';
		$parameters = array(
			'foo' => 'bar',
		);
		
		$vm = $this->_context->createInstanceFor('validation_manager'); /** @var $vm ValidationManager */
		$val1 = $vm->createValidator('Agavi\\Test\\Validator\\DummyValidator', array('foo'), array('' => 'My error message'));
		$val1->val_result = false;
		
		$config = array(
			'error_messages' => array(
				'self::*' =>  array(
					'location'  => 'before',
					'container' => __CLASS__ . '::_errorCallback',
				),
			),
		);
		
		$content = $this->executeFormPopulationFilter($html, $parameters, $vm, $config);
		$xpath = $this->loadXpath($content);
		
		$this->assertEquals(1, $xpath->query('//div')->length);
	}
	
	public static function _errorCallback($element, array $errorStrings, array $errors) {
		return new \DOMElement('div', implode(',', $errorStrings));
	}
	
	/**
	 * @param string $content
	 * @param RequestDataHolder|array $parameters
	 * @param ValidationManager $validationManager
	 * @param array $config
	 * @return string
	 */
	protected function executeFormPopulationFilter($content, $parameters, $validationManager = null, array $config = array())
	{
		$container = $this->_context->getDispatcher()->createExecutionContainer('FilterTests', 'FormPopulationFilter');
		$container->getResponse()->setContent($content);
		
		if($parameters instanceof RequestDataHolder) {
			$rd = $parameters;
		} else {
			$rd = new RequestDataHolder(array(
				RequestDataHolder::SOURCE_PARAMETERS => $parameters,
			));
		}
		
		if($validationManager) {
			$validationManager->execute($rd);
		}
		
		$fpf = new FormPopulationFilter();
		$fpf->initialize($this->_context, array_merge(array(
			'populate' => $rd,
			'validation_report' => $validationManager ? $validationManager->getReport() : null,
			'force_request_uri' => '/',
		), $config));
		
		$filterChain = new FilterChain();
		$filterChain->initialize($this->_context);
		
		$fpf->execute($filterChain, $container);
		
		return $container->getResponse()->getContent();
	}
	
	/**
	 * @param string $content
	 * @return \DOMXPath
	 */
	protected function loadXpath($content) {
		$dom = new \DOMDocument();
		$dom->strictErrorChecking = false;
		$dom->recover = true;
		$dom->loadHTML($content);
		return new \DOMXPath($dom);
	}
	
}

?>