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
use Agavi\Core\Context;
use Agavi\Exception\ConfigurationException;
use Agavi\Request\RequestDataHolder;
use Agavi\Util\ArrayPathDefinition;
use Agavi\Util\ParameterHolder;
use Agavi\Util\VirtualArrayPath;

/**
 * AgaviValidationManager provides management for request parameters and their
 * associated validators.
 *
 * @package    agavi
 * @subpackage validator
 *
 * @author     Uwe Mesecke <uwe@mesecke.net>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.11.0
 *
 * @version    $Id$
 */
class ValidationManager extends ParameterHolder implements ValidatorContainerInterface
{
    /**
     * @var        DependencyManager The dependency manager.
     */
    protected $dependencyManager = null;

    /**
     * @var        array An array of child validators.
     */
    protected $children = array();

    /**
     * @var        Context The context instance.
     */
    protected $context = null;

    /**
     * @var        ValidationReport The report container storing the validation results.
     */
    protected $report = null;

    /**
     * All request variables are always available.
     */
    const MODE_RELAXED = 'relaxed';

    /**
     * All request variables are available when no validation defined else only
     * validated request variables are available.
     */
    const MODE_CONDITIONAL = 'conditional';

    /**
     * Only validated request variables are available.
     */
    const MODE_STRICT = 'strict';

    /**
     * initializes the validator manager.
     *
     * @param      Context $context    The context instance.
     * @param      array   $parameters The initialization parameters.
     *
     * @author     Uwe Mesecke <uwe@mesecke.net>
     * @since      0.11.0
     */
    public function initialize(Context $context, array $parameters = array())
    {
        if (isset($parameters['mode'])) {
            if (!in_array($parameters['mode'], array(self::MODE_RELAXED, self::MODE_CONDITIONAL, self::MODE_STRICT))) {
                throw new ConfigurationException('Invalid validation mode "' . $parameters['mode'] . '" specified');
            }
        } else {
            $parameters['mode'] = self::MODE_STRICT;
        }

        $this->context = $context;
        $this->setParameters($parameters);

        $this->dependencyManager = new DependencyManager();
        $this->report = new ValidationReport();
        $this->children = array();
    }

    /**
     * Retrieve the current application context.
     *
     * @return     Context The current Context instance.
     *
     * @author     Uwe Mesecke <uwe@mesecke.net>
     * @since      0.11.0
     */
    final public function getContext()
    {
        return $this->context;
    }
    
    /**
     * Retrieve the validation result report container of the last validation run.
     *
     * @return     ValidationReport The result report container.
     *
     * @author     Dominik del Bondio <dominik.del.bondio@bitextender.com>
     * @since      1.0.0
     */
    public function getReport()
    {
        return $this->report;
    }

    /**
     * Creates a new validator instance.
     *
     * @param      string             $class      The name of the class implementing the validator.
     * @param      array              $arguments  The argument names.
     * @param      array              $errors     The error messages.
     * @param      array              $parameters The validator parameters.
     * @param      ValidatorContainerInterface $parent     The parent (will use the validation
     *                                            manager if null is given)
     * @return     Validator
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public function createValidator($class, array $arguments, array $errors = array(), $parameters = array(), ValidatorContainerInterface $parent = null)
    {
        if ($parent === null) {
            $parent = $this;
        }
        /** @var Validator $obj */
        $obj = new $class;
        $obj->initialize($this->getContext(), $parameters, $arguments, $errors);
        $parent->addChild($obj);

