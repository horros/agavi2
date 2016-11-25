<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2005-2011 the Agavi Project.                                |
// | Based on the Mojavi3 MVC Framework, Copyright (c) 2003-2005 Sean Kerr.    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org/LICENSE.txt                   |
// |   vi: set noexpandtab:                                                    |
// |   Local Variables:                                                        |
// |   indent-tabs-mode: t                                                     |
// |   End:                                                                    |
// +---------------------------------------------------------------------------+

namespace Agavi\Controller;

use Agavi\Action\Action;
use Agavi\Config\Config;
use Agavi\Config\ConfigCache;
use Agavi\Exception\AgaviException;
use Agavi\Exception\ClassNotFoundException;
use Agavi\Exception\FileNotFoundException;
use Agavi\Filter\FilterChain;
use Agavi\Filter\Filter;
use Agavi\Request\RequestDataHolder;
use Agavi\Response\Response;
use Agavi\Util\ParameterHolder;
use Agavi\Exception\ControllerException;
use Agavi\Core\Context;
use Agavi\Exception\DisabledModuleException;
use Agavi\Util\Toolkit;
use Agavi\View\View;

/**
 * Controller directs application flow.
 *
 * @package    agavi
 * @subpackage controller
 *
 * @author     Sean Kerr <skerr@mojavi.org>
 * @author     David Zülke <dz@bitxtender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.9.0
 *
 * @version    $Id$
 */
class Controller extends ParameterHolder
{
	/**
	 * @var        int The number of execution containers run so far.
	 */
	protected $numExecutions = 0;
	
	/**
	 * @var        Context An Context instance.
	 */
	protected $context = null;
	
	/**
	 * @var        Response The global response.
	 */
	protected $response = null;
	
	/**
	 * @var        FilterChain The global filter chain.
	 */
	protected $filterChain = null;
	
	/**
	 * @var        array An array of filter instances for reuse.
	 */
	protected $filters = array(
		'global' => array(),
		'action' => array(
			'*' => null
		),
		'dispatch' => null,
		'execution' => null,
		'security' => null
	);
	
	/**
	 * @var        string The default Output Type.
	 */
	protected $defaultOutputType = null;
	
	/**
	 * @var        array An array of registered Output Types.
	 */
	protected $outputTypes = array();
	
	/**
	 * @var        array Ref to the request data object from the request.
	 */
	private $requestData = null;
	
	/**
	 * Increment the execution counter.
	 * Will throw an exception if the maximum amount of runs is exceeded.
	 *
	 * @throws     ControllerException If too many execution runs were made.
	 *
	 * @author     David Zülke <dz@bitxtender.com>
	 * @since      0.11.0
	 */
	public function countExecution()
	{
		$maxExecutions = $this->getParameter('max_executions');
		
		if(++$this->numExecutions > $maxExecutions && $maxExecutions > 0) {
			throw new ControllerException('Too many execution runs have been detected for this Context.');
		}
	}
	
	/**
	 * Create and initialize new execution container instance.
	 *
	 * @param      string                 $moduleName    The name of the module.
	 * @param      string                 $actionName    The name of the action.
	 * @param      RequestDataHolder      $arguments     A RequestDataHolder with additional
	 *                                    request arguments.
	 * @param      string                 $outputType    Optional name of an initial output type
	 *                                    to set.
	 * @param      string                 $requestMethod Optional name of the request method to
	 *                                    be used in this container.
	 *
	 * @return     ExecutionContainer A new execution container instance,
	 *                                     fully initialized.
	 *
	 * @author     David Zülke <dz@bitxtender.com>
	 * @since      0.11.0
	 */
	public function createExecutionContainer($moduleName = null, $actionName = null, RequestDataHolder $arguments = null, $outputType = null, $requestMethod = null)
	{
		// create a new execution container
		$container = $this->context->createInstanceFor('execution_container');
		$container->setModuleName($moduleName);
		$container->setActionName($actionName);
		$container->setRequestData($this->requestData);
		if($arguments !== null) {
			$container->setArguments($arguments);
		}
		$container->setOutputType($this->context->getController()->getOutputType($outputType));
		if($requestMethod === null) {
			$requestMethod = $this->context->getRequest()->getMethod();
		}
		$container->setRequestMethod($requestMethod);
		return $container;
	}
	
