<?php
namespace Agavi\Test\Validator;

use Agavi\Tests\Unit\Validator\BaseValidatorTest;
use Agavi\Validator\Validator;

require_once(__DIR__ . '/BaseValidatorTest.php');

class RegexValidatorTest extends BaseValidatorTest
{

	public function testExecute()
	{
		$good = array(
			'nnbb',
			'nbb',
			'nnnbb'
		);
		$bad = array(
			'bb',
			'nnnnbb',
			'jdsakl'
		);
		$parameters = array('pattern' => '/^[n]{1,3}bb$/', 'match' => true);
		$errors = array('' => $errorMsg = 'Some other error');
		foreach($good as $value) {
			$this->doTestExecute('Agavi\\Validator\\RegexValidator', $value, Validator::SUCCESS, null, $errors, $parameters);
		}
		foreach($bad as $value) {
			$this->doTestExecute('Agavi\\Validator\\RegexValidator', $value, Validator::ERROR, $errorMsg, $errors, $parameters);
		}

		$parameters['match'] = false;
		foreach($bad as $value) {
			$this->doTestExecute('Agavi\\Validator\\RegexValidator', $value, Validator::SUCCESS, null, $errors, $parameters);
		}
		foreach($good as $value) {
			$this->doTestExecute('Agavi\\Validator\\RegexValidator', $value, Validator::ERROR, $errorMsg, $errors, $parameters);
		}
	}
}

?>