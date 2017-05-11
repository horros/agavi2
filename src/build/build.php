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
 * Command-line script for Agavi2

 * @author     Markus Lervik <markuslervik1234@gmail.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      2.0.0
 *
 * @version    $Id$
 */

$composerVendorDir = __DIR__ . '/../../vendor';
require_once($composerVendorDir . '/autoload.php');


// Autoloader for our console application. We don't want
// to pollute Composer's autoloader
spl_autoload_register(function ($class) {
    if (file_exists(__DIR__  . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php')) {
        include (__DIR__ . DIRECTORY_SEPARATOR .  str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php');
    }
});

if (!(defined("AGAVI2_SRC_DIR"))) {
    define('AGAVI2_SRC_DIR', __DIR__ . '/..');
}

$app = new Agavi\Build\Console\AgaviApplication("Agavi", Agavi\Build\Console\AgaviApplication::$VERSION, AGAVI2_SRC_DIR);
$app->run();
