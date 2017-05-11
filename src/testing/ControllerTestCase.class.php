<?php
namespace Agavi\Testing;

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2005-2011 the Agavi Project.                                |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org/LICENSE.txt                   |
// |   vi: set noexpandtab:                                                    |
// |   Local Variables:                                                        |
// |   indent-tabs-mode: t                                                     |
// |   End:                                                                    |
// +---------------------------------------------------------------------------+
use Agavi\Dispatcher\ExecutionContainer;
use Agavi\Request\RequestDataHolder;
use Agavi\Testing\PHPUnit\Constraint\ConstraintControllerHandlesMethod;
use Agavi\Validator\ValidationArgument;

/**
 * ControllerTestCase is the base class for all controller testcases and provides
 * the necessary assertions
 *
 *
 * @package    agavi
 * @subpackage testing
 *
 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
 * @copyright  The Agavi Project
 *
 * @since      1.0.0
 *
 * @version    $Id$
 */
abstract class ControllerTestCase extends FragmentTestCase
{

    
    /**
     * @var        string the name of the resulting view
     */
    protected $viewName;
    
    /**
     * @var        string the name of the resulting view's module
     */
    protected $viewModuleName;

    /**
     * @var        ExecutionContainer
     */
    protected $container;
    /**
     * run the controller for this testcase
     *
     * @return     void
     *
     * @author     Felix Gilcher <felix.gilcher@bitextender.com>
     * @since      1.0.0
     */
    protected function runController()
    {
        $this->container->setControllerInstance($this->createControllerInstance());
        //$executionFilter = $this->createExecutionFilter();
        $this->container->initRequestData();
        list($this->viewModuleName, $this->viewName) = $this->container->runController();
    }
    
    /**
     * register the validators for this testcase
     *
     * @return     void
     *
     * @author     Felix Gilcher <felix.gilcher@bitextender.com>
     * @since      1.0.0
     */
    protected function performValidation()
    {
        $this->container->setControllerInstance($this->createControllerInstance());
        $this->validationSuccess = $this->container->performValidation($this->container);
    }
    
    /**
     * asserts that the viewName is the expected value after runController was called
     *
     * @param      string $expected the expected viewname in short form ('Success' etc)
     * @param      string $message an optional message to display if the test fails
     *
     * @return     void
     *
     * @author     Felix Gilcher <felix.gilcher@bitextender.com>
     * @since      1.0.0
     */
    protected function assertViewNameEquals($expected, $message = 'Failed asserting that the view\'s name is <%1$s>.')
    {
        $expected = $this->normalizeViewName($expected);
        $this->assertEquals($expected, $this->viewName, sprintf($message, $expected));
    }
    
    /**
     * asserts that the view's modulename is the expected value after runController was called
     *
     * @param      string $expected the expected moduleName
     * @param      string $message an optional message to display if the test fails
     *
     * @return     void
     *
     * @author     Felix Gilcher <felix.gilcher@bitextender.com>
     * @since      1.0.0
     */
    protected function assertViewModuleNameEquals($expected, $message = 'Failed asserting that the view\'s module name is <%1$s>.')
    {
        $this->assertEquals($expected, $this->viewModuleName, sprintf($message, $expected));
    }
    
    /**
     * asserts that the DefaultView is the expected
     *
     * @param     mixed $expected A string containing the view name associated with the
     *                   controller.
     *                   Or an array with the following indices:
     *                   - The parent module of the view that will be executed.
     *                   - The view that will be executed.
     *
     * @param      string $message an optional message to display if the test fails
     *
     * @return     void
     *
     * @see        Controller::getDefaultViewName()
     *
     * @author     Felix Gilcher <felix.gilcher@bitextender.com>
     * @since      1.0.0
     */
    protected function assertDefaultView($expected, $message = 'Failed asserting that the defaultView is the expected value.')
    {
        $controllerInstance = $this->createControllerInstance();
        $this->assertEquals($expected, $controllerInstance->getDefaultViewName(), $message);
    }
    