        return $obj;
    }

    /**
     * Clears the validation manager for reuse
     *
     * clears the validator manager by resetting the dependency and error
     * manager and removing all validators after calling their shutdown
     * method so they can do a save shutdown.
     *
     * @author     Uwe Mesecke <uwe@mesecke.net>
     * @since      0.11.0
     */
    public function clear()
    {
        $this->dependencyManager->clear();

        $this->report = new ValidationReport();

        /** @var Validator $child */
        foreach ($this->children as $child) {
            $child->shutdown();
        }
        $this->children = array();
    }

    /**
     * Adds a new child validator.
     *
     * @param      Validator $validator The new child validator.
     *
     * @author     Uwe Mesecke <uwe@mesecke.net>
     * @since      0.11.0
     */
    public function addChild(Validator $validator)
    {
        $name = $validator->getName();
        if (isset($this->children[$name])) {
            throw new \InvalidArgumentException('A validator with the name "' . $name . '" already exists');
        }

        $this->children[$name] = $validator;
        $validator->setParentContainer($this);
    }

    /**
     * Returns a named child validator.
     *
     * @param      string $name The child validator name.
     *
     * @return     Validator
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public function getChild($name)
    {
        if (!isset($this->children[$name])) {
            throw new \InvalidArgumentException('A validator with the name "' . $name . '" does not exist');
        }

        return $this->children[$name];
    }

    /**
     * Returns all child validators.
     *
     * @return     Validator[] An array of Validator instances.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public function getChilds()
    {
        return $this->children;
    }

    /**
     * Returns the dependency manager.
     *
     * @return     DependencyManager The dependency manager instance.
     *
     * @author     Uwe Mesecke <uwe@mesecke.net>
     * @since      0.11.0
     */
    public function getDependencyManager()
    {
        return $this->dependencyManager;
    }

    /**
     * Gets the base path of the validator.
     *
     * @return     VirtualArrayPath The base path.
     *
     * @author     Uwe Mesecke <uwe@mesecke.net>
     * @since      0.11.0
     */
    public function getBase()
    {
        return new VirtualArrayPath($this->getParameter('base', ''));
    }

    /**
     * Starts the validation process.
     *
     * @param      RequestDataHolder $parameters The data which should be validated.
     *
     * @return     bool true, if validation succeeded.
     *
     * @author     Uwe Mesecke <uwe@mesecke.net>
     * @since      0.11.0
     */
    public function execute(RequestDataHolder $parameters)
    {
        $success = true;
        $this->report = new ValidationReport();
        $result = Validator::SUCCESS;
        
        $req = $this->context->getRequest();

        $executedValidators = 0;
        /** @var Validator $validator */
        foreach ($this->children as $validator) {
            ++$executedValidators;

            $validatorResult = $validator->execute($parameters);
            $result = max($result, $validatorResult);

            switch ($validatorResult) {
                case Validator::SUCCESS:
                    continue 2;
                case Validator::INFO:
                    continue 2;
                case Validator::SILENT:
                    continue 2;
                case Validator::NOTICE:
                    continue 2;
                case Validator::ERROR:
                    $success = false;
                    continue 2;
                case Validator::CRITICAL:
                    $success = false;
                    break 2;
            }
        }
        $this->report->setResult($result);
        $this->report->setDependTokens($this->getDependencyManager()->getDependTokens());

        $ma = $req->getParameter('module_accessor');
        $aa = $req->getParameter('controller_accessor');
        $umap = $req->getParameter('use_module_controller_parameters');

        $mode = $this->getParameter('mode');

        if ($executedValidators == 0 && $mode == self::MODE_STRICT) {
            // strict mode and no validators executed -> clear the parameters
            if ($umap) {
                $maParam = $parameters->getParameter($ma);
                $aaParam = $parameters->getParameter($aa);
            }
            $parameters->clearAll();
            if ($umap) {
                if ($maParam) {
                    $parameters->setParameter($ma, $maParam);
                }
                if ($aaParam) {
                    $parameters->setParameter($aa, $aaParam);
                }
            }
        }

        if ($mode == self::MODE_STRICT || ($executedValidators > 0 && $mode == self::MODE_CONDITIONAL)) {
            // first, we explicitly unset failed arguments
            // the primary purpose of this is to make sure that arrays that failed validation themselves (e.g. due to array length validation, or due to use of operator validators with an argument base) are removed
            // that's of course only necessary if validation failed
            $failedArguments = $this->report->getFailedArguments();

            /** @var ValidationArgument $argument */
            foreach ($failedArguments as $argument) {
                $parameters->remove($argument->getSource(), $argument->getName());
            }
            
            // next, we remove all arguments from the request data that are not in the list of succeeded arguments
            // this will also remove any arguments that didn't have validation rules defined
            $succeededArguments = $this->report->getSucceededArguments();
            foreach ($parameters->getSourceNames() as $source) {
                $sourceItems = $parameters->getAll($source);
                foreach (ArrayPathDefinition::getFlatKeyNames($sourceItems) as $name) {
                    if (!isset($succeededArguments[$source . '/' . $name]) && (!$umap || ($source != RequestDataHolder::SOURCE_PARAMETERS || ($name != $ma && $name != $aa)))) {
                        $parameters->remove($source, $name);
                    }
                }
            }
        }

        return $success;
    }

    /**
     * Shuts the validation system down.
     *
     * @author     Uwe Mesecke <uwe@mesecke.net>
     * @since      0.11.0
     */
    public function shutdown()
    {
        /** @var Validator $child */
        foreach ($this->children as $child) {
            $child->shutdown();
        }
    }

    /**
     * Registers multiple validators.
     *
     * @param      Validator[] $validators An array of validators.
     *
     * @author     Uwe Mesecke <uwe@mesecke.net>
     * @since      0.11.0
     */
    public function registerValidators(array $validators)
    {
        foreach ($validators as $validator) {
            $this->addChild($validator);
        }
    }
    
    /**
     * Adds an incident to the validation result. This will automatically adjust
     * the field result table (which is required because one can still manually
     * add errors either via AgaviRequest::addError or by directly using this
     * method)
     *
     * @param      ValidationIncident $incident The incident.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     */
    public function addIncident(ValidationIncident $incident)
    {
        return $this->report->addIncident($incident);
    }
    
    
    /////////////////////////////////////////////////////////////////////////////
    ////////////////////////////// Deprecated Parts /////////////////////////////
    /////////////////////////////////////////////////////////////////////////////
    
    
    /**
     * Returns the final validation result.
     *
     * @return     int The result of the validation process.
     *
     * @author     Dominik del Bondio <dominik.del.bondio@bitextender.com>
     * @since      0.11.0
     *
     * @deprecated 1.0.0
     */
    public function getResult()
    {
        $result = $this->report->getResult();
        
        if (null === $result) {
            $result = Validator::NOT_PROCESSED;
        }
        
        return $result;
    }

    /**
     * Adds a validation result for a given field.
     *
     * @param      Validator $validator The validator.
     * @param      string    $fieldname The name of the field which has been validated.
     * @param      int       $result    The result of the validation.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     *
     * @deprecated 1.0.0
     */
    public function addFieldResult($validator, $fieldname, $result)
    {
        $argument = new ValidationArgument($fieldname);
        return $this->report->addArgumentResult($argument, $result, $validator);
    }

    /**
     * Adds a intermediate result of an validator for the given argument
     *
     * @param      ValidationArgument $argument The argument
     * @param      int                $result The arguments result.
     * @param      Validator          $validator The validator (if the error was caused
     *                                     inside a validator).
     *
     * @author     Dominik del Bondio <dominik.del.bondio@bitextender.com>
     * @since      1.0.0
     */
    public function addArgumentResult(ValidationArgument $argument, $result, $validator = null)
    {
        return $this->report->addArgumentResult($argument, $result, $validator);
    }

    /**
     * Will return the highest error code for a field. This can be optionally
     * limited to the highest error code of an validator. If the field was not
     * "touched" by a validator null is returned.
     *
     * @param      string $fieldname The name of the field.
     * @param      string $validatorName The Validator name
     *
     * @return     int The error code.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     *
     * @deprecated 1.0.0
     */
    public function getFieldErrorCode($fieldname, $validatorName = null)
    {
        return $this->report->getAuthoritativeArgumentSeverity(new ValidationArgument($fieldname), $validatorName);
    }

    /**
     * Checks whether a field has failed in any validator.
     *
     * @param      string $fieldname The name of the field.
     *
     * @return     bool Whether the field has failed.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     *
     * @deprecated 1.0.0
     */
    public function isFieldFailed($fieldname)
    {
        return $this->report->isArgumentFailed(new ValidationArgument($fieldname));
    }

    /**
     * Checks whether a field has been processed by a validator (this includes
     * fields which were skipped because their value was not set and the validator
     * was not required)
     *
     * @param      string $fieldname The name of the field.
     *
     * @return     bool Whether the field was validated.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     *
     * @deprecated 1.0.0
     */
    public function isFieldValidated($fieldname)
    {
        return $this->report->isArgumentValidated(new ValidationArgument($fieldname));
    }

    /**
     * Returns all fields which succeeded in the validation. Includes fields which
     * were not processed (happens when the field is "not set" and the validator
     * is not required)
     *
     * @param      string $source The source for which the fields should be returned.
     *
     * @return     array An array of field names.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     *
     * @deprecated 1.0.0
     */
    public function getSucceededFields($source)
    {
        $names = array();
        $arguments = $this->report->getSucceededArguments($source);
        foreach ($arguments as $argument) {
            $names[] = $argument->getName();
        }
        
        return $names;
    }
    
    /**
     * Checks if any incidents occurred Returns all fields which succeeded in the
     * validation. Includes fields which were not processed (happens when the
     * field is "not set" and the validator is not required)
     *
     * @param      int $minSeverity The minimum severity which shall be checked for.
     *
     * @return     bool The result.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     *
     * @deprecated 1.0.0
     */
    public function hasIncidents($minSeverity = null)
    {
        return count($this->getIncidents($minSeverity)) > 0;
    }

    /**
     * Returns all incidents which happened during the execution of the validation.
     *
     * @param      int $minSeverity The minimum severity a returned incident needs to have.
     *
     * @return     ValidationIncident[] The incidents.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     *
     * @deprecated 1.0.0
     */
    public function getIncidents($minSeverity = null)
    {
        $incidents = array();
        if ($minSeverity === null) {
            return $this->report->getIncidents();
        } else {
            foreach ($this->report->getIncidents() as $incident) {
                if ($incident->getSeverity() >= $minSeverity) {
                    $incidents[] = $incident;
                }
            }
        }
        return $incidents;
    }

    /**
     * Returns all incidents of a given validator.
     *
     * @param      string $validatorName The name of the validator.
     * @param      int $minSeverity The minimum severity a returned incident needs to have.
     *
     * @return     ValidationIncident[] The incidents.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     *
     * @deprecated 1.0.0
     */
    public function getValidatorIncidents($validatorName, $minSeverity = null)
    {
        $incidents = $this->report->byValidator($validatorName)->getIncidents();
        
        if ($minSeverity === null) {
            return $incidents;
        } else {
            $matchingIncidents = array();
            foreach ($incidents as $incident) {
                if ($incident->getSeverity() >= $minSeverity) {
                    $matchingIncidents[] = $incident;
                }
            }
            return $matchingIncidents;
        }
    }
    /**
     * Returns all incidents of a given field.
     *
     * @param      string $fieldname The name of the field.
     * @param      int $minSeverity The minimum severity a returned incident needs to have.
     *
     * @return     ValidationIncident[] The incidents.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     *
     * @deprecated 1.0.0
     */
    public function getFieldIncidents($fieldname, $minSeverity = null)
    {
        $incidents = $this->report->byArgument($fieldname)->getIncidents();
        
        if ($minSeverity === null) {
            return $incidents;
        } else {
            $matchingIncidents = array();
            foreach ($incidents as $incident) {
                if ($incident->getSeverity() >= $minSeverity) {
                    $matchingIncidents[] = $incident;
                }
            }
            return $matchingIncidents;
        }
    }

    /**
     * Returns all errors of a given field.
     *
     * @param      string $fieldname The name of the field.
     * @param      int $minSeverity The minimum severity a returned incident of the error
     *                 needs to have.
     *
     * @return     ValidationError[] The errors.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     *
     * @deprecated 1.0.0
     */
    public function getFieldErrors($fieldname, $minSeverity = null)
    {
        $incidents = $this->getFieldIncidents($fieldname, $minSeverity);
        $errors = array();
        foreach ($incidents as $incident) {
            $errors = array_merge($errors, $incident->getErrors());
        }
        return $errors;
    }

    /**
     * Returns all errors of a given field in a given validator.
     *
     * @param      string $validatorName The name of the field.
     * @param      int $minSeverity The minimum severity a returned incident of the error
     *                 needs to have.
     *
     * @return     ValidationIncident[] The incidents.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     *
     * @deprecated 1.0.0
     */
    public function getValidatorFieldErrors($validatorName, $fieldname, $minSeverity = null)
    {
        $incidents = $this->getFieldIncidents($fieldname, $minSeverity);
        $matchingIncidents = array();
        foreach ($incidents as $incident) {
            $validator = $incident->getValidator();
            if ($validator && $validator->getName() == $validatorName) {
                $matchingIncidents[] = $incident;
            }
        }
        return $matchingIncidents;
    }

    /**
     * Returns all failed fields (this are all fields including those with
     * severity none and notice).
     *
     * @return     array The names of the fields.
     * @param      int $minSeverity The minimum severity a field needs to have.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     *
     * @deprecated 1.0.0
     */
    public function getFailedFields($minSeverity = null)
    {
        $fields = array();
        foreach ($this->getIncidents($minSeverity) as $incident) {
            $fields = array_merge($fields, $incident->getFields());
        }
        
        return array_values(array_unique($fields));
    }
    
    /**
     * Retrieve an error message.
     *
     * @param      string $name An error name.
     *
     * @return     string The error message.
     *
     * @author     Sean Kerr <skerr@mojavi.org>
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.9.0
     *
     * @deprecated 1.0.0
     */
    public function getError($name)
    {
        $incidents = $this->getFieldIncidents($name, Validator::NOTICE);

        if (count($incidents) == 0) {
            return null;
        }

        $errors = $incidents[0]->getErrors();
        return $errors[0]->getMessage();
    }

    /**
     * Retrieve an array of error names.
     *
     * @return     array An indexed array of error names.
     *
     * @author     Sean Kerr <skerr@mojavi.org>
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.9.0
     *
     * @deprecated 1.0.0
     */
    public function getErrorNames()
    {
        return $this->getFailedFields();
    }

    /**
     * Retrieve an array of errors.
     *
     * @param      string $name An optional error name.
     *
     * @return     array An associative array of errors(if no name was given) as
     *                   an array with the error messages (key 'messages') and
     *                   the validators (key 'validators') which failed.
     *
     * @author     Sean Kerr <skerr@mojavi.org>
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.9.0
     *
     * @deprecated 1.0.0
     */
    public function getErrors($name = null)
    {
        $errors = array();

        foreach ($this->getIncidents(Validator::NOTICE) as $incident) {
            $validator = $incident->getValidator();
            foreach ($incident->getErrors() as $error) {
                $msg = $error->getMessage();
                foreach ($error->getFields() as $field) {
                    if (!isset($errors[$field])) {
                        $errors[$field] = array('messages' => array(), 'validators' => array());
                    }
                    $errors[$field]['messages'][] = $msg;
                    if ($validator) {
                        $errors[$field]['validators'][] = $validator->getName();
                    }
                }
            }
        }

        if ($name === null) {
            return $errors;
        } else {
            return isset($errors[$name]) ? $errors[$name] : null;
        }
    }

    /**
     * Retrieve an array of error Messages.
     *
     * @param      string $name An optional error name.
     *
     * @return     array An indexed array of error messages (if a name was given)
     *                   or an indexed array in this format:
     *                   array('message' => string, 'errors' => array(string))
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.11.0
     *
     * @deprecated 1.0.0
     */
    public function getErrorMessages($name = null)
    {

        if ($name !== null) {
            $incidents = $this->getFieldIncidents($name, Validator::NOTICE);
            $msgs = array();
            foreach ($incidents as $incident) {
                foreach ($incident->getErrors() as $error) {
                    $msgs[] = $error->getMessage();
                }
            }
            return $msgs;
        } else {
            $incidents = $this->getIncidents(Validator::NOTICE);
            $msgs = array();
            foreach ($incidents as $incident) {
                foreach ($incident->getErrors() as $error) {
                    $msgs[] = array('message' => $error->getMessage(), 'errors' => $error->getFields());
                }
            }
            return $msgs;
        }
    }

    /**
     * Indicates whether or not a field has an error.
     *
     * @param      string $name A field name.
     *
     * @return     bool true, if the field has an error, false otherwise.
     *
     * @author     Sean Kerr <skerr@mojavi.org>
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @author     David Zülke <david.zuelke@bitextender.com>
     * @since      0.9.0
     *
     * @deprecated 1.0.0
     */
    public function hasError($name)
    {
        $ec = $this->getFieldErrorCode($name);
        // greater than or equal to notice cause that's when we need to show an error (this is different to hasErrors() behavior due to legacy)
        return ($ec >= Validator::NOTICE);
    }

    /**
     * Indicates whether or not any errors exist.
     *
     * @return     bool true, if any error exist, otherwise false.
     *
     * @author     Sean Kerr <skerr@mojavi.org>
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.9.0
     *
     * @deprecated 1.0.0
     */
    public function hasErrors()
    {
        // anything above notice. just notice means validation didn't fail, although a notice is considered an error itself. but notices only "show up" if other validators with higher severity (error, fatal) failed
        return $this->getResult() > Validator::NOTICE;
    }

    /**
     * Set an error.
     *
     * @param      string $name An error name.
     * @param      string $message An error message.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.9.0
     *
     * @deprecated 1.0.0
     */
    public function setError($name, $message)
    {
        $name = new ValidationArgument($name);
        $incident = new ValidationIncident(null, Validator::ERROR);
        $incident->addError(new ValidationError($message, null, array($name)));
        $this->addIncident($incident);
    }

    /**
     * Set an array of errors
     *
     * If an existing error name matches any of the keys in the supplied
     * array, the associated message will be appended to the messages array.
     *
     * @param      array $errors An associative array of errors and their associated
     *                   messages.
     *
     * @author     Dominik del Bondio <ddb@bitxtender.com>
     * @since      0.9.0
     *
     * @deprecated 1.0.0
     */
    public function setErrors(array $errors)
    {
        $incident = new ValidationIncident(null, Validator::ERROR);
        foreach ($errors as $name => $error) {
            $name = new ValidationArgument($name);
            $incident->addError(new ValidationError($error, null, array($name)));
        }

        $this->addIncident($incident);
    }
}
