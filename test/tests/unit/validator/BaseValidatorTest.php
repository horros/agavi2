<?php
namespace Agavi\Tests\Unit\Validator;

use Agavi\Request\RequestDataHolder;
use Agavi\Testing\UnitTestCase;

class BaseValidatorTest extends UnitTestCase
{
    protected function executeValidator($class, $value, array $errors = array(), $parameters = array())
    {
        $vm = $this->getContext()->createInstanceFor('validation_manager');

        $validator = $vm->createValidator($class, array('value'), $errors, $parameters);
        $rd = new RequestDataHolder(array(
            RequestDataHolder::SOURCE_PARAMETERS => array('value' => $value)
        ));
        $result = $validator->execute($rd);
        
        return array(
            'result' => $result,
            'vm' => $vm,
            'rd' => $rd
        );
    }
    
    protected function doTestExecute($class, $value, $expectedResult, $expectedError = null, array $errors = array(), array $parameters = array())
    {
        $res = $this->executeValidator($class, $value, $errors, $parameters);
        $this->assertSame($expectedResult, $res['result']);
        $errorMessages = $res['vm']->getReport()->getErrorMessages();
        if ($expectedError === null) {
            $this->assertCount(0, $errorMessages);
        } else {
            $this->assertCount(1, $errorMessages);
            $this->assertSame($expectedError, reset($errorMessages));
        }
    }
}
