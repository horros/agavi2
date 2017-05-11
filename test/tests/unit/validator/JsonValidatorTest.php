<?php
namespace Agavi\Tests\Unit\Validator;

use Agavi\Validator\Validator;

require_once(__DIR__ . '/BaseValidatorTest.php');

class JsonValidatorTest extends BaseValidatorTest
{
    public function testExecute()
    {
        $this->doTestExecute('Agavi\\Validator\\JsonValidator', json_encode(array('foo' => 'bar')), Validator::SUCCESS);
        
        $errors = array(
            'syntax' => $errorMsg = 'Syntax error',
        );
        $this->doTestExecute('Agavi\\Validator\\JsonValidator', '{', Validator::ERROR, $errorMsg, $errors);
    }

    public function testExport()
    {
        $value = array('foo' => 'bar');

        $res = $this->executeValidator('Agavi\\Validator\\JsonValidator', json_encode($value), array(), array(
            'export' => 'test',
        ));
        $this->assertEquals($res['rd']->getParameter('test'), $value);

        $res = $this->executeValidator('Agavi\\Validator\\JsonValidator', json_encode($value), array(), array(
            'export' => 'test',
            'assoc'  => false,
        ));
        $this->assertEquals($res['rd']->getParameter('test'), (object)$value);
    }
}
