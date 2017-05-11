<?php
namespace Agavi\Testing\PHPUnit\Constraint;

use Agavi\Controller\Controller;
use Agavi\Testing\BaseConstraintBecausePhpunitSucksAtBackwardsCompatibility;

/**
 * Constraint that checks if an Controller handles an expected request method.
 *
 * The Controller instance is passed to the constructor.
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
class ConstraintControllerHandlesMethod extends BaseConstraintBecausePhpunitSucksAtBackwardsCompatibility
{
    /**
     * @var        Controller The Controller instance.
     */
    protected $controllerInstance;
    
    /**
     * @var        bool Whether generic 'execute' methods should be accepted.
     */
    protected $acceptGeneric;
    
    /**
     * Class constructor.
     *
     * @param      Controller $controllerInstance Instance of the Controller to test.
     * @param      bool   $acceptGeneric Whether generic execute methods should be accepted.
     *
     * @author     Felix Gilcher <felix.gilcher@bitextender.com>
     * @since      1.0.0
     */
    public function __construct(Controller $controllerInstance, $acceptGeneric = true)
    {
        $this->controllerInstance = $controllerInstance;
        $this->acceptGeneric = $acceptGeneric;
    }
    
    /**
     * Evaluates the constraint for parameter $other. Returns TRUE if the
     * constraint is met, FALSE otherwise.
     *
     * @param      mixed $other Value or object to evaluate.
     *
     * @return     bool The result of the evaluation.
     *
     * @author     Felix Gilcher <felix.gilcher@bitextender.com>
     * @since      1.0.7
     */
    public function matches($other)
    {
        $executeMethod = 'execute' . $other;
        if (is_callable(array($this->controllerInstance, $executeMethod)) || ($this->acceptGeneric && is_callable(array($this->controllerInstance, 'execute')))) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Returns a string representation of the constraint.
     *
     * @return     string The string representation.
     *
     * @author     Felix Gilcher <felix.gilcher@bitextender.com>
     * @since      1.0.0
     */
    public function toString()
    {
        return sprintf(
            '%1$s handles method',
            get_class($this->controllerInstance)
        );
    }
    
    /**
     * Returns a custom error description.
     *
     * @param      mixed  $other Value or object to evaluate.
     * @param      string $description The original description.
     * @param      bool   $not true if the constraint was negated.
     *
     * @return     string The error description.
     *
     * @author     Felix Gilcher <felix.gilcher@bitextender.com>
     * @since      1.0.0
     */
    protected function customFailureDescription($other, $description, $not)
    {
        if ($not) {
            return sprintf(
                'Failed asserting that %1$s does not handle method "%2$s".',
                get_class($this->controllerInstance),
                $other
            );
        } else {
            return sprintf(
                'Failed asserting that %1$s handles method "%2$s".',
                get_class($this->controllerInstance),
                $other
            );
        }
    }
}