    /**
     * assert that the controller handles the given request method
     *
     * @param      string  $message the method name
     * @param      boolean $acceptGeneric true if the generic 'execute' method should be accepted as handled
     * @param      string  $message an optional message to display if the test fails
     *
     * @author     Felix Gilcher <felix.gilcher@bitextender.com>
     * @since      1.0.0
     */
    protected function assertHandlesMethod($method, $acceptGeneric = true, $message = '')
    {
        $controllerInstance = $this->createControllerInstance();
        $constraint = new ConstraintControllerHandlesMethod($controllerInstance, $acceptGeneric);
        
        self::assertThat($method, $constraint, $message);
    }
    
    /**
     * assert that the controller does not handle the given request method
     *
     * @param      string  $method the method name
     * @param      boolean $acceptGeneric true if the generic 'execute' method should be accepted as handled
     * @param      string  $message an optional message to display if the test fails
     *
     * @author     Felix Gilcher <felix.gilcher@bitextender.com>
     * @since      1.0.0
     */
    protected function assertNotHandlesMethod($method, $acceptGeneric = true, $message = '')
    {
        $controllerInstance = $this->createControllerInstance();
        $constraint = self::logicalNot(new ConstraintControllerHandlesMethod($controllerInstance, $acceptGeneric));
        
        self::assertThat($method, $constraint, $message);
    }
    
    /**
     * assert that the controller is simple
     *
     * @param      string $message an optional message to display if the test fails
     *
     * @author     Felix Gilcher <felix.gilcher@bitextender.com>
     * @since      1.0.0
     */
    protected function assertIsSimple($message = 'Failed asserting that the controller is simple.')
    {
        $controllerInstance = $this->createControllerInstance();
        $this->assertTrue($controllerInstance->isSimple(), $message);
    }
    
    /**
     * assert that the controller is not simple
     *
     * @param      string $message an optional message to display if the test fails
     *
     * @author     Felix Gilcher <felix.gilcher@bitextender.com>
     * @since      1.0.0
     */
    protected function assertIsNotSimple($message = 'Failed asserting that the controller is not simple.')
    {
        $controllerInstance = $this->createControllerInstance();
        $this->assertFalse($controllerInstance->isSimple(), $message);
    }

    /**
     * asserts that the given argument has been touched by a validator
     *
     * This does not imply that the validation failed or succeeded, just
     * that a validator attempted to validate the given argument
     *
     * @param      string $argumentName the name of the argument
     * @param      string $source the source of the argument
     * @param      string $message an optional message
     *
     * @author     Felix Gilcher <felix.gilcher@bitextender.com>
     * @since      1.0.0
     */
    protected function assertValidatedArgument($argumentName, $source = RequestDataHolder::SOURCE_PARAMETERS, $message = 'Failed asserting that the argument <%1$s> is validated.')
    {
        $report = $this->container->getValidationManager()->getReport();
        $this->assertTrue($report->isArgumentValidated(new ValidationArgument($argumentName, $source)), sprintf($message, $argumentName));
    }

    /**
     * asserts that the given argument has failed the validation
     *
     * @param      string $argumentName the name of the argument
     * @param      string $source the source of the argument
     * @param      string $message an optional message
     *
     * @author     Felix Gilcher <felix.gilcher@bitextender.com>
     * @since      1.0.0
     */
    protected function assertFailedArgument($argumentName, $source = RequestDataHolder::SOURCE_PARAMETERS, $message = 'Failed asserting that the argument <%1$s> is failed.')
    {
        $report = $this->container->getValidationManager()->getReport();
        $this->assertTrue($report->isArgumentFailed(new ValidationArgument($argumentName, $source)), sprintf($message, $argumentName));
    }

    /**
     * asserts that the given argument has succeeded the validation
     *
     * @param      string $argumentName the name of the argument
     * @param      string $source the source of the argument
     * @param      string $message an optional message
     *
     * @author     Felix Gilcher <felix.gilcher@bitextender.com>
     * @since      1.0.0
     */
    protected function assertSucceededArgument($argumentName, $source = RequestDataHolder::SOURCE_PARAMETERS, $message = 'Failed asserting that the argument <%1$s> is succeeded.')
    {
        $report = $this->container->getValidationManager()->getReport();
        $success = $report->isArgumentValidated(new ValidationArgument($argumentName, $source)) && ! $report->isArgumentFailed(new ValidationArgument($argumentName, $source));
        $this->assertTrue($success, sprintf($message, $argumentName));
    }
}