	/**
	 * Initialize a module and load its autoload, module config etc.
	 *
	 * @param      string $moduleName The name of the module to initialize.
	 *
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	public function initializeModule($moduleName)
	{
		$lowerModuleName = strtolower($moduleName);
		
		if(null === Config::get('modules.' . $lowerModuleName . '.enabled')) {
			// set some defaults first
			Config::fromArray(array(
				'modules.' . $lowerModuleName . '.agavi.action.path' => '%core.module_dir%/${moduleName}/actions/${actionName}Action.class.php',
				'modules.' . $lowerModuleName . '.agavi.cache.path' => '%core.module_dir%/${moduleName}/cache/${actionName}.xml',
				'modules.' . $lowerModuleName . '.agavi.template.directory' => '%core.module_dir%/${module}/templates',
				'modules.' . $lowerModuleName . '.agavi.validate.path' => '%core.module_dir%/${moduleName}/validate/${actionName}.xml',
				'modules.' . $lowerModuleName . '.agavi.view.path' => '%core.module_dir%/${moduleName}/views/${viewName}View.class.php',
				'modules.' . $lowerModuleName . '.agavi.view.name' => '${actionName}${viewName}',
			));
			// include the module configuration
			// loaded only once due to the way load() (former import()) works
			if(is_readable(Config::get('core.module_dir') . '/' . $moduleName . '/config/module.xml')) {
				include_once(ConfigCache::checkConfig(Config::get('core.module_dir') . '/' . $moduleName . '/config/module.xml'));
			} else {
				Config::set('modules.' . $lowerModuleName . '.enabled', true);
			}
			
			$moduleAutoload = Config::get('core.module_dir') . '/' . $moduleName . '/config/autoload.xml';
			if(is_readable($moduleAutoload)) {
				ConfigCache::load($moduleAutoload);
			}
			
			if(Config::get('modules.' . $lowerModuleName . '.enabled')) {
				$moduleConfigHandlers = Config::get('core.module_dir') . '/' . $moduleName . '/config/config_handlers.xml';
				if(is_readable($moduleConfigHandlers)) {
					ConfigCache::addConfigHandlersFile($moduleConfigHandlers);
				}
			}
		}
		
		if(!Config::get('modules.' . $lowerModuleName . '.enabled')) {
			throw new DisabledModuleException(sprintf('The module "%1$s" is disabled.', $moduleName));
		}
		
		// check for a module config.php
		$moduleConfig = Config::get('core.module_dir') . '/' . $moduleName . '/config.php';
		if(is_readable($moduleConfig)) {
			require_once($moduleConfig);
		}
	}
	
	/**
	 * Dispatch a request
	 *
	 * @param      RequestDataHolder  $arguments An optional request data holder object
	 *                                with additional request data.
	 * @param      ExecutionContainer $container An optional execution container that,
	 *                                if given, will be executed right away,
	 *                                skipping routing execution.
	 *
	 * @return     Response The response produced during this dispatch call.
	 *
	 * @author     David Zülke <dz@bitxtender.com>
	 * @since      0.9.0
	 */
	public function dispatch(RequestDataHolder $arguments = null, ExecutionContainer $container = null)
	{
		try {
			
			$rq = $this->context->getRequest();
			$rd = $rq->getRequestData();
			
			if($container === null) {
				// match routes and assign returned initial execution container
				$container = $this->context->getRouting()->execute();
			}
			
			if($container instanceof ExecutionContainer) {
				// merge in any arguments given. they need to have precedence over what the routing found
				if($arguments !== null) {
					$rd->merge($arguments);
				}
				
				// next, we have to see if the routing did anything useful, i.e. whether or not it was enabled.
				$moduleName = $container->getModuleName();
				$actionName = $container->getActionName();
				if(!$moduleName) {
					// no module has been specified; that means the routing did not run, as it would otherwise have the 404 action's module name
					
					// lets see if our request data has values for module and action
					$ma = $rq->getParameter('module_accessor');
					$aa = $rq->getParameter('action_accessor');
					if($rd->hasParameter($ma) && $rd->hasParameter($aa)) {
						// yup. grab those
						$moduleName = $rd->getParameter($ma);
						$actionName = $rd->getParameter($aa);
					} else {
						// nope. then its time for the default action
						$moduleName = Config::get('actions.default_module');
						$actionName = Config::get('actions.default_action');
					}
					
					// so by now we hopefully have something reasonable for module and action names - let's set them on the container
					$container->setModuleName($moduleName);
					$container->setActionName($actionName);
				}
				
				if(!Config::get('core.available', false)) {
					$container = $container->createSystemActionForwardContainer('unavailable');
				}
				
				// create a new filter chain
				/** @var FilterChain $filterChain */
				$filterChain = $this->getFilterChain();
				
				$this->loadFilters($filterChain, 'global');
				
				// register the dispatch filter
				$filterChain->register($this->filters['dispatch'], 'agavi_dispatch_filter');
				
				// go, go, go!
				$filterChain->execute($container);
				
				$response = $container->getResponse();
			} elseif($container instanceof Response) {
				// the routing returned a response!
				$response = $container;
				// set $container to null so Exception::render() won't think it is a container if an exception happens later!
				$container = null;
			} else {
				throw new AgaviException('AgaviRouting::execute() returned neither ExecutionContainer nor Response object.');
			}
			$response->merge($this->response);
			
			if($this->getParameter('send_response')) {
				$response->send();
			}
			
			return $response;
			
		} catch(\Exception $e) {
			AgaviException::render($e, $this->context, $container);
		}
	}
	
