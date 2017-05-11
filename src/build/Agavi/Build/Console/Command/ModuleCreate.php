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
 * Create an empty module
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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

class ModuleCreate extends AgaviCommand
{

    protected function configure()
    {
        $this->setName('agavi:module')
            ->setDescription('Create module')
            ->addOption('settings', null, InputOption::VALUE_REQUIRED, '.settings.yml to read configuration from')
            ->addArgument('module', InputArgument::OPTIONAL, 'The name of the module. If it\'s not provided, it will be asked for.');
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
            'projectName' => $settings['project']['name'],
            'projectPrefix' => $settings['project']['prefix'],
            'moduleName' => $module,
            'FQNS' => $settings['project']['namespace'],
            'NS' => substr($settings['project']['namespace'], 1, strlen($settings['project']['namespace']))
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
            $fc->copy($file, $projectLocation . '/app/modules/' . $module . '/lib/controller/' . $settings['project']['prefix'] . $module . basename($file, '.tmpl'),
                function ($data, $params) {
                    return $this->moduleTokenReplacer($data, $params);
                }, $defaultParams);
        }
        @mkdir($projectLocation . '/app/modules/' . $module . '/lib/model', 0755, true);
        foreach (glob($this->getSourceDir() . '/build/templates/app/modules/lib/model/*.tmpl') as $file) {
            $fc->copy($file, $projectLocation . '/app/modules/' . $module . '/lib/model/' . $settings['project']['prefix'] . $module . basename($file, '.tmpl'),
                function ($data, $params) {
                    return $this->moduleTokenReplacer($data, $params);
                }, $defaultParams);
        }
        @mkdir($projectLocation . '/app/modules/' . $module . '/lib/view', 0755, true);
        foreach (glob($this->getSourceDir() . '/build/templates/app/modules/lib/view/*.tmpl') as $file) {
            $fc->copy($file, $projectLocation . '/app/modules/' . $module . '/lib/view/' . $settings['project']['prefix'] . $module . basename($file, '.tmpl'),
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
            '%%MODULE_TEMPLATES_DIRECTORY%%',
            '%%PROJECT_NAMESPACE%%',
            '%%FQNS%%'
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
            '%core.module_dir%/${module}/templates',
            $params['NS'],
            $params['FQNS']
        ], $data);
    }
}
