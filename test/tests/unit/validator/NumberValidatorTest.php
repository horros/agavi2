<?php
namespace Agavi\Tests\Unit\Validator;

use Agavi\Request\RequestDataHolder;
use Agavi\Testing\UnitTestCase;
use Agavi\Validator\ValidationManager;
use Agavi\Validator\Validator;

class NumberValidatorTest extends UnitTestCase
{

	/**
	 * @var ValidationManager
	 */
	protected $vm;

	public function setUp()
	{
		$this->vm = $this->getContext()->createInstanceFor('validation_manager');
	}
	
	public function testNoCastOnFail()
	{
		$number = '1.23';
		$validator = $this->vm->createValidator('Agavi\\Validator\\NumberValidator', array('number'), array('invalid argument'), $parameters = array('type' => 'int'));
		$rd = new RequestDataHolder(array(RequestDataHolder::SOURCE_PARAMETERS => array('number' => $number)));
		$result = $validator->execute($rd);
		$this->assertEquals(Validator::ERROR, $result);
		$this->assertEquals($number, $rd->getParameter('number'));
		$this->assertTrue(is_string($rd->getParameter('number')), 'Failed asserting that the parameter "number" is a string');
	}
	
	public function testImplicitCastToFloat()
	{
		$number = '1.23';
		$validator = $this->vm->createValidator('Agavi\\Validator\\NumberValidator', array('number'), array('invalid argument'), $parameters = array('type' => 'float'));
		$rd = new RequestDataHolder(array(RequestDataHolder::SOURCE_PARAMETERS => array('number' => $number)));
		$result = $validator->execute($rd);
		$this->assertEquals(Validator::SUCCESS, $result);
		$this->assertEquals($number, $rd->getParameter('number'));
		$this->assertTrue(is_float($rd->getParameter('number')), 'Failed asserting that the parameter "number" is a float');
	}
	
	public function testImplicitCastToInt()
	{
		$number = '1';
		$validator = $this->vm->createValidator('Agavi\\Validator\\NumberValidator', array('number'), array('invalid argument'), $parameters = array('type' => 'int'));
		$rd = new RequestDataHolder(array(RequestDataHolder::SOURCE_PARAMETERS => array('number' => $number)));
		$result = $validator->execute($rd);
		$this->assertEquals(Validator::SUCCESS, $result);
		$this->assertEquals($number, $rd->getParameter('number'));
		$this->assertTrue(is_int($rd->getParameter('number')), 'Failed asserting that the parameter "number" is an int');
	}
	
	public function testExplicitCastToInt()
	{
		$number = '1.23';
		$validator = $this->vm->createValidator('Agavi\\Validator\\NumberValidator', array('number'), array('invalid argument'), $parameters = array('type' => 'float', 'cast_to' => 'int'));
		$rd = new RequestDataHolder(array(RequestDataHolder::SOURCE_PARAMETERS => array('number' => $number)));
		$result = $validator->execute($rd);
		$this->assertEquals(Validator::SUCCESS, $result);
		$this->assertEquals(1, $rd->getParameter('number'));
		$this->assertTrue(is_int($rd->getParameter('number')), 'Failed asserting that the parameter "number" is an int');
	}
	
	public function testExplicitCastToFloat()
	{
		$number = '1';
		$validator = $this->vm->createValidator('Agavi\\Validator\\NumberValidator', array('number'), array('invalid argument'), $parameters = array('type' => 'float', 'cast_to' => 'float'));
		$rd = new RequestDataHolder(array(RequestDataHolder::SOURCE_PARAMETERS => array('number' => $number)));
		$result = $validator->execute($rd);
		$this->assertEquals(Validator::SUCCESS, $result);
		$this->assertEquals(1, $rd->getParameter('number'));
		$this->assertTrue(is_float($rd->getParameter('number')), 'Failed asserting that the parameter "number" is a float');
	}
	
	public function testMinFail()
	{
		$minError = 'value too low';
		$number = '1';
		$validator = $this->vm->createValidator('Agavi\\Validator\\NumberValidator', array('number'), array('min' => $minError), $parameters = array('type' => 'int', 'min' => 2));
		$rd = new RequestDataHolder(array(RequestDataHolder::SOURCE_PARAMETERS => array('number' => $number)));
		$result = $validator->execute($rd);
		$this->assertEquals(Validator::ERROR, $result);
		$this->assertEquals(1, $this->vm->getReport()->byErrorName('min')->count(), 'Failes asserting that there is one min error.');
		$this->assertEquals(array($minError), $this->vm->getReport()->getErrorMessages(), 'Failed asserting that the min error message is emittet.');
	}
	
	public function testMinSuccess()
	{
		$minError = 'value too low';
		$number = '1';
		$validator = $this->vm->createValidator('Agavi\\Validator\\NumberValidator', array('number'), array('min' => $minError), $parameters = array('type' => 'int', 'min' => 1));
		$rd = new RequestDataHolder(array(RequestDataHolder::SOURCE_PARAMETERS => array('number' => $number)));
		$result = $validator->execute($rd);
		$this->assertEquals(Validator::SUCCESS, $result);
		$this->assertEquals(0, $this->vm->getReport()->byErrorName('min')->count(), 'Failes asserting that there is no min error.');
		$this->assertEquals(array(), $this->vm->getReport()->getErrorMessages(), 'Failed asserting that no min error message is emittet.');
	}
	
	public function testMaxFail()
	{
		$maxError = 'value too high';
		$number = '2';
		$validator = $this->vm->createValidator('Agavi\\Validator\\NumberValidator', array('number'), array('max' => $maxError), $parameters = array('type' => 'int', 'max' => 1));
		$rd = new RequestDataHolder(array(RequestDataHolder::SOURCE_PARAMETERS => array('number' => $number)));
		$result = $validator->execute($rd);
		$this->assertEquals(Validator::ERROR, $result);
		$this->assertEquals(1, $this->vm->getReport()->byErrorName('max')->count(), 'Failes asserting that there is one max error.');
		$this->assertEquals(array($maxError), $this->vm->getReport()->getErrorMessages(), 'Failed asserting that the max error message is emittet.');
	}
	
	public function testMaxSuccess()
	{
		$maxError = 'value too high';
		$number = '2';
		$validator = $this->vm->createValidator('Agavi\\Validator\\NumberValidator', array('number'), array('max' => $maxError), $parameters = array('type' => 'int', 'max' => 2));
		$rd = new RequestDataHolder(array(RequestDataHolder::SOURCE_PARAMETERS => array('number' => $number)));
		$result = $validator->execute($rd);
		$this->assertEquals(Validator::SUCCESS, $result);
		$this->assertEquals(0, $this->vm->getReport()->byErrorName('max')->count(), 'Failes asserting that there is no max error.');
		$this->assertEquals(array(), $this->vm->getReport()->getErrorMessages(), 'Failed asserting that no max error message is emittet.');
	}
	
}

?>