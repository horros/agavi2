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
 * ValidationReportQuery allows queries against the validation run report.
 *
 * @package    agavi
 * @subpackage validator
 *
 * @author     Dominik del Bondio <dominik.del.bondio@bitextender.com>
 * @author     David Zülke <david.zuelke@bitextender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      1.0.0
 *
 * @version    $Id$
 */
interface ValidationReportQueryInterface
{
    /**
     * Returns a new ValidationReportQuery which returns only the incidents
     * for the given argument (and the other existing filter rules).
     *
     * @param      ValidationArgument|string|array The argument instance, or
     *                                                  a parameter name, or an
     *                                                  array of these elements.
     *
     * @return     ValidationReportQuery
     *
     * @author     Dominik del Bondio <dominik.del.bondio@bitextender.com>
     * @since      1.0.0
     */
    public function byArgument($argument);
    
    /**
     * Returns a new ValidationReportQuery which contains only the incidents
     * for the given validator (and the other existing filter rules).
     *
     * @param      string|array The name of the validator, or an array of names.
     *
     * @return     ValidationReportQuery
     *
     * @author     Dominik del Bondio <dominik.del.bondio@bitextender.com>
     * @since      1.0.0
     */
    public function byValidator($name);
    
    /**
     * Returns a new ValidationReportQuery which contains only the incidents
     * for the given error name (and the other existing filter rules).
     *
     * @param      string|array The name of the error, or an array of names.
     *
     * @return     ValidationReportQuery
     *
     * @author     Dominik del Bondio <dominik.del.bondio@bitextender.com>
     * @since      1.0.0
     */
    public function byErrorName($name);
    
    /**
     * Returns a new ValidationReportQuery which contains only the incidents
     * of the given severity or higher (and the other existing filter rules).
     *
     * @param      int $minSeverity The minimum severity.
     *
     * @return     ValidationReportQuery
     *
     * @author     Dominik del Bondio <dominik.del.bondio@bitextender.com>
     * @since      1.0.0
     */
    public function byMinSeverity($minSeverity);
    
    /**
     * Returns a new ValidationReportQuery which contains only the incidents
     * of the given severity or lower (and the other existing filter rules).
     *
     * @param      int $maxSeverity The maximum severity.
     *
     * @return     ValidationReportQuery
     *
     * @author     David Zülke <david.zuelke@bitextender.com>
     * @since      1.0.0
     */
    public function byMaxSeverity($maxSeverity);
    
    /**
     * Retrieves all incidents which match the currently defined filter rules.
     *
     * @return     ValidationIncident[] An array of ValidationIncident objects.
     *
     * @author     Dominik del Bondio <dominik.del.bondio@bitextender.com>
     * @since      1.0.0
     */
    public function getIncidents();
    
    /**
     * Retrieves all ValidationError objects which match the currently
     * defined filter rules.
     *
     * @return     ValidationError[] An array of ValidationError objects.
     *
     * @author     Dominik del Bondio <dominik.del.bondio@bitextender.com>
     * @since      1.0.0
     */
    public function getErrors();
    
    /**
     * Retrieves all error messages which match the currently defined filter
     * rules.
     *
     * @return     array An array of message strings.
     *
     * @author     Dominik del Bondio <dominik.del.bondio@bitextender.com>
     * @since      1.0.0
     */
    public function getErrorMessages();
    
    /**
     * Retrieves all AgaviValidationArgument objects which match the currently
     * defined filter rules.
     *
     * @return     array An array of AgaviValidationArgument objects.
     *
     * @author     Dominik del Bondio <dominik.del.bondio@bitextender.com>
     * @since      1.0.0
     */
    public function getArguments();
    
    /**
     * Check if there are any incidents matching the currently defined filter
     * rules.
     *
     * @return     bool Whether or not any incidents exist for the currently
     *                  defined filter rules.
     *
     * @author     Dominik del Bondio <dominik.del.bondio@bitextender.com>
     * @since      1.0.0
     */
    public function has();
    
    /**
     * Get the number of incidents matching the currently defined filter rules.
     *
     * @return     int The number of incidents matching the currently defined
     *                 filter rules.
     *
     * @author     Dominik del Bondio <dominik.del.bondio@bitextender.com>
     * @since      1.0.0
     */
    public function count();
    
    /**
     * Retrieves the highest validation result code of the collection composed of
     * the currently defined filter rules.
     *
     * @return     int A Validator::* severity constant, or null if there is
     *                 no result for this filter combination. Please remember to
     *                 do a strict === comparison if you are comparing against
     *                 Validator::SUCCESS.
     *
     * @author     David Zülke <david.zuelke@bitextender.com>
     */
    public function getResult();
}
