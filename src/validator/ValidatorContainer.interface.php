<?php
namespace Agavi\Validator;

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

/**
 * AgaviIValidatorContainer is an interface for classes which contains several
 * child validators
 *
 * @package    agavi
 * @subpackage validator
 *
 * @author     Dominik del Bondio <ddb@bitxtender.com>
 * @author     Uwe Mesecke <uwe@mesecke.net>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.11.0
 *
 * @version    $Id$
 */
interface ValidatorContainerInterface
{
    /**
     * Adds a new validator to the list of children.
     *
     * @param      Validator $validator new child
     *
     * @author     Uwe Mesecke <uwe@mesecke.net>
     * @since      0.11.0
     */
    public function addChild(Validator $validator);

    /**
     * Adds a intermediate result of an validator for the given argument
     *
     * @param      ValidationArgument $argument  The argument
     * @param      int                $result    The arguments result.
     * @param      Validator          $validaotr The validator (if the error was caused
     *                                           inside a validator).
     *
     * @author     Dominik del Bondio <dominik.del.bondio@bitextender.com>
     * @since      1.0.0
     */
    public function addArgumentResult(ValidationArgument $argument, $result, $validator = null);

    /**
     * Adds an incident to the validation result.
     *
     * @param      ValidationIncident $incident The incident.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public function addIncident(ValidationIncident $incident);

    /**
     * Returns a named child validator.
     *
     * @param      string $name The child validator name.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public function getChild($name);

    /**
     * Returns all child validators.
     *
     * @return     Validator[] An array of Validator instances.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public function getChilds();

    /**
     * Fetches the dependency manager
     *
     * @return     DependencyManager The dependency manager to be used
     *                               by child validators.
     *
     * @author     Uwe Mesecke <uwe@mesecke.net>
     * @since      0.11.0
     */
    public function getDependencyManager();
}
