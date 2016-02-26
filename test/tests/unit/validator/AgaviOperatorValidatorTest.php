<?php
namespace Agavi\Tests\Unit\Validator;
use Agavi\Request\RequestDataHolder;
use Agavi\Test\Validator\DummyValidator;
use Agavi\Testing\UnitTestCase;
use Agavi\Validator\OperatorValidator;
use Agavi\Validator\ValidationManager;
use Agavi\Validator\Validator;

class MyOperatorValidator extends OperatorValidator
{
	public $checked = false;
	
	protected function validate() {return true;}
	protected function checkValidSetup() {$this->checked = true;}
	public function getChildren() {return $this->children;}
}

class AgaviOperatorValidatorTest extends UnitTestCase
{
	private $context;

	/**
	 * @var ValidationManager
	 */
	private $vm;
	
	public function setUp()
	{
		$this->context = $this->getContext();
		$this->vm = $this->context->createInstanceFor('validation_manager');
	}
	
	public function tearDown()
	{
		$this->vm = null;
		$this->context = null;
	}
	
	public function testShutdown()
	{
		/** @var DummyValidator $val */
		$val = $this->vm->createValidator('Agavi\\Test\\Validator\\DummyValidator', array());
		/** @var MyOperatorValidator $v */
		$v = $this->vm->createValidator('Agavi\\Tests\\Unit\\Validator\\MyOperatorValidator', array());
		$v->addChild($val);
		
		$this->assertFalse($val->shutdown);
		$v->shutdown();
		$this->assertTrue($val->shutdown);
	}
	
	public function testRegisterValidators()
	{
		/** @var DummyValidator $val1 */
		$val1 = $this->vm->createValidator('Agavi\\Test\\Validator\\DummyValidator', array(), array(), array('name' => 'val1'));
		/** @var DummyValidator $val2 */
		$val2 = $this->vm->createValidator('Agavi\\Test\\Validator\\DummyValidator', array(), array(), array('name' => 'val2'));

		/** @var MyOperatorValidator $v */
		$v = $this->vm->createValidator('Agavi\\Tests\\Unit\\Validator\\MyOperatorValidator', array(), array(), array());
		$this->assertEquals($v->getChildren(), array());
		$v->registerValidators(array($val1, $val2));
		$this->assertEquals($v->getChildren(), array('val1' => $val1, 'val2' =>$val2));
	}
	
	public function testAddChild()
	{
		/** @var DummyValidator $val */
		$val = $this->vm->createValidator('Agavi\\Test\\Validator\\DummyValidator', array(), array(), array('name' => 'val'));
		/** @var MyOperatorValidator $v */
		$v = $this->vm->createValidator('Agavi\\Tests\\Unit\\Validator\\MyOperatorValidator', array());

		$this->assertEquals($v->getChildren(), array());
		$v->addChild($val);
		$this->assertEquals($v->getChildren(), array('val' => $val));
	}
	
	public function testExecute()
	{
		/** @var MyOperatorValidator $v */
		$v = $this->vm->createValidator('Agavi\\Tests\\Unit\\Validator\\MyOperatorValidator', array());
		$this->assertFalse($v->checked);
		$this->assertEquals($v->execute(new RequestDataHolder()), Validator::SUCCESS);
		$this->assertTrue($v->checked);
	}
}
?>