	/**
	 * Get the global response instance.
	 *
	 * @return     Response The global response.
	 *
	 * @author     David Zülke <dz@bitxtender.com>
	 * @since      0.11.0
	 */
	public function getGlobalResponse()
	{
		return $this->response;
	}
	
	
	/**
	 * Indicates whether or not a module has a specific action file.
	 * 
	 * Please note that this is only a cursory check and does not 
	 * check whether the file actually contains the proper class
	 *
	 * @param      string $moduleName A module name.
	 * @param      string $actionName An action name.
	 *
	 * @return     mixed  the path to the action file if the action file 
	 *                    exists and is readable, false in any other case
	 *
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	public function checkActionFile($moduleName, $actionName)
	{
		$this->initializeModule($moduleName);
		
		$actionName = Toolkit::canonicalName($actionName);
		$file = Toolkit::evaluateModuleDirective(
			$moduleName,
			'agavi.action.path',
			array(
				'moduleName' => $moduleName,
				'actionName' => $actionName,
			)
		);
		
		if(is_readable($file) && substr($actionName, 0, 1) !== '/') {
			return $file;
		}
		
		return false;
	}
	
	/**
	 * Retrieve an Action implementation instance.
	 *
	 * @param      string $moduleName A module name.
	 * @param      string $actionName An action name.
	 *
	 * @return     Action An Action implementation instance
	 *
	 * @throws     AgaviException if the action could not be found.
	 *
	 * @author     Sean Kerr <skerr@mojavi.org>
	 * @author     Mike Vincent <mike@agavi.org>
	 * @author     David Zülke <dz@bitxtender.com>
	 * @since      0.9.0
	 */
	public function createActionInstance($moduleName, $actionName)
	{
		$this->initializeModule($moduleName);
		
		$actionName = Toolkit::canonicalName($actionName);
		$longActionName = str_replace('/', '_', $actionName);
		
		$class = $moduleName . '_' . $longActionName . 'Action';
		
		if(!class_exists($class)) {
			if(false !== ($file = $this->checkActionFile($moduleName, $actionName))) {
				require($file);
			} else {
				throw new FileNotFoundException(sprintf('Could not find file for Action "%s" in Module "%s".', $actionName, $moduleName));
			}
			
			if(!class_exists($class, false)) {
				throw new ClassNotFoundException(sprintf('Failed to instantiate Action "%s" in Module "%s" because file "%s" does not contain class "%s".', $actionName, $moduleName, $file, $class));
			}
		} 
		
		return new $class();
	}

