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
 * The project creation command.
 *
 * @author     Markus Lervik <markuslervik1234@gmail.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      2.0.0
 **/
namespace Agavi\Build\Console\Command;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

class ProjectCreate extends AgaviCommand
{

    protected function configure() {
        $this->setName('agavi:project-create')
            ->setDescription('Create bare bones project')
			->addOption('dir', null, InputOption::VALUE_REQUIRED, 'The location of the new project');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
	{

		/**
		 * Hard coded template-location(s)
		 *
		 * TODO: Change these so they are configurable
		 */
		$templates = $this->getSourceDir() . '/build/templates';


		$helper = $this->getHelper('question');

		/**
		 * If the location was passed on the command line we don't need to ask for it again.
		 * This happens when the Project Wizard command calls this command
		 */
		if ($input->hasOption('dir') && $input->getOption('dir') != null) {
			$projectLocation = $input->getOption('dir');
		} else {
			$question = new Question('Please enter the location of the new project (defaults to Agavi2Project): ', 'Agavi2Project');
			$projectLocation = $helper->ask($input, $output, $question);

			if (is_dir($projectLocation)) {
				throw new \InvalidArgumentException(sprintf("The location \"%s\" already exists.", $projectLocation));
			}
			$output->writeln("Setting \$projectLocation to $projectLocation", Output::VERBOSITY_VERY_VERBOSE);

			if (!@mkdir($projectLocation, 0777, true)) {
				throw new \Exception(sprintf("Could not create directory \"%s\". Please check the file system permissions.", $projectLocation));
			}
		}

		$settings['project']['location'] = realpath($projectLocation);

		$question = new Question('Please enter the name of the new project (defaults to NewAgavi2Project): ', 'NewAgavi2Project');
		$projectName = $helper->ask($input, $output, $question);

		$output->writeln("Setting \$projectName to $projectName", Output::VERBOSITY_VERY_VERBOSE);
		$settings['project']['name'] = $projectName;

		$tmp = explode(' ', $projectName);
		if (is_array($tmp)) {
			array_walk(
				$tmp,
				function (&$v) {
					$v = ucfirst($v);
				}
			);
			$defaultPrefix = implode('', $tmp);
		} else {
			$defaultPrefix = $projectName;
		}


		$question = new Question(sprintf('Project prefix (used for example in the base controllers, defaults to "%s"): ', $defaultPrefix), $defaultPrefix);
		$projectPrefix = $helper->ask($input, $output, $question);

		$output->writeln("Setting \$projectPrefix to $projectPrefix", Output::VERBOSITY_VERY_VERBOSE);
		$settings['project']['prefix'] = $projectPrefix;

		$output->writeln("Agavi supports different module layouts.\n");
		$output->writeln("agavi2: (TBD)");
		$output->writeln("legacy: (Agavi 1.0 - default)");
		$output->writeln("custom: For custom layouts you can use %module_name% for the current");
		$output->writeln("module name, %controller_name% the current controller name, and %view_name% for");
		$output->writeln("the current view name\n");
		$question = new ChoiceQuestion("Please select the module layout (defaults to legacy): ", array('agavi2', 'legacy', 'custom'), 1);
		$projectLayout = $helper->ask($input, $output, $question);

		$output->writeln("Setting \$projectLayout to $projectLayout", Output::VERBOSITY_VERY_VERBOSE);
		$settings['project']['layout'] = $projectLayout;

		$fc = new FileCopyHelper();
		if ($projectLayout == 'custom') {
			// TODO: Implement me
		} elseif ($projectLayout == 'legacy') {

			@mkdir($projectLocation . '/app', 0755);
			@mkdir($projectLocation . '/app/config', 0755);
			@mkdir($projectLocation . '/app/modules', 0755);
			@mkdir($projectLocation . '/app/models', 0755);
			@mkdir($projectLocation . '/app/cache', 0777);
			@mkdir($projectLocation . '/app/lib', 0755);
			@mkdir($projectLocation . '/app/templates', 0755);
			@mkdir($projectLocation . '/app/logs', 0777);

			$defaultParams = [
				'projectLocation' => $projectLocation,
				'projectName' => $projectName,
				'projectPrefix' => $projectPrefix
			];

			// Copy config.php
			$fc->copy($templates . '/app/config.php.tmpl', $projectLocation . '/app/config/config.php',
				function ($data, $params) {
					return $this->projectTokenReplacer($data, $params);
				}, $defaultParams);

			// Copy lib
			@mkdir($projectLocation . '/app/lib/controller', 0755, true);
			@mkdir($projectLocation . '/app/lib/model', 0755, true);
			@mkdir($projectLocation . '/app/lib/view', 0755, true);

			// Base controller
			$fc->copy($templates . '/app/lib/controller/BaseController.class.php.tmpl', $projectLocation . '/app/lib/controller/' . $projectPrefix . 'BaseController.class.php',
				function ($data, $params) {
					return $this->projectTokenReplacer($data, $params);
				}, $defaultParams);

			// Base model
			$fc->copy($templates . '/app/lib/model/BaseModel.class.php.tmpl', $projectLocation . '/app/lib/model/' . $projectPrefix . 'BaseModel.class.php',
				function ($data, $params) {
					return $this->projectTokenReplacer($data, $params);
				}, $defaultParams);

			// Base view
			$fc->copy($templates . '/app/lib/view/BaseView.class.php.tmpl', $projectLocation . '/app/lib/view/' . $projectPrefix . 'BaseView.class.php',
				function ($data, $params) {
					return $this->projectTokenReplacer($data, $params);
				}, $defaultParams);

			// Config
			@mkdir($projectLocation . '/app/config', 0755, true);
			foreach (glob($this->getSourceDir() . '/build/templates/app/config/*.xml.tmpl') as $file) {
				$fc->copy($file, $projectLocation . '/app/config/' . basename($file, '.tmpl'),
					function ($data, $params) {
						return $this->projectTokenReplacer($data, $params);
					}, $defaultParams);
			}

			// Base controller
			$fc->copy($templates . '/app/config.php.tmpl', $projectLocation . '/app/config.php',
				function ($data, $params) {
					return $this->projectTokenReplacer($data, $params);
				}, $defaultParams);




			// Copy the Master and exception templates
			@mkdir($projectLocation . '/app/templates', 0755, true);
			@copy($this->getSourceDir() . '/build/templates/defaults/app/templates/Master.php.tmpl',
				$projectLocation . '/app/templates/Master.php');
			@mkdir($projectLocation . '/app/templates/exceptions', 0755, true);
			foreach (glob($this->getSourceDir() . '/build/templates/defaults/app/templates/exceptions/*.tmpl') as $file) {
				@copy($file, $projectLocation . '/app/templates/exceptions/' . basename($file, '.tmpl'));
			}



			$data = Yaml::dump($settings);
			file_put_contents(realpath($projectLocation) . DIRECTORY_SEPARATOR . '.settings.yml', $data);

		}
	}

    public function projectTokenReplacer($data, $params) {
		return str_replace([
			'%%AGAVI_SOURCE_LOCATION%%',
			'%%PROJECT_LOCATION%%',
			'%%PROJECT_NAME%%',
			'%%PROJECT_PREFIX%%',
			'%%PUBLIC_ENVIRONMENT%%',
			'%%TEMPLATE_EXTENSION%%',
			'%controllers.default_module%',
			'%controllers.default_controller%'
		], [
			$this->getSourceDir(),
			$params['projectLocation'],
			$params['projectName'],
			$params['projectPrefix'],
			'development',
			'php',
			'Default',
			'Index'
		], $data);

	}
}