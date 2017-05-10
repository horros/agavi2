<?php
namespace Agavi\Tests\Unit\Validator;
use Agavi\Exception\ValidatorException;
use Agavi\Request\RequestDataHolder;
use Agavi\Test\Validator\DummyValidator;
use Agavi\Testing\UnitTestCase;
use Agavi\Validator\NotoperatorValidator;
use Agavi\Validator\Validator;

class NotoperatorValidatorTest extends UnitTestCase
{
	public function testvalidate()
	{
		$vm = $this->getContext()->createInstanceFor('validation_manager');
		$vm->clear();
		/** @var NotoperatorValidator $o */
		$o = $vm->createValidator('Agavi\\Validator\\NotoperatorValidator', array(), array(), array('severity' => 'error'));

		/** @var DummyValidator $val1 */
		$val1 = $vm->createValidator('Agavi\\Test\\Validator\\DummyValidator', array(), array(), array('severity' => 'error'));
		$o->registerValidators(array($val1));
		
		// 1st test: successful
		$val1->val_result = true;
		$this->assertEquals($o->execute(new RequestDataHolder()), Validator::ERROR);
		$this->assertTrue($val1->validated);
		$val1->clear();

		// 2nd test: failure
		$val1->val_result = false;
		$this->assertEquals($o->execute(new RequestDataHolder()), Validator::SUCCESS);
		$this->assertTrue($val1->validated);
		$val1->clear();

		// 3rd test: critical
		$val1->val_result = false;
		$val1->setParameter('severity', 'critical');
		$this->assertEquals($o->execute(new RequestDataHolder()), Validator::CRITICAL);
		$this->assertTrue($val1->validated);
		$val1->clear();
	}
	
	public function testcheckValidSetup()
	{
		$vm = $this->getContext()->createInstanceFor('validation_manager');
		$vm->clear();
		/** @var NotoperatorValidator $o */
		$o = $vm->createValidator('Agavi\\Validator\\NotoperatorValidator', array(), array(), array('severity' => 'error'));
		
		$val1 = $vm->createValidator('Agavi\\Test\\Validator\\DummyValidator', array(), array(), array('severity' => 'error'));
		$val2 = $vm->createValidator('Agavi\\Test\\Validator\\DummyValidator', array(), array(), array('severity' => 'error'));
		
		try {
			$o->execute(new RequestDataHolder());
			$this->fail();
		} catch(ValidatorException $e) {
			$this->assertEquals($e->getMessage(), 'NOT allows only 1 child validator');
		}
		$o->addChild($val1);
		
		$o->addChild($val2);
		try {
			$o->execute(new RequestDataHolder());
			$this->fail();
		} catch(ValidatorException $e) {
			$this->assertEquals($e->getMessage(), 'NOT allows only 1 child validator');
		}
	}
}
?>
