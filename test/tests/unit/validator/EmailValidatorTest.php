<?php
namespace Agavi\Tests\Unit\Validator;

use Agavi\Core\Context;
use Agavi\Testing\UnitTestCase;
use Agavi\Validator\EmailValidator;
use Agavi\Validator\ValidationManager;

class EmailValidatorWrapper extends EmailValidator
{
	protected $data;


	public function setData($data)
	{
		$this->data = $data;
	}

	public function &getData($paramname)
	{
		return $this->data;
	}

	public function validate()
	{
		return parent::validate();
	}

}

class EmailValidatorTest extends UnitTestCase
{
	/** @var  ValidationManager */
	protected $_vm;

	/** @var  EmailValidator */
	protected $validator;
	
	public function setUp()
	{
		$this->_vm = $this->getContext()->createInstanceFor('validation_manager');
		$this->validator = $this->_vm->createValidator('Agavi\\Tests\\Unit\\Validator\\EmailValidatorWrapper', array());
	}

	public function tearDown()
	{
		unset($this->validator);
	}

	public function testgetContext()
	{
		$this->assertTrue($this->validator->getContext() instanceof Context);
	}
	
	public function testexecute()
	{
		$good = array(
			'bob@agavi.org',
			'me.bob@agavi.org',
			'stupidmonkey@example.com',
			'anotherbunk@bunk-domain.com',
			'somethingelse@ez-bunk-domain.biz'
		);
		$bad = array(
			'bad mojo@agavi.org',
			'bunk(data)@agavi.org',
			'bunk@agavi info.com',
			'sjklsdfsfd'
		);
		$error = '';
		foreach ($good as &$value) {
			$this->validator->setData($value);
			$this->assertTrue($this->validator->validate(), "False negative: $value");
		}
		foreach ($bad as &$value) {
			$this->validator->setData($value);
			$this->assertFalse($this->validator->validate(), "False positive: $value");
		}
	}
}

?>