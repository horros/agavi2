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
 * Configures an Agavi module by reading the module's configuration file.
 *
 * @package    agavi
 * @subpackage build
 *
 * @author     Noah Fontes <noah.fontes@bitextender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      1.0.0
 *
 * @version    $Id$
 */
class ConfiguremoduleTask extends AgaviTask
{
	protected $name;
	protected $prefix = 'module';
	
	/**
	 * Sets the module name.
	 *
	 * @param      string The module name.
	 */
	public function setName($name)
	{
		$this->name = $name;
	}
	
	/**
	 * Sets the property prefix.
	 *
	 * @param      string The prefix.
	 */
	public function setPrefix($prefix)
	{
		$this->prefix = $prefix;
	}
	
	/**
	 * Executes the task.
	 */
	public function main()
	{
		if($this->name === null) {
			throw new BuildException('The name attribute must be specified');
		}
		
		$this->tryLoadAgavi();
		$this->tryBootstrapAgavi();
		
		/* Oookay. This is interesting. */

		$moduleName = $this->name;
		require_once(\Agavi\Config\ConfigCache::checkConfig(
			sprintf('%s/%s/%s/%s/module.xml',
				(string)$this->project->getProperty('project.directory'),
				(string)$this->project->getProperty('project.directory.app.modules'),
				$this->name,
				(string)$this->project->getProperty('module.config.directory')
			)
		));
		
		/* Set up us the values.
		 *
		 * XXX: With regards to the defaults:
		 *
		 * You might expect to use the <property>.default properties defined in
		 * build.xml. But this is not so; consider that someone might have decided
		 * to upgrade their project properties but still have some legacy modules
		 * lying around. We need to use the actual Agavi defaults to ensure
		 * consistency.
		 *
		 * If you change this, you're fucking asking for it. */
		$values = array();
		$lowerModuleName = strtolower($moduleName);
		
		$values['controller.path'] = \Agavi\Config\Config::get(
			sprintf('modules.%s.agavi.controller.path', $lowerModuleName),
			'%core.module_dir%/${moduleName}/controllers/${controllerName}Controller.class.php'
		);
		$values['controller.path'] = \Agavi\Util\Toolkit::expandVariables(
			$values['controller.path'],
			array('moduleName' => $moduleName)
		);
		
		$values['cache.path'] = \Agavi\Config\Config::get(
			sprintf('modules.%s.agavi.cache.path', $lowerModuleName),
			'%core.module_dir%/${moduleName}/cache/${controllerName}.xml'
		);
		$values['cache.path'] = \Agavi\Util\Toolkit::expandVariables(
			$values['cache.path'],
			array('moduleName' => $moduleName)
		);
		
		$values['templates.directory'] = \Agavi\Config\Config::get(
			sprintf('modules.%s.agavi.template.directory', $lowerModuleName),
			'%core.module_dir%/${module}/templates'
		);
		$values['templates.directory'] = \Agavi\Util\Toolkit::expandVariables(
			$values['templates.directory'],
			array('module' => $moduleName)
		);
		
		$values['validate.path'] = \Agavi\Config\Config::get(
			sprintf('modules.%s.agavi.validate.path', $lowerModuleName),
			'%core.module_dir%/${moduleName}/validate/${controllerName}.xml'
		);
		$values['validate.path'] = \Agavi\Util\Toolkit::expandVariables(
			$values['validate.path'],
			array('moduleName' => $moduleName)
		);
		
		$values['view.path'] = \Agavi\Config\Config::get(
			sprintf('modules.%s.agavi.view.path', $lowerModuleName),
			'%core.module_dir%/${moduleName}/views/${viewName}View.class.php'
		);
		$values['view.path'] = \Agavi\Util\Toolkit::expandVariables(
			$values['view.path'],
			array('moduleName' => $moduleName)
		);
		
		$values['view.name'] = \Agavi\Config\Config::get(
			sprintf('modules.%s.agavi.view.name', $lowerModuleName),
			'${controllerName}${viewName}'
		);
		
		/* Main screen turn on. */
		foreach($values as $name => $value) {
			$this->project->setUserProperty(sprintf('%s.%s', $this->prefix, $name), $value);
		}
	}
}

?>