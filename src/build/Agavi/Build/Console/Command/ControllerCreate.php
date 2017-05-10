<?php
// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2005-2016 the Agavi Project.                                |
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
 * Create controller, view and template
 *
 * @author     Markus Lervik <markuslervik1234@gmail.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      2.0.0
 **/

namespace Agavi\Build\Console\Command;


use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

class ControllerCreate extends AgaviCommand
{
	protected function configure()
	{
		$this->setName('agavi:controller')
			->setDescription('Create controller')
			->addArgument('name', InputArgument::OPTIONAL, 'The name of the controller. If it\'s not provided, it will be asked for.')
			->addOption('module', 'm', InputOption::VALUE_REQUIRED, 'The module that the controller belongs to. If it\'s not provided, it will be asked for.')
			->addOption('settings', 's', InputOption::VALUE_REQUIRED, '.settings.yml to read configuration from')
			->addOption('simple', null, InputOption::VALUE_NONE, 'Create a simple action')
			->addOption('methods', null, InputOption::VALUE_REQUIRED, 'Quoted space separated list of request methods that the controller should support. If it\'s not provided, it will be asked for.')
			->addOption('views', null, InputOption::VALUE_REQUIRED, 'Quoted space separated list of views the controller should have. If it\'s not provided, it will be asked for.')
			->addOption('output-types', 'ot', InputOption::VALUE_REQUIRED, 'Quoted space separated list of views the controller should have (format: <view>:<output-type>, eg. input:html success:html success:json). If it\'s not provided, it will be asked for.')
			->addOption('system', null, InputOption::VALUE_REQUIRED, 'This is a system controller, value must be one of "default", "error_404", "secure", "login", "unavailable" or "module_disabled"');
	}

