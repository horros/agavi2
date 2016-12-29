<?php
/**
 * Created by PhpStorm.
 * User: markus
 * Date: 14/12/2016
 * Time: 15:52
 */

namespace Agavi\Build\Console\Command;


use Agavi\Build\Console\Command\AgaviCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LocateProject extends AgaviCommand
{

    protected function configure() {
        $this->setName('agavi:project-locate')
            ->setDescription('Locate the project')
            ->addOption('print', 'p', InputOption::VALUE_NONE, 'Print the location of the project');
    }


    /**
     * Locate the project and set it as a setting and optionally print it to the screen
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return null
     */
    protected function execute(InputInterface $input, OutputInterface $output) {

    }
}