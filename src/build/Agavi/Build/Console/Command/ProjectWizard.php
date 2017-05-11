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

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

class ProjectWizard extends AgaviCommand
{

    protected function configure()
    {
        $this->setName('agavi:project-wizard')
            ->setDescription('New Project wizard');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

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

        $settingsFile = $projectLocation . '/.settings.yml';

        if (!file_exists($settingsFile)) {
            throw new InvalidArgumentException(sprintf('Cannot find settings file "%s"', $settingsFile));
        }

        $settings = Yaml::parse(file_get_contents($settingsFile));

        if (!is_array($settings)) {
            throw new InvalidArgumentException(sprintf('Error parsing settings file "%s". Return value unexpected. Expected array, got %s', $settings, gettype($settings)));
        }

        if (!isset($settings['project']['prefix'])) {
            throw new InvalidArgumentException(sprintf('No project prefix found in settings file "%s"', $settings));
        }

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


        // Copy the welcome templates

        $fc = new FileCopyHelper();
        $fc->copy($this->getSourceDir() . '/build/templates/defaults/app/modules/views/WelcomeSuccessView.class.php.tmpl',
            $projectLocation . '/app/modules/Welcome/views/IndexSuccessView.class.php',
            function ($data, $params) {
                return str_replace([
                    '%%PROJECT_PREFIX%%',
                    '%%MODULE_NAME%%',
                    '%%VIEW_CLASS%%',
                    '%%PROJECT_NAMESPACE%%',
                    '%%FQNS%%'
                ], [
                    $params['projectPrefix'],
                    $params['moduleName'],
                    $params['viewClass'],
                    $params['NS'],
                    $params['FQNS'],
                ], $data);
            }, [
                'projectPrefix' => $settings['project']['prefix'],
                'moduleName' => 'Welcome',
                'viewClass' => 'IndexSuccessView',
                'FQNS' => $settings['project']['namespace'],
                'NS' => substr($settings['project']['namespace'], 1, strlen($settings['project']['namespace']))
            ]
        );


        copy($this->getSourceDir() . '/build/templates/defaults/app/modules/templates/WelcomeSuccess.php.tmpl',
            $projectLocation . '/app/modules/Welcome/templates/IndexSuccess.php');

        mkdir($settings['project']['pub']. '/welcome', 0755, true);
        copy($this->getSourceDir() . '/build/templates/defaults/pub/welcome/bg.png', $settings['project']['pub']. '/welcome/bg.png');
        copy($this->getSourceDir() . '/build/templates/defaults/pub/welcome/plant.png', $settings['project']['pub']. '/welcome/plant.png');

        // Add the Default-module
        $dcCommand = $this->getApplication()->find('agavi:module');
        $dcCommandInput = new ArrayInput([
            'command' => 'agavi:module',
            '--settings' => $projectLocation . '/.settings.yml',
            'module' => '_Default'
        ]);
        $dcCommand->run($dcCommandInput, $output);

        $settings['FQNS'] = $settings['project']['namespace'];
        $settings['NS'] = substr($settings['project']['namespace'], 1, strlen($settings['project']['namespace']));

        // Copy the default settings
        foreach (glob($this->getSourceDir() . '/build/templates/defaults/app/config/*.xml.tmpl') as $file) {
            $fc->copy($file, $projectLocation . '/app/config/' . basename($file, '.tmpl'), function ($data, $params) {
                return str_replace([
                    '%%AGAVI_SOURCE_LOCATION%%',
                    '%%PROJECT_LOCATION%%',
                    '%%PROJECT_NAME%%',
                    '%%PROJECT_PREFIX%%',
                    '%%PROJECT_NAMESPACE%%',
                    '%%FQNS%%',
                    '%%PUBLIC_ENVIRONMENT%%',
                    '%%TEMPLATE_EXTENSION%%',
                    '%controllers.default_module%',
                    '%controllers.default_controller%'
                ], [
                    $this->getSourceDir(),
                    $params['project']['location'],
                    $params['project']['name'],
                    $params['project']['prefix'],
                    $params['NS'],
                    $params['FQNS'],
                    'development',
                    'php',
                    '_Default',
                    'Index'
                ], $data);
            }, $settings);
        }


        // Create the system controllers

        // The default controller
        $scCommand = $this->getApplication()->find('agavi:controller');
        $scCommandInput = new ArrayInput([
            'command' => 'agavi:controller',
            '--settings' => $projectLocation . '/.settings.yml',
            '--module' => '_Default',
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
            '--module' => '_Default',
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
            '--module' => '_Default',
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
            '--module' => '_Default',
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
            '--module' => '_Default',
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
            '--module' => '_Default',
            '--methods' => 'read',
            '--views' => 'success',
            '--output-types' =>  'success:html',
            '--system' => 'login',
            'name' => 'Login'
        ]);
        $scCommand->run($scCommandInput, $output);
    }
}
