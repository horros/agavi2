<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2003-2006 the Agavi Project.                                |
// | Based on the Mojavi3 MVC Framework, Copyright (c) 2003-2005 Sean Kerr.    |
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
 * @author     David Zuelke <dz@bitxtender.com>
 * @copyright  (c) Authors
 * @since      0.9.0
 *
 * @version    $Id$
 */

/**
 * Minimum requirement check
 * 
 * Things arent going to work unless we're running with php5,
 * so dont assume we are. 
 *
 * @author     Mike Vincent <mike@agavi.org>
 * @since      0.9.0
 */

define('MINIMUM_VER_PHP', '5.0.0');

if ( !version_compare(PHP_VERSION, MINIMUM_VER_PHP, 'ge') ) {
	die ('You must be using PHP version 5 or greater.');
}



/**
 * Handles autoloading of classes that have been specified in autoload.ini.
 *
 * @param      string A class name.
 *
 * @return     void
 *
 * @author     Sean Kerr <skerr@mojavi.org>
 * @since      0.9.0
 */
function __autoload ($class)
{

	// this static variable is generated by the $config file below
	static $classes;

	if (!isset($classes)) {
		try	{
			// include the list of autoload classes
			$config = ConfigCache::checkConfig('config/autoload.ini');
		} catch (AgaviException $e) {
			$e->printStackTrace();
		} catch (Exception $e) {
			// unknown exception
			$e = new AgaviException($e->getMessage());
			$e->printStackTrace();
		}
		require_once($config);
	}

	if (isset($classes[$class])) {
		// class exists, let's include it
		require_once($classes[$class]);
	}
	/*	

		If the class doesn't exist in autoload.ini there's not a lot we can do. Because 
		PHP's class_exists resorts to __autoload we cannot throw exceptions
		for this might break some 3rd party lib autoloading mechanism.

	*/

}

try {

	// set default error reporting and debug modes if none specified
	if(!defined('AG_ERROR_REPORTING')) {
		define('AG_ERROR_REPORTING', E_ALL | E_STRICT);
	}
	error_reporting(AG_ERROR_REPORTING);

	if(!defined('AG_DEBUG')) {
		define('AG_DEBUG', false);
	}

	// bail out if AG_WEBAPP_DIR was not defined before including this file
	if(!defined('AG_WEBAPP_DIR')) {
		trigger_error('Constant AG_WEBAPP_DIR not defined, terminating...', E_USER_ERROR);
	}

	// ini settings
	ini_set('arg_separator.output',      '&amp;');
	ini_set('display_errors',            1);
	ini_set('magic_quotes_runtime',      0);
	ini_set('unserialize_callback_func', '__autoload');

	// define a few filesystem paths
	if(!defined('AG_APP_DIR')) {
		define('AG_APP_DIR', dirname(__FILE__));
	}
	if(!defined('AG_CACHE_DIR')) {
		define('AG_CACHE_DIR', AG_WEBAPP_DIR . '/cache');
	}
	if(!defined('AG_CONFIG_DIR')) {
		define('AG_CONFIG_DIR', AG_WEBAPP_DIR . '/config');
	}
	if(!defined('AG_LIB_DIR')) {
		define('AG_LIB_DIR', AG_WEBAPP_DIR . '/lib');
	}
	if(!defined('AG_MODULE_DIR')) {
		define('AG_MODULE_DIR', AG_WEBAPP_DIR . '/modules');
	}
	if(!defined('AG_TEMPLATE_DIR')) {
		define('AG_TEMPLATE_DIR', AG_WEBAPP_DIR . '/templates');
	}

	// required files
	require_once(AG_APP_DIR . '/version.php');

	// required classes for this file and ConfigCache to run
	require_once(AG_APP_DIR . '/util/ParameterHolder.class.php');
	require_once(AG_APP_DIR . '/config/ConfigCache.class.php');
	require_once(AG_APP_DIR . '/config/ConfigHandler.class.php');
	require_once(AG_APP_DIR . '/config/ParameterParser.class.php');
	require_once(AG_APP_DIR . '/config/IniConfigHandler.class.php');
	require_once(AG_APP_DIR . '/config/AutoloadConfigHandler.class.php');
	require_once(AG_APP_DIR . '/config/RootConfigHandler.class.php');
	require_once(AG_APP_DIR . '/exception/AgaviException.class.php');
	require_once(AG_APP_DIR . '/exception/AutoloadException.class.php');
	require_once(AG_APP_DIR . '/exception/CacheException.class.php');
	require_once(AG_APP_DIR . '/exception/ConfigurationException.class.php');
	require_once(AG_APP_DIR . '/exception/UnreadableException.class.php');
	require_once(AG_APP_DIR . '/exception/ParseException.class.php');
	require_once(AG_APP_DIR . '/util/Toolkit.class.php');

	// clear our cache if the conditions are right
	if (AG_DEBUG)	{
		Toolkit::clearCache();
	}

	// load base settings
	ConfigCache::import('config/settings.ini');

	// required classes for the framework
	ConfigCache::import('config/compile.conf');

} catch (AgaviException $e) {
	$e->printStackTrace();
} catch (Exception $e) {
	// unknown exception
	$e = new AgaviException($e->getMessage());
	$e->printStackTrace();
}

?>