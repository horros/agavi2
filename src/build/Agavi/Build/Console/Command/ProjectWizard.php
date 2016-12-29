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
 * Project wizard command
 *
 * Creates the skeleton project, pub dir, a default module,
 * and a welcome-module
 *
 * @author     Markus Lervik <markuslervik1234@gmail.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      2.0.0
 **/
namespace Agavi\Build\Console\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ProjectWizard extends AgaviCommand
{

	protected function configure() {
		$this->setName('agavi:project-wizard')
			->setDescription('New Project wizard');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		// Project location
		$helper = $this->getHelper('question');

		$question = new Question('Please enter the location of the new project: ', 'Agavi2Project');

		$projectLocation = $helper->ask($input, $output, $question);

		if (is_dir($projectLocation)) {
			throw new \InvalidArgumentException("The location $projectLocation already exists.");
		}
		$output->writeln("Setting \$projectLocation to $projectLocation", Output::VERBOSITY_VERY_VERBOSE);


		if (!@mkdir($projectLocation, 0777, true)) {
			throw new \Exception("Could not create directory $projectLocation. Please check the file system permissions.");
		}

		// Now that we have a project location we can pass around,
		// we can fire the project creation command
		/** @var ProjectCreate $projectCommand */
		$projectCommand = $this->getApplication()->find('agavi:project-create');

		$projectCommandInput = new ArrayInput([
			'command' => 'agavi:project-create',
			'--dir' => $projectLocation
		]);
		$returnCode = $projectCommand->run($projectCommandInput, $output);

		// Add the pub dir
		$pubCommand = $this->getApplication()->find('agavi:pub');
		$pubCommandInput = new ArrayInput([
			'command' => 'agavi:pub',
			'--settings' => $projectLocation . '/.settings.yml'
		]);
		$returnCode = $pubCommand->run($pubCommandInput, $output);

		// Add the Welcome-module
		$mcCommand = $this->getApplication()->find('agavi:module');
		$mcCommandInput = new ArrayInput([
			'command' => 'agavi:module',
			'--settings' => $projectLocation . '/.settings.yml',
			'module' => 'Welcome'
		]);
		$returnCode = $mcCommand->run($mcCommandInput, $output);

		// Add the Index-action to the Welcome-module
		$ccCommand = $this->getApplication()->find('agavi:controller');
		$ccCommandInput = new ArrayInput([
			'command' => 'agavi:controller',
			'--settings' => $projectLocation . '/.settings.yml',
			'--module' => 'Welcome',
			'--methods' => 'read',
			'--views' => 'success',
			'--output-types' =>  'success:html',
			'name' => 'Index'
		]);
		$returnCode = $ccCommand->run($ccCommandInput, $output);

		// Add the Default-module
		$dcCommand = $this->getApplication()->find('agavi:module');
		$dcCommandInput = new ArrayInput([
			'command' => 'agavi:module',
			'--settings' => $projectLocation . '/.settings.yml',
			'module' => 'Default'
		]);
		$dcCommand->run($dcCommandInput, $output);

		// Create the system controllers

		// The default controller
		$scCommand = $this->getApplication()->find('agavi:controller');
		$scCommandInput = new ArrayInput([
			'command' => 'agavi:controller',
			'--settings' => $projectLocation . '/.settings.yml',
			'--module' => 'Default',
			'--methods' => 'read',
			'--views' => 'success',
			'--output-types' =>  'success:html',
			'--system' => 'default',
			'name' => 'Default'
		]);
		$scCommand->run($scCommandInput, $output);

		// The error_404 controller
		$scCommand = $this->getApplication()->find('agavi:controller');
		$scCommandInput = new ArrayInput([
			'command' => 'agavi:controller',
			'--settings' => $projectLocation . '/.settings.yml',
			'--module' => 'Default',
			'--methods' => 'read',
			'--views' => 'success',
			'--output-types' =>  'success:html',
			'--system' => 'error_404',
			'name' => 'Error404'
		]);
		$scCommand->run($scCommandInput, $output);

		// The module_disabled controller
		$scCommand = $this->getApplication()->find('agavi:controller');
		$scCommandInput = new ArrayInput([
			'command' => 'agavi:controller',
			'--settings' => $projectLocation . '/.settings.yml',
			'--module' => 'Default',
			'--methods' => 'read',
			'--views' => 'success',
			'--output-types' =>  'success:html',
			'--system' => 'module_disabled',
			'name' => 'ModuleDisabled'
		]);
		$scCommand->run($scCommandInput, $output);

		// The secure controller
		$scCommand = $this->getApplication()->find('agavi:controller');
		$scCommandInput = new ArrayInput([
			'command' => 'agavi:controller',
			'--settings' => $projectLocation . '/.settings.yml',
			'--module' => 'Default',
			'--methods' => 'read',
			'--views' => 'success',
			'--output-types' =>  'success:html',
			'--system' => 'secure',
			'name' => 'Secure'
		]);
		$scCommand->run($scCommandInput, $output);

		// The unavailable controller
		$scCommand = $this->getApplication()->find('agavi:controller');
		$scCommandInput = new ArrayInput([
			'command' => 'agavi:controller',
			'--settings' => $projectLocation . '/.settings.yml',
			'--module' => 'Default',
			'--methods' => 'read',
			'--views' => 'success',
			'--output-types' =>  'success:html',
			'--system' => 'unavailable',
			'name' => 'Unavailable'
		]);
		$scCommand->run($scCommandInput, $output);

		// The login controller
		$scCommand = $this->getApplication()->find('agavi:controller');
		$scCommandInput = new ArrayInput([
			'command' => 'agavi:controller',
			'--settings' => $projectLocation . '/.settings.yml',
			'--module' => 'Default',
			'--methods' => 'read',
			'--views' => 'success',
			'--output-types' =>  'success:html',
			'--system' => 'login',
			'name' => 'Login'
		]);
		$scCommand->run($scCommandInput, $output);


	}
}