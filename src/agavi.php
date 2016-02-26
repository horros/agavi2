<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2005-2011 the Agavi Project.                                |
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
 * Pre-initialization script.
 *
 * @package    agavi
 *
 * @author     Sean Kerr <skerr@mojavi.org>
 * @author     Mike Vincent <mike@agavi.org>
 * @author     David Zülke <dz@bitxtender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.9.0
 *
 * @version    $Id$
 */

// load the AgaviConfig class
require(__DIR__ . '/config/Config.class.php');

// check minimum PHP version
\Agavi\Config\Config::set('core.minimum_php_version', '5.3.2');
if(version_compare(PHP_VERSION, \Agavi\Config\Config::get('core.minimum_php_version'), '<') ) {
	trigger_error('Agavi requires PHP version ' . \Agavi\Config\Config::get('core.minimum_php_version') . ' or greater', E_USER_ERROR);
}

// define a few filesystem paths
\Agavi\Config\Config::set('core.agavi_dir', $agavi_config_directive_core_agavi_dir = __DIR__, true, true);

// default exception template
\Agavi\Config\Config::set('exception.default_template', $agavi_config_directive_core_agavi_dir . '/exception/templates/shiny.php');

// required files
require($agavi_config_directive_core_agavi_dir . '/version.php');
require($agavi_config_directive_core_agavi_dir . '/core/Agavi.class.php');
require($agavi_config_directive_core_agavi_dir . '/util/Autoloader.class.php');
// required files for classes Agavi and ConfigCache to run
// consider this the bare minimum we need for bootstrapping
require($agavi_config_directive_core_agavi_dir . '/util/Inflector.class.php');
require($agavi_config_directive_core_agavi_dir . '/util/ArrayPathDefinition.class.php');
require($agavi_config_directive_core_agavi_dir . '/util/VirtualArrayPath.class.php');
require($agavi_config_directive_core_agavi_dir . '/util/ParameterHolder.class.php');
require($agavi_config_directive_core_agavi_dir . '/config/ConfigCache.class.php');
require($agavi_config_directive_core_agavi_dir . '/exception/AgaviException.class.php');
require($agavi_config_directive_core_agavi_dir . '/exception/AutoloadException.class.php');
require($agavi_config_directive_core_agavi_dir . '/exception/CacheException.class.php');
require($agavi_config_directive_core_agavi_dir . '/exception/ConfigurationException.class.php');
require($agavi_config_directive_core_agavi_dir . '/exception/UnreadableException.class.php');
require($agavi_config_directive_core_agavi_dir . '/exception/ParseException.class.php');
require($agavi_config_directive_core_agavi_dir . '/util/Toolkit.class.php');

// clean up (we don't want collisions with whatever file included us, in case you were wondering about the ugly name of that var)
unset($agavi_config_directive_core_agavi_dir);

?>