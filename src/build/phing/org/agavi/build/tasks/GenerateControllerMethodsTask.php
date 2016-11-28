<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2005-2010 the Agavi Project.                                |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org/LICENSE.txt                   |
// |   vi: set noexpandtab:                                                    |
// |   Local Variables:                                                        |
// |   indent-tabs-mode: t                                                     |
// |   End:                                                                    |
// +---------------------------------------------------------------------------+

require_once(__DIR__ . '/AgaviTask.php');

/**
 * Creates the methods to handle requests for an agavi controller.
 *
 * @package    agavi
 * @subpackage build
 *
 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      1.1.0
 *
 * @version    $Id$
 */
class GenerateControllerMethodsTask extends AgaviTask
{
	/**
	 * @var          string The property to modify.
	 */
	protected $property = null;
	
	/**
	 * @var          array the list of request methods to generate handlers for
	 */
	protected $methods = array();
	
	/**
	 * @var          boolean whether the generated controller should be simple
	 */
	protected $isSimple = false;
	
	/**
	 * @var          string the template to use for the handler methods
	 */
	protected $requestMethodTemplate;
	
	/**
	 * @var          string the template to use for the isSimple method
	 */
	protected $simpleMethodTemplate;
	
	/**
	 * Sets the property that this task will modify.
	 *
	 * @param      string The property to modify.
	 */
	public function setProperty($property)
	{
		$this->property = $property;
	}
	
	/**
	 * Sets the methods to generate code for.
	 *
	 * @param      string a space separated list of methodnames.
	 */
	public function setMethods($methodNames)
	{
		if ("" == trim($methodNames)) {
			$this->methods = array();
		} else {
			$this->methods = explode(" ", $methodNames);
		}		
	}
	
	/**
	 * Sets if the controller should be simple.
	 *
	 * @param      boolean true if the controller is simple.
	 */
	public function setSimple($flag)
	{
		$this->isSimple = 'y' == $flag;
	}
	
	/**
	 * Sets the template to use for the request method handling methods.
	 *
	 * @param      string the template path
	 */
	public function setRequestMethodTemplate($path)
	{
		$this->requestMethodTemplate = $path;
	}
	
	/**
	 * Sets the template to use for the isSimple() method.
	 *
	 * @param      string the template path
	 */
	public function setSimpleMethodTemplate($path)
	{
		$this->simpleMethodTemplate = $path;
	}
	
	/**
	 * Executes the task.
	 */
	public function main()
	{
		if($this->property === null) {
			throw new \Agavi\Build\Exception\BuildException('The property attribute must be specified');
		}
		
		if(count($this->methods) > 0 && $this->isSimple) {
			throw new \Agavi\Build\Exception\BuildException('An controller cannot serve request methods and be simple at the same time.');
		}
		
		if($this->requestMethodTemplate === null || !is_readable($this->requestMethodTemplate)) {
			throw new \Agavi\Build\Exception\BuildException(
				sprintf(
					'The requestMethodTemplate attribute must be specified and must point to a readable template file. Current value is "%1$s".',
					$this->requestMethodTemplate
				)
			);
		}
		
		if($this->simpleMethodTemplate === null || !is_readable($this->simpleMethodTemplate)) {
			throw new \Agavi\Build\Exception\BuildException(
				sprintf(
					'The simpleMethodTemplate attribute must be specified and must point to a readable template file. Current value is "%1$s".',
					$this->simpleMethodTemplate
				)
			);
		}
		
		$template = file_get_contents($this->requestMethodTemplate);
		
		$methodDeclarations = '';
		foreach($this->methods as $methodName) {
			$methodDeclarations .= str_replace('%%METHOD_NAME%%', ucfirst($methodName), $template);
		}
		
		if($this->isSimple) {
			$methodDeclarations .= file_get_contents($this->simpleMethodTemplate);
		}
	
		$this->project->setUserProperty($this->property, $methodDeclarations);
		return;
	}
}

?>