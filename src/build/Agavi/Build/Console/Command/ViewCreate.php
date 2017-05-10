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
 * Create view and template
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

class ViewCreate extends AgaviCommand
{
	protected function configure()
	{
		$this->setName('agavi:view')
			->setDescription('Create view')
			->addArgument('view', InputArgument::OPTIONAL, 'The name of the view. If it\'s not provided, it will be asked for.')
			->addOption('module', 'm', InputOption::VALUE_REQUIRED, 'The module that the view belongs to. If it\'s not provided, it will be asked for.')
			->addOption('controller', 'c', InputOption::VALUE_REQUIRED, 'The controller that the view belongs to. If it\'s not provided, it will be asked for.')
			->addOption('settings', 's', InputOption::VALUE_REQUIRED, '.settings.yml to read configuration from')
			->addOption('output-types', 'ot', InputOption::VALUE_REQUIRED, 'Quoted space separated list of output types the controller should have. If it\'s not provided, it will be asked for.');
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

		if ($input->hasOption('controller') && $input->getOption('controller') != null) {
			$controllerName = $input->getOption('controller');
		} else {
			$question = new Question('Controller name: ');
			$controllerName = $helper->ask($input, $output, $question);
		}

		if (strlen($controllerName) == 0) {
			throw new InvalidArgumentException("Controller name cannot be empty.");
		}

		if (!file_exists($projectLocation . '/app/modules/' . $module . '/controllers/' . str_replace('.', '/', $controllerName) . 'Controller.class.php')) {
			throw new InvalidArgumentException(sprintf('Controller "%s" does not seem to exist in "%s"', $controllerName, $projectLocation .
				implode(DIRECTORY_SEPARATOR, ['app', 'modules', $module, 'controllers', explode('.', $controllerName)]) . 'Controller.class.php'));
		}

		$controllerName = TransformIdentifier::transform($controllerName);
		$output->writeln(sprintf("Controller name transformed to  \"%s\"", $controllerName), Output::VERBOSITY_VERY_VERBOSE);

		if ($input->hasArgument('view') && $input->getArgument('view') != null) {
			$viewName = $input->getArgument('view');
		} else {
			$question = new Question('View name: ');
			$viewName = $helper->ask($input, $output, $question);
		}

		if (strlen($viewName) == 0) {
			throw new InvalidArgumentException("Controller name cannot be empty.");
		}

		if (file_exists($projectLocation . '/app/modules/' . $module . '/views/' . str_replace('.', '/', $controllerName) . $viewName . 'View.class.php')) {
			throw new InvalidArgumentException(sprintf('View "%s" seems to already exist in "%s"', $controllerName . $viewName, $projectLocation .
				implode(DIRECTORY_SEPARATOR, ['app', 'modules', $module, 'views', explode('.', $controllerName)]) . $viewName . 'View.class.php'));
		}

		$viewName = TransformIdentifier::transform($viewName);
		$output->writeln(sprintf("View name transformed to  \"%s\"", $viewName), Output::VERBOSITY_VERY_VERBOSE);

		$fc = new FileCopyHelper();

		if ($input->hasOption('output-types') && $input->getOption('output-types') != null) {
			// Remove quotes and turn input into an array
			$output_types = explode(' ', str_replace('"', '', $input->getOption('output-types')));
		} else {
			// Ask for the input types
			$question = new Question(sprintf("Space-separated list of output-types handled by the view [%s] (empty for none): ", $controllerName . ucfirst($viewName)));
			$output_types = $helper->ask($input, $output, $question);
			$output_types = explode(' ', $output_types);
		}

		$output->writeln('Copying view', Output::VERBOSITY_VERY_VERBOSE);

		$viewFile = $projectLocation . '/app/modules/' . $module . '/views/' . str_replace('.', '/', $controllerName) . ucfirst($viewName) . 'View.class.php';

		$output->writeln(sprintf("[%s -> %s]",
			$this->getSourceDir() . '/build/templates/app/modules/views/View.class.php.tmpl',
			$viewFile
		), Output::VERBOSITY_DEBUG);

		$srcview = $this->getSourceDir() . '/build/templates/app/modules/views/View.class.php.tmpl';

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
				'viewClass' => $module . '_' . $controllerName . ucfirst($viewName) . 'View',
				'methodDeclarations' => $this->generateHandleOutputTypeMethods($output_types, $controllerName),
				'FQNS' => $settings['project']['namespace'],
				'NS' => substr($settings['project']['namespace'], 1, strlen($settings['project']['namespace']))
			]
		);

		$templateFile = $projectLocation . '/app/modules/' . $module . '/templates/' . str_replace('.', '/', $controllerName) . ucfirst($viewName) . '.php';

		$output->writeln(sprintf('Creating empty template file "%s"', $templateFile), Output::VERBOSITY_DEBUG);
		@touch($templateFile, 0755);


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


}