	public function execute(InputInterface $input, OutputInterface $output)
	{

		if ($input->hasOption('settings') && $input->getOption('settings') != null) {
			$settingsFile = $input->getOption('settings');
		} else {
			$settingsFile = '.' . DIRECTORY_SEPARATOR . '.settings.yml';
		}

		if (!file_exists($settingsFile)) {
			throw new InvalidArgumentException(sprintf('Cannot find settings file "%s"', $settingsFile));
		}

		$helper = $this->getHelper('question');

		$settings = Yaml::parse(file_get_contents($settingsFile));

		if (!is_array($settings)) {
			throw new InvalidArgumentException(sprintf('Error parsing settings file "%s". Return value unexpected. Expected array, got %s', $settingsFile, gettype($settings)));
		}

		if (!isset($settings['project']['prefix'])) {
			throw new InvalidArgumentException(sprintf('No project prefix found in settings file "%s"', $settingsFile));
		}
		$projectLocation = (is_array($settings) && isset($settings['project']['location']) ? $settings['project']['location'] : '.');

		$output->writeln(sprintf("Project location set to \"%s\"", $projectLocation), Output::VERBOSITY_VERY_VERBOSE);

		if ($input->hasOption('module') && $input->getOption('module') != null) {
			$module = $input->getOption('module');
		} else {
			$question = new Question("Module name: ");
			$module = $helper->ask($input, $output, $question);
		}

		$module = ucfirst(TransformIdentifier::transform($module));

		$output->writeln(sprintf("Module name transformed to to \"%s\"", $module), Output::VERBOSITY_VERY_VERBOSE);


		if (!file_exists($projectLocation . '/app/modules/' . $module . '/config/module.xml')) {
			throw new InvalidArgumentException(sprintf('Module "%s" does not seem to exist in "%s"', $module, implode(DIRECTORY_SEPARATOR, [$projectLocation, 'app', 'modules', $module])));
		}

		if ($input->hasArgument('name') && $input->getArgument('name') != null) {
			$controllerName = $input->getArgument('name');
		} else {
			$question = new Question('Controller name: ');
			$controllerName = $helper->ask($input, $output, $question);
		}

		if (strlen($controllerName) == 0) {
			throw new InvalidArgumentException("Controller name cannot be empty.");
		}


		if (file_exists($projectLocation . '/app/modules/' . $module . '/controllers/' . str_replace('.', '/', $controllerName))) {
			throw new InvalidArgumentException(sprintf('Controller "%s" seems to already exist in "%s"', $controllerName, $projectLocation .
				implode(DIRECTORY_SEPARATOR, ['app', 'modules', $module, 'controllers', explode('.', $controllerName)])));
		}

		$controllerName = TransformIdentifier::transform($controllerName);
		$output->writeln(sprintf("Controller name transformed to  \"%s\"", $controllerName), Output::VERBOSITY_VERY_VERBOSE);

		if ($input->getOption('simple') == false && (!$input->hasOption('methods') || $input->getOption('methods') == null)) {

			$question = new Question("Create a simple controller? Simple controllers cannot handle request methods. (y/n) ", 'n');
			$simple = $helper->ask($input, $output, $question);

			if (!in_array($simple, ['y', 'Y', 'n', 'N'])) {
				throw new InvalidArgumentException(sprintf('Invalid answer "%s", expected one of y, Y, n or N.'), $simple);
			}

		} else {
			$simple = 'n';
		}

		$methodDeclarations = '';


		if (strtolower($simple) == 'n') {

			if ($input->hasOption('methods') && $input->getOption('methods') != null) {
				$requestMethods = $input->getOption('methods');
			} else {
				$question = new Question(sprintf("Space-separated list of request methods that should be handled by controller [%s] (empty for none): ", $controllerName));
				$requestMethods = $helper->ask($input, $output, $question);
			}

			$requestMethods = str_replace('"', '', $requestMethods);
			$output->writeln(sprintf("Request methods set to \"%s\"", $requestMethods), Output::VERBOSITY_VERY_VERBOSE);

			if (strlen($requestMethods) > 0) {
				$methodDeclarations = $this->generateExecuteMethods(explode(' ', $requestMethods));
			}

		} else {
			$methodDeclarations = file_get_contents($this->getSourceDir() . '/build/templates/code/controllers/Simple.tmpl');
		}

		// If the controller name contains a dot, it's a subcontroller, so we create the directory
		if (strpos($controllerName, '.') !== false) {
			$output->writeln(sprintf("Controller \"%s\" seems to be a subcontroller, creating directories", $controllerName), Output::VERBOSITY_VERY_VERBOSE);
			@mkdir($projectLocation . '/app/modules/' . $module . '/controllers/' . str_replace('.', '/', $controllerName), 0755, true);
			@mkdir($projectLocation . '/app/modules/' . $module . '/views/' . str_replace('.', '/', $controllerName), 0755, true);
			@mkdir($projectLocation . '/app/modules/' . $module . '/templates/' . str_replace('.', '/', $controllerName), 0755, true);
			@mkdir($projectLocation . '/app/modules/' . $module . '/cache/' . str_replace('.', '/', $controllerName), 0755, true);
			@mkdir($projectLocation . '/app/modules/' . $module . '/validate/' . str_replace('.', '/', $controllerName), 0755, true);
		}


		$fc = new FileCopyHelper();

		$output->writeln("Copying files for controller", Output::VERBOSITY_VERY_VERBOSE);

		// Copy controller
		$controllerFile = $projectLocation . '/app/modules/' . $module . '/controllers/' . str_replace('.', '/', $controllerName) . 'Controller.class.php';

		$output->writeln(sprintf("[%s -> %s]",
			$this->getSourceDir() . '/build/templates/app/modules/controllers/Controller.class.php.tmpl',
			$controllerFile
		), Output::VERBOSITY_DEBUG);

		$fc->copy($this->getSourceDir() . '/build/templates/app/modules/controllers/Controller.class.php.tmpl',
			$controllerFile,
			function ($data, $params) {
				return str_replace([
					'%%PROJECT_PREFIX%%',
					'%%MODULE_NAME%%',
					'%%CONTROLLER_CLASS%%',
					'%%METHOD_DECLARATIONS%%',
					'%%PROJECT_NAMESPACE%%',
					'%%FQNS%%'
				], [
					$params['projectPrefix'],
					$params['moduleName'],
					$params['controllerClass'],
					$params['methodDeclarations'],
					$params['NS'],
					$params['FQNS']
				], $data);
			}, [
				'projectPrefix' => $settings['project']['prefix'],
				'moduleName' => $module,
				'controllerClass' => $controllerName . 'Controller',
				'methodDeclarations' => $methodDeclarations,
				'FQNS' => $settings['project']['namespace'],
				'NS' => substr($settings['project']['namespace'], 1, strlen($settings['project']['namespace']))
			]
		);

		// Copy validator
		$validatorFile = $projectLocation . '/app/modules/' . $module . '/validate/' . str_replace('.', '/', $controllerName) . '.xml';

		$output->writeln(sprintf("[%s -> %s]",
			$this->getSourceDir() . '/build/templates/app/modules/validate/controller.xml.tmpl',
			$validatorFile
		), Output::VERBOSITY_DEBUG);


		$fc->copy($this->getSourceDir() . '/build/templates/app/modules/validate/controller.xml.tmpl',
			$validatorFile,
			function ($data, $params) {
				return str_replace([
					'%%MODULE_NAME%%',
				], [
					$params['moduleName'],
				], $data);
			}, [
				'moduleName' => $module,
			]
		);

		// Copy cache file
		$cacheFile = $projectLocation . '/app/modules/' . $module . '/cache/' . str_replace('.', '/', $controllerName) . '.xml';
		$output->writeln(sprintf("[%s -> %s]",
			$this->getSourceDir() . '/build/templates/app/modules/cache/controller.xml.tmpl',
			$cacheFile
		), Output::VERBOSITY_DEBUG);

		$fc->copy($this->getSourceDir() . '/build/templates/app/modules/cache/controller.xml.tmpl',
			$cacheFile,
			function ($data, $params) {
				return str_replace([
					'%%MODULE_NAME%%',
				], [
					$params['moduleName'],
				], $data);
			}, [
				'moduleName' => $module,
			]
		);

		if ($input->hasOption('views') && $input->getOption('views') != null) {
			$views = str_replace('"', '', $input->getOption('views'));
		} else {
			$question = new Question(sprintf("Space-separated list of views for controller [%s] (empty for none): ", $controllerName));
			$views = $helper->ask($input, $output, $question);
		}

		$output->writeln(sprintf("Setting views to \"%s\"", $views), Output::VERBOSITY_VERY_VERBOSE);


		$views = explode(' ', $views);
		$viewdefs = [];
		if ($input->hasOption('output-types') && $input->getOption('output-types') != null) {
			// Remove quotes and turn input into an array
			$viewoutputtypes = explode(' ', str_replace('"', '', $input->getOption('output-types')));
			foreach ($viewoutputtypes as $viewoutputtype) {
				if (strpos($viewoutputtype, ':') !== false) {
					list($view, $output_type) = explode(':', $viewoutputtype);
					if (!in_array($view, $views)) {
						throw new InvalidArgumentException(sprintf('Cannot find view "%s" in the list of views [%s]', ucfirst($view), implode(', ', ucfirst($views))));
					}
					$viewdefs[$view][] = $output_type;
				}
			}
		} else {
			// Ask the input types for every view
			foreach ($views as $viewname) {
				$question = new Question(sprintf("Space-separated list of output-types handled by the view [%s] (empty for none): ", ucfirst($viewname)));
				$vopt = $helper->ask($input, $output, $question);
				$vopts = explode(' ', $vopt);
				foreach ($vopts as $vo) {
					$viewdefs[ucfirst($viewname)][] = $vo;
				}
			}
		}

		$output->writeln('Copying views', Output::VERBOSITY_VERY_VERBOSE);

		// Copy views
		foreach ($viewdefs as $viewname => $output_types) {

			$viewFile = $projectLocation . '/app/modules/' . $module . '/views/' . str_replace('.', '/', $controllerName) . ucfirst($viewname) . 'View.class.php';

			$output->writeln(sprintf("[%s -> %s]",
				$this->getSourceDir() . '/build/templates/app/modules/views/View.class.php.tmpl',
				$viewFile
			), Output::VERBOSITY_DEBUG);

			$srcview = $this->getSourceDir() . '/build/templates/app/modules/views/View.class.php.tmpl';

			// If this is a system controller, copy a specific default view
			if ($input->hasOption('system') && $input->getOption('system') != null) {
				switch ($input->getOption('system')) {
					case 'error_404':
						$srcview = $this->getSourceDir() . '/build/templates/defaults/app/modules/views/Error404SuccessView.class.php.tmpl';
						break;
					case 'module_disabled':
						$srcview = $this->getSourceDir() . '/build/templates/defaults/app/modules/views/ModuleDisabledSuccessView.class.php.tmpl';
						break;
					case 'secure':
						$srcview = $this->getSourceDir() . '/build/templates/defaults/app/modules/views/SecureSuccessView.class.php.tmpl';
						break;
					case 'unavailable':
						$srcview = $this->getSourceDir() . '/build/templates/defaults/app/modules/views/UnavailableSuccessView.class.php.tmpl';
						break;
				}
			}

			$fc->copy($srcview, $viewFile,
				function ($data, $params) {
					return str_replace([
						'%%PROJECT_PREFIX%%',
						'%%MODULE_NAME%%',
						'%%VIEW_CLASS%%',
						'%%METHOD_DECLARATIONS%%',
						'%%PROJECT_NAMESPACE%%',
						'%%FQNS%%'
					], [
						$params['projectPrefix'],
						$params['moduleName'],
						$params['viewClass'],
						$params['methodDeclarations'],
						$params['NS'],
						$params['FQNS']
					], $data);
				}, [
					'projectPrefix' => $settings['project']['prefix'],
					'moduleName' => $module,
					'viewClass' => $controllerName . ucfirst($viewname) . 'View',
					'methodDeclarations' => $this->generateHandleOutputTypeMethods($output_types, $controllerName),
					'FQNS' => $settings['project']['namespace'],
					'NS' => substr($settings['project']['namespace'], 1, strlen($settings['project']['namespace']))
				]
			);

			$templateFile = $projectLocation . '/app/modules/' . $module . '/templates/' . str_replace('.', '/', $controllerName) . ucfirst($viewname) . '.php';

			$output->writeln(sprintf('Creating empty template file "%s"', $templateFile), Output::VERBOSITY_DEBUG);
			@touch($templateFile, 0755);
		}

		/*
		 * For system controllers we need to modify the settings.xml -file too
		 */
		$system_controllers = ['default', 'error_404', 'login', 'module_disabled', 'secure', 'unavailable'];
		if ($input->hasOption('system') && $input->getOption('system') != null) {
			$system = $input->getOption('system');

			if (!in_array($system, $system_controllers)) {
				throw new InvalidArgumentException(sprintf('"%s" is not an allowed system controller (one of %s)', $system, implode(', ', $system_controllers)));
			}

			$settingsFile = $projectLocation . '/app/config/settings.xml';

			// Set the module

			$moduleXPath = "//*[local-name() = 'configuration' and (namespace-uri() = 'http://agavi.org/agavi/config/global/envelope/1.0' or namespace-uri() = 'http://agavi.org/agavi/config/global/envelope/1.1')]//*[local-name() = 'system_controller' and (namespace-uri() = 'http://agavi.org/agavi/config/parts/settings/1.0' or namespace-uri() = 'http://agavi.org/agavi/config/parts/settings/1.1') and @name='" . $system . "']/*[local-name() = 'module']";

			$this->writeSettings($settingsFile, $moduleXPath, $module, $output);

			// Set the controller
			$controllerXPath = "//*[local-name() = 'configuration' and (namespace-uri() = 'http://agavi.org/agavi/config/global/envelope/1.0' or namespace-uri() = 'http://agavi.org/agavi/config/global/envelope/1.1')]//*[local-name() = 'system_controller' and (namespace-uri() = 'http://agavi.org/agavi/config/parts/settings/1.0' or namespace-uri() = 'http://agavi.org/agavi/config/parts/settings/1.1') and @name='" . $system . "']/*[local-name() = 'controller']";
			$this->writeSettings($settingsFile, $controllerXPath, $controllerName, $output);

			// Copy the default templates
			switch ($system) {
				case "error_404":
					@copy($this->getSourceDir() . '/build/templates/defaults/app/modules/templates/Error404Success.php.tmpl', $projectLocation . '/app/modules/' . $module . '/templates/Error404Success.php');
					break;
				case 'module_disabled':
					@copy($this->getSourceDir() . '/build/templates/defaults/app/modules/templates/ModuleDisabledSuccess.php.tmpl', $projectLocation . '/app/modules/' . $module . '/templates/ModuleDisabledSuccess.php');
					break;
				case 'success':
					@copy($this->getSourceDir() . '/build/templates/defaults/app/modules/templates/SecureSuccess.php.tmpl', $projectLocation . '/app/modules/' . $module . '/templates/SecureSuccess.php');
					break;
				case 'unavailable':
					@copy($this->getSourceDir() . '/build/templates/defaults/app/modules/templates/UnavailableSuccess.php.tmpl', $projectLocation . '/app/modules/' . $module . '/templates/UnavailableSuccess.php');
					break;

			}

		}

	}

