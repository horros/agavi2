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
 * Command that will create pub-directories
 *
 * @author     Markus Lervik <markuslervik1234@gmail.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      2.0.0
 **/
namespace Agavi\Build\Console\Command;


use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

class PubCreate extends AgaviCommand
{

	/**
	 * The environment to bootstrap in index.php
	 *
	 * @var string The environment name
	 */
	protected $environment;

	protected function configure()
	{
		$this->setName('agavi:pub')
			->setDescription('Create pub-dir')
			->addOption('dir', null, InputOption::VALUE_REQUIRED, 'The pub directory location')
			->addOption('settings', null, InputOption::VALUE_REQUIRED, 'settings.yml file to read project settings from')
			->addOption('environment', null, InputOption::VALUE_REQUIRED, 'The environment to bootstrap');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{

		if ($input->hasOption('environment') && $input->getOption('environment') != null) {
			$this->environment = $input->getOption('environment');
		} else {
			$this->environment = "development";
		}

		$projectLocation = '.';
		$helper = $this->getHelper('question');

		if ($input->hasOption('settings') && ($settings = $input->getOption('settings')) != null) {
			if (!file_exists($settings))
				throw new InvalidArgumentException('PubCreate: Cannot find the settings file "' . $settings . '""');
			$data = Yaml::parse(file_get_contents($settings));
			$projectLocation = (is_array($data) && isset($data['project']['location']) ? $data['project']['location'] : '.');
		}
		if ($input->hasOption('dir') && $input->getOption('dir') != null) {
			$dir = $input->getOption('dir');
		} else {
			$question = new Question('Please enter the location of the pub directory (defaults to ' . $projectLocation . DIRECTORY_SEPARATOR . 'pub): ', $projectLocation . '/pub');
			$dir = $helper->ask($input, $output, $question);

			if (is_dir($dir)) {
				throw new \InvalidArgumentException("The location \"$dir\" already exists.");
			}
		}

		@mkdir($dir, 0755, true);

		$fc = new FileCopyHelper();

		/**
		 * Copy the files and replace the tokens in the template files
		 */
		$fc->copy($this->getSourceDir() . '/build/templates/pub/dot.htaccess.tmpl', $dir . '/.htaccess', function ($data) {
			$this->pubTokenReplacer($data);
		});

		$fc->copy($this->getSourceDir() . '/build/templates/pub/index.php.tmpl', $dir . '/index.php', function ($data) {
			$this->pubTokenReplacer($data);
		});

		return 0;

	}

	public function pubTokenReplacer($data)
	{
		return str_replace([
			'%%AGAVI_SOURCE_LOCATION%%',
			'%%PUBLIC_ENVIRONMENT%%'
		], [
			$this->getSourceDir(),
			$this->environment
		],
			$data);

	}
}