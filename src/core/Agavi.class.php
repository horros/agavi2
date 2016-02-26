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
namespace Agavi\Core;

use Agavi\Config\Config;
use Agavi\Config\ConfigCache;
use Agavi\Exception\AgaviException;
use Agavi\Util\Toolkit;

/**
 * Main framework class used for autoloading and initial bootstrapping of Agavi.
 *
 * @package    agavi
 * @subpackage core
 *
 * @author     David Zülke <dz@bitxtender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.11.0
 *
 * @version    $Id$
 */
final class Agavi
{
	/**
	 * Startup the Agavi core
	 *
	 * @param      string $environment the environment to use for this session.
	 *
	 * @author     David Zülke <dz@bitxtender.com>
	 * @since      0.11.0
	 */
	public static function bootstrap($environment = null)
	{
		// set up our __autoload
		spl_autoload_register(array('Agavi\\Util\\Autoloader', 'loadClass'));

		try {
			if($environment === null) {
				// no env given? let's read one from core.environment
				$environment = Config::get('core.environment');
			} elseif(Config::has('core.environment') && Config::isReadonly('core.environment')) {
				// env given, but core.environment is read-only? then we must use that instead and ignore the given setting
				$environment = Config::get('core.environment');
			}
			
			if($environment === null) {
				// still no env? oh man...
				throw new AgaviException('You must supply an environment name to Agavi::bootstrap() or set the name of the default environment to be used in the configuration directive "core.environment".');
			}
			
			// finally set the env to what we're really using now.
			Config::set('core.environment', $environment, true, true);

			Config::set('core.debug', false, false);

			if(!Config::has('core.app_dir')) {
				throw new AgaviException('Configuration directive "core.app_dir" not defined, terminating...');
			}

			// define a few filesystem paths
			Config::set('core.cache_dir', Config::get('core.app_dir') . '/cache', false, true);

			Config::set('core.config_dir', Config::get('core.app_dir') . '/config', false, true);

			Config::set('core.system_config_dir', Config::get('core.agavi_dir') . '/config/defaults', false, true);

			Config::set('core.lib_dir', Config::get('core.app_dir') . '/lib', false, true);

			Config::set('core.model_dir', Config::get('core.app_dir') . '/models', false, true);

			Config::set('core.module_dir', Config::get('core.app_dir') . '/modules', false, true);

			Config::set('core.template_dir', Config::get('core.app_dir') . '/templates', false, true);

			Config::set('core.cldr_dir', Config::get('core.agavi_dir') . '/translation/data', false, true);

			// autoloads first (will trigger the compilation of config_handlers.xml)
			$autoload = Config::get('core.config_dir') . '/autoload.xml';
			if(!is_readable($autoload)) {
				$autoload = Config::get('core.system_config_dir') . '/autoload.xml';
			}
			ConfigCache::load($autoload);
			
			// load base settings
			ConfigCache::load(Config::get('core.config_dir') . '/settings.xml');

			// clear our cache if the conditions are right
			if(Config::get('core.debug')) {
				Toolkit::clearCache();

				// load base settings
				ConfigCache::load(Config::get('core.config_dir') . '/settings.xml');
			}

			$compile = Config::get('core.config_dir') . '/compile.xml';
			if(!is_readable($compile)) {
				$compile = Config::get('core.system_config_dir') . '/compile.xml';
			}
			// required classes for the framework
			ConfigCache::load($compile);

		} catch(\Exception $e) {
			AgaviException::render($e);
		}
	}
}

?>