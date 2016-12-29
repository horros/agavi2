<?php
/**
 * Created by PhpStorm.
 * User: markus
 * Date: 21/12/2016
 * Time: 17:16
 */

namespace Agavi\Build\Console\Command;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

class ModuleCreate extends AgaviCommand
{

	protected function configure() {
		$this->setName('agavi:module')
			->setDescription('Create module')
			->addOption('settings', null, InputOption::VALUE_REQUIRED, '.settings.yml to read configuration from')
			->addArgument('module', InputArgument::OPTIONAL, 'The name of the module. If it\'s not provided, it will be asked for.');
	}

	public function execute(InputInterface $input, OutputInterface $output) {

		if ($input->hasOption('settings') && $input->getOption('settings') != null) {
			$settings = $input->getOption('settings');
		} else {
			$settings = '.' . DIRECTORY_SEPARATOR . '.settings.yml';
		}

		if (!file_exists($settings)) {
			throw new InvalidArgumentException(sprintf('Cannot find settings file "%s"', $settings));
		}

		$helper = $this->getHelper('question');

		$data = Yaml::parse(file_get_contents($settings));

		if (!is_array($data)) {
			throw new InvalidArgumentException(sprintf('Error parsing settings file "%s". Return value unexpected. Expected array, got %s', $settings, gettype($data)));
		}

		if (!isset($data['project']['prefix'])) {
			throw new InvalidArgumentException(sprintf('No project prefix found in settings file "%s"', $settings));
		}
		$projectLocation = (is_array($data) && isset($data['project']['location']) ? $data['project']['location'] : '.');

		if ($input->hasArgument('module') && $input->getArgument('module') != null) {
			$module = $input->getArgument('module');
		} else {
			$question = new Question("Module name: ");
			$module = $helper->ask($input, $output, $question);
		}

		$module = ucfirst(TransformIdentifier::transform($module));

		if (file_exists($projectLocation . '/app/modules/' . $module)) {
			throw new InvalidArgumentException(sprintf('Module "%s" already exists in "%s"', $module, implode(DIRECTORY_SEPARATOR, [$projectLocation, 'app', 'modules', $module])));
		}

		$defaultParams = [
			'projectLocation' => $projectLocation,
			'projectName' => $data['project']['name'],
			'projectPrefix' => $data['project']['prefix'],
			'moduleName' => $module
		];

		@mkdir($projectLocation . '/app/modules/' . $module, 0755, true);
		@mkdir($projectLocation . '/app/modules/' . $module . '/cache', 0755, true);
		@mkdir($projectLocation . '/app/modules/' . $module . '/config', 0755, true);
		@mkdir($projectLocation . '/app/modules/' . $module . '/controllers', 0755, true);
		@mkdir($projectLocation . '/app/modules/' . $module . '/lib', 0755, true);
		@mkdir($projectLocation . '/app/modules/' . $module . '/validate', 0755, true);
		@mkdir($projectLocation . '/app/modules/' . $module . '/views', 0755, true);
		@mkdir($projectLocation . '/app/modules/' . $module . '/templates', 0755, true);
		@mkdir($projectLocation . '/app/modules/' . $module . '/models', 0755, true);

		$fc = new FileCopyHelper();

		// Config
		foreach (glob($this->getSourceDir() . '/build/templates/app/modules/config/*.xml.tmpl') as $file) {
			$fc->copy($file, $projectLocation . '/app/modules/' . $module . '/config/' . basename($file, '.tmpl'),
				function ($data, $params) {
					return $this->moduleTokenReplacer($data, $params);
				}, $defaultParams);

		}

		// Lib
		@mkdir($projectLocation . '/app/modules/' . $module . '/lib/controller', 0755, true);
		foreach (glob($this->getSourceDir() . '/build/templates/app/modules/lib/controller/*.tmpl') as $file) {
			$fc->copy($file, $projectLocation . '/app/modules/' . $module . '/lib/controller/' . $data['project']['prefix'] . $module . basename($file, '.tmpl'),
				function ($data, $params) {
					return $this->moduleTokenReplacer($data, $params);
				}, $defaultParams);

		}
		@mkdir($projectLocation . '/app/modules/' . $module . '/lib/model', 0755, true);
		foreach (glob($this->getSourceDir() . '/build/templates/app/modules/lib/model/*.tmpl') as $file) {
			$fc->copy($file, $projectLocation . '/app/modules/' . $module . '/lib/model/' . $data['project']['prefix'] . $module . basename($file, '.tmpl'),
				function ($data, $params) {
					return $this->moduleTokenReplacer($data, $params);
				}, $defaultParams);

		}
		@mkdir($projectLocation . '/app/modules/' . $module . '/lib/view', 0755, true);
		foreach (glob($this->getSourceDir() . '/build/templates/app/modules/lib/view/*.tmpl') as $file) {
			$fc->copy($file, $projectLocation . '/app/modules/' . $module . '/lib/view/' . $data['project']['prefix'] . $module . basename($file, '.tmpl'),
				function ($data, $params) {
					return $this->moduleTokenReplacer($data, $params);
				}, $defaultParams);

		}


	}

	public function moduleTokenReplacer($data, $params)
	{
		return str_replace([
			'%%AGAVI_SOURCE_LOCATION%%',
			'%%PROJECT_LOCATION%%',
			'%%PROJECT_NAME%%',
			'%%PROJECT_PREFIX%%',
			'%%MODULE_NAME%%',
			'%%MODULE_CONTROLLER_PATH%%',
			'%%MODULE_VIEW_PATH%%',
			'%%MODULE_VIEW_NAME%%',
			'%%MODULE_CACHE_PATH%%',
			'%%MODULE_VALIDATE_PATH%%',
			'%%MODULE_TEMPLATES_DIRECTORY%%'
		], [
			$this->getSourceDir(),
			$params['projectLocation'],
			$params['projectName'],
			$params['projectPrefix'],
			$params['moduleName'],
			'%core.module_dir%/${moduleName}/controllers/${controllerName}Controller.class.php',
			'%core.module_dir%/${moduleName}/views/${viewName}View.class.php',
			'${controllerName}${viewName}',
			'%core.module_dir%/${moduleName}/cache/${controllerName}.xml',
			'%core.module_dir%/${moduleName}/validate/${controllerName}.xml',
			'%core.module_dir%/${module}/templates'
		], $data);
	}
}