	/**
	 * Retrieve the current application context.
	 *
	 * @return     Context An Context instance.
	 *
	 * @author     Sean Kerr <skerr@mojavi.org>
	 * @since      0.9.0
	 */
	public final function getContext()
	{
		return $this->context;
	}


	
	/**
	 * Indicates whether or not a module has a specific view file.
	 * 
	 * Please note that this is only a cursory check and does not 
	 * check whether the file actually contains the proper class
	 *
	 * @param      string $moduleName A module name.
	 * @param      string $viewName A view name.
	 *
	 * @return     mixed  the path to the view file if the view file 
	 *                    exists and is readable, false in any other case
	 * 
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	public function checkViewFile($moduleName, $viewName)
	{
		$this->initializeModule($moduleName);
		
		$viewName = Toolkit::canonicalName($viewName);
		$file = Toolkit::evaluateModuleDirective(
			$moduleName,
			'agavi.view.path',
			array(
				'moduleName' => $moduleName,
				'viewName' => $viewName,
			)
		);
		
		if(is_readable($file) && substr($viewName, 0, 1) !== '/') {
			return $file;
		}
		
		return false;
	}
	
	/**
	 * Retrieve a View implementation instance.
	 *
	 * @param      string $moduleName A module name.
	 * @param      string $viewName A view name.
	 *
	 * @return     View A View implementation instance,
	 *
	 * @throws     AgaviException if the view could not be found.
	 *
	 * @author     Sean Kerr <skerr@mojavi.org>
	 * @author     Mike Vincent <mike@agavi.org>
	 * @author     David Zülke <dz@bitxtender.com>
	 * @since      0.9.0
	 */
	public function createViewInstance($moduleName, $viewName)
	{
		try {
			$this->initializeModule($moduleName);
		} catch(DisabledModuleException $e) {
			// views from disabled modules should be usable by definition
			// swallow
		}
		
		$viewName = Toolkit::canonicalName($viewName);
		$longViewName = str_replace('/', '_', $viewName);
		
		$class = $moduleName . '_' . $longViewName . 'View';
		
		if(!class_exists($class)) {
			
			if(false !== ($file = $this->checkViewFile($moduleName, $viewName))) {
				require($file);
			} else {
				throw new FileNotFoundException(sprintf('Could not find file for View "%s" in Module "%s".', $viewName, $moduleName));
			}
			
			if(!class_exists($class, false)) {
				throw new ClassNotFoundException(sprintf('Failed to instantiate View "%s" in Module "%s" because file "%s" does not contain class "%s".', $viewName, $moduleName, $file, $class));
			}
		} 
		
		return new $class();
	}

	/**
	 * Constructor.
	 *
	 * @author     David Zülke <dz@bitxtender.com>
	 * @since      0.11.0
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setParameters(array(
			'max_executions' => 20,
			'send_response' => true,
		));
	}
	
	/**
	 * Initialize this controller.
	 *
	 * @param      Context $context A Context instance.
	 * @param      array   $parameters An array of initialization parameters.
	 *
	 * @author     David Zülke <dz@bitxtender.com>
	 * @since      0.9.0
	 */
	public function initialize(Context $context, array $parameters = array())
	{
		$this->context = $context;
		
		$this->setParameters($parameters);
		
		$this->response = $this->context->createInstanceFor('response');
		
		$cfg = Config::get('core.config_dir') . '/output_types.xml';
		require(ConfigCache::checkConfig($cfg, $this->context->getName()));
		
		if(Config::get('core.use_security', false)) {
			$this->filters['security'] = $this->context->createInstanceFor('security_filter');
		}
		
		$this->filters['dispatch'] = $this->context->createInstanceFor('dispatch_filter');
		
		$this->filters['execution'] = $this->context->createInstanceFor('execution_filter');
	}
	
	/**
	 * Get a filter.
	 *
	 * @param      string $which The name of the filter list section.
	 *
	 * @return     Filter A filter instance, or null.
	 *
	 * @author     David Zülke <dz@bitxtender.com>
	 * @since      0.11.0
	 */
	public function getFilter($which)
	{
		return (isset($this->filters[$which]) ? $this->filters[$which] : null);
	}
	
	/**
	 * Get the global filter chain.
	 *
	 * @return     FilterChain The global filter chain.
	 *
	 * @author     David Zülke <david.zuelke@bitextender.com>
	 * @since      1.1.0
	 */
	public function getFilterChain()
	{
		if($this->filterChain === null) {
			$this->filterChain = $this->context->createInstanceFor('filter_chain');
			$this->filterChain->setType(FilterChain::TYPE_GLOBAL);
		}
		
		return $this->filterChain;
	}
	
