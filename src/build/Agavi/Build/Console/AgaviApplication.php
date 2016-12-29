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
 * Agavi console build application
 *
 * @author     Markus Lervik <markuslervik1234@gmail.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      2.0.0
 **/
namespace Agavi\Build\Console;


use Symfony\Component\Console\Application;
use Symfony\Component\Finder\Finder;


class AgaviApplication extends Application
{

    public static $VERSION = '2.0.0-alpha1';

    public $SRC_DIR;

    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN', $SRC_DIR= "../..") {

        parent::__construct($name, $version);

        $this->SRC_DIR = $SRC_DIR;

        // Shamelessly stolen off of Propel
        // Walk through all files in the Command subdirectory and check if
        // they are Commands, if so add them.
        $finder = new Finder();
        $finder->files()->name('*.php')->in(__DIR__ . '/Command');
        foreach ($finder as $file) {
            $ns = 'Agavi\\Build\\Console\\Command';
            $r = new \ReflectionClass($ns . '\\' . $file->getBasename('.php'));
            if ($r->isSubclassOf($ns . '\\AgaviCommand') && !$r->isAbstract()) {
                $instance = $r->newInstance();
                if ($r->hasMethod('setSourceDir')) {
                    $instance->setSourceDir($SRC_DIR);
                }
                $this->add($instance);
            }
        }
    }

}