	private function generateExecuteMethods(array $requestMethods)
	{

		$tmpl = file_get_contents($this->getSourceDir() . '/build/templates/code/controllers/HandleRequestMethod.tmpl');

		$code = '';
		foreach ($requestMethods as $requestMethod) {
			$code .= str_replace('%%METHOD_NAME%%', ucfirst($requestMethod), $tmpl);
		}
		return $code;

	}

	/**
	 * Generate the execute<OutputType> -methods
	 *
	 * @param array $output_types an array of OutputTypes
	 * @param string $controllerName the controller name
	 * @return string the generated execute-methods
	 */
	private function generateHandleOutputTypeMethods(array $output_types, string $controllerName)
	{
		$tmpl = file_get_contents($this->getSourceDir() . '/build/templates/code/views/HandleOutputType.tmpl');

		$code = '';
		foreach ($output_types as $output_type) {
			$code .= str_replace(['%%OUTPUT_TYPE_NAME%%', '%%CONTROLLER_NAME%%'], [ucfirst($output_type), $controllerName], $tmpl);

		}
		return $code;
	}

	private function writeSettings($file, $xpath, $value, OutputInterface $output)
	{
		$document = new \DOMDocument();
		$document->preserveWhiteSpace = true;
		$document->load($file);

		$path = new \DOMXPath($document);
		$path->registerNamespace('envelope', 'http://agavi.org/agavi/config/global/envelope/1.0');
		$path->registerNamespace('envelope10', 'http://agavi.org/agavi/config/global/envelope/1.0');
		$path->registerNamespace('envelope11', 'http://agavi.org/agavi/config/global/envelope/1.1');
		$path->registerNamespace('document', 'http://agavi.org/agavi/config/parts/settings/1.1');

		$entries = $path->query($xpath);
		foreach ($entries as $entry) {
			$entry->nodeValue = (string)$value;
		}

		$document->save($file);

		$output->writeln(sprintf('Setting value for "%s" in "%s" to "%s"', $xpath, $file, $value), Output::VERBOSITY_DEBUG);
	}


}