<?php
namespace Agavi\Testing\Unit\Controller;

use Agavi\Testing\UnitTestCase;

class ExecutionContainerTest extends UnitTestCase
{
    
    public function testSimpleActionWithoutArguments()
    {
        $container = $this->getContext()->getDispatcher()->createExecutionContainer('ControllerTests', 'SimpleController');
        $response = $container->execute();
    }
}