	/**
	 * Load filters.
	 *
	 * @param      FilterChain $filterChain A FilterChain instance.
	 * @param      string      $which "global" or "action".
	 * @param      string      $module A module name, or "*" for the generic config.
	 *
	 * @author     David Zülke <dz@bitxtender.com>
	 * @since      0.11.0
	 */
	public function loadFilters(FilterChain $filterChain, $which = 'global', $module = null)
	{
		if($module === null) {
			$module = '*';
		}
		
		if(($which != 'global' && !isset($this->filters[$which][$module])) || $which == 'global' && $this->filters[$which] == null) {
			if($which == 'global') {
				$this->filters[$which] = array();
				$filters =& $this->filters[$which];
			} else {
				$this->filters[$which][$module] = array();
				$filters =& $this->filters[$which][$module];
			}
			$config = ($module == '*' ? Config::get('core.config_dir') : Config::get('core.module_dir') . '/' . $module . '/config') . '/' . $which . '_filters.xml';
			if(is_readable($config)) {
				require(ConfigCache::checkConfig($config, $this->context->getName()));
			}
		} else {
			if($which == 'global') {
				$filters =& $this->filters[$which];
			} else {
				$filters =& $this->filters[$which][$module];
			}
		}
		
		foreach($filters as $name => $filter) {
			$filterChain->register($filter, $name);
		}
	}

	/**
	 * Indicates whether or not a module has a specific model.
	 *
	 * @param      string $moduleName A module name.
	 * @param      string $modelName A model name.
	 *
	 * @return     bool true, if the model exists, otherwise false.
	 *
	 * @author     Sean Kerr <skerr@mojavi.org>
	 * @since      0.9.0
	 */
	public function modelExists($moduleName, $modelName)
	{
		$modelName = Toolkit::canonicalName($modelName);
		$file = Config::get('core.module_dir') . '/' . $moduleName . '/models/' . $modelName .	'Model.class.php';
		return is_readable($file);
	}

	/**
	 * Indicates whether or not a module exists.
	 *
	 * @param      string $moduleName A module name.
	 *
	 * @return     bool true, if the module exists, otherwise false.
	 *
	 * @author     Sean Kerr <skerr@mojavi.org>
	 * @since      0.9.0
	 */
	public function moduleExists($moduleName)
	{
		$file = Config::get('core.module_dir') . '/' . $moduleName . '/config/module.xml';
		return is_readable($file);
	}

	/**
	 * Do any necessary startup work after initialization.
	 *
	 * This method is not called directly after initialize().
	 *
	 * @author     David Zülke <dz@bitxtender.com>
	 * @since      0.11.0
	 */
	public function startup()
	{
		// grab a pointer to the request data
		$this->requestData = $this->context->getRequest()->getRequestData();
	}

	/**
	 * Execute the shutdown procedure for this controller.
	 *
	 * @author     Sean Kerr <skerr@mojavi.org>
	 * @since      0.9.0
	 */
	public function shutdown()
	{
	}

	/**
	 * Indicates whether or not a module has a specific action.
	 *
	 * @param      string $moduleName A module name.
	 * @param      string $actionName A view name.
	 *
	 * @return     bool true, if the action exists, otherwise false.
	 *
	 * @author     David Zülke <david.zuelke@bitextender.com>
	 * @since      1.0.1
	 */
	public function actionExists($moduleName, $actionName)
	{
		return $this->checkActionFile($moduleName, $actionName) !== false;
	}

	/**
	 * Indicates whether or not a module has a specific view.
	 *
	 * @param      string $moduleName A module name.
	 * @param      string $viewName A view name.
	 *
	 * @return     bool true, if the view exists, otherwise false.
	 *
	 * @author     Sean Kerr <skerr@mojavi.org>
	 * @since      0.9.0
	 */
	public function viewExists($moduleName, $viewName)
	{
		return $this->checkViewFile($moduleName, $viewName) !== false;
	}
	
	/**
	 * Retrieve an Output Type object
	 *
	 * @param      string $name The optional output type name.
	 *
	 * @return     OutputType An Output Type object.
	 *
	 * @author     David Zülke <dz@bitxtender.com>
	 * @since      0.11.0
	 */
	public function getOutputType($name = null)
	{
		if($name === null) {
			$name = $this->defaultOutputType;
		}
		if(isset($this->outputTypes[$name])) {
			return $this->outputTypes[$name];
		} else {
			throw new AgaviException('Output Type "' . $name . '" has not been configured.');
		}
	}
}

?>