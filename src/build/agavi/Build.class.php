<?php
namespace Agavi\Build;
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
 * Build system utility class.
 *
 * @package    agavi
 * @subpackage build
 *
 * @author     Noah Fontes <noah.fontes@bitextender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      1.0.0
 *
 * @version    $Id$
 */
final class Build
{
	/**
	 * @var        bool Whether or not the build system has been bootstrapped yet.
	 */
	protected static $bootstrapped = false;
	
	/**
	 * @var        array An associative array of classes and files that
	 *                   can be autoloaded.
	 */
	public static $autoloads = array(
		'Agavi\\Build\\Exception\\BuildException' => 'exception/BuildException.class.php',
		'Agavi\\Build\\Exception\\EventBuildException' => 'exception/EventBuildException.class.php',
		'Agavi\\Build\\Check\\Check' => 'check/Check.class.php',
		'Agavi\\Build\\Check\\FilesystemCheck' => 'check/FilesystemCheck.class.php',
		'Agavi\\Build\\Check\\ProjectFilesystemCheck' => 'check/ProjectFilesystemCheck.class.php',
		'Agavi\\Build\\Check\\ModuleFilesystemCheck' => 'check/ModuleFilesystemCheck.class.php',
		'Agavi\\Build\\Transform\\Transform' => 'transform/Transform.class.php',
		'Agavi\\Build\\Transform\\IdentifierTransform' => 'transform/IdentifierTransform.class.php',
		'Agavi\\Build\\Transform\\ArraytostringTransform' => 'transform/ArraytostringTransform.class.php',
		'Agavi\\Build\\Transform\\StringtoarrayTransform' => 'transform/StringtoarrayTransform.class.php',
		'Agavi\\Build\\Event\\ListenerInterface' => 'event/Listener.interface.php',
		'Agavi\\Build\\Event\\EventDispatcher' => 'event/EventDispatcher.class.php',
		'Agavi\\Build\\Event\\EventInterface' => 'event/Event.interface.php',
		'Agavi\\Build\\Event\\Event' => 'event/AgaviEventInterface.class.php',
		'Agavi\\Build\\Phing\\ProxyProject' => 'phing/ProxyProject.class.php',
		'Agavi\\Build\\Phing\\ProxyXmlContext' => 'phing/ProxyXmlContext.class.php',
		'Agavi\\Build\\Phing\\ProxyTarget' => 'phing/ProxyTarget.class.php',
		'Agavi\\Build\\Phing\\PhingEventDispatcherManager' => 'phing/PhingEventDispatcherManagers.class.php',
		'Agavi\\Build\\Phing\\PhingEventDispatcher' => 'phing/PhingEventDispatcher.class.php',
		'Agavi\\Build\\Phing\\PhingEvent' => 'phing/PhingEvents.class.php',
		'Agavi\\Build\\Phing\\PhingTargetEvent' => 'phing/PhingTargetEvents.class.php',
		'Agavi\\Build\\Phing\\PhingTaskEvent' => 'phing/PhingTaskEvent.class.php',
		'Agavi\\Build\\Phing\\PhingMessageEvent' => 'phing/PhingMessageEvents.class.php',
		'Agavi\\Build\\Phing\\PhingListenerInterface' => 'phing/PhingListener.interface.php',
		'Agavi\\Build\\Phing\\PhingTargetListenerInterface' => 'phing/PhingTargetListener.interface.php',
		'Agavi\\Build\\Phing\\PhingTaskListenerInterface' => 'phing/PhingTaskListener.interface.php',
		'Agavi\\Build\\Phing\\PhingMessageListenerInterface' => 'phing/PhingMessageListener.interface.php',
		'Agavi\\Build\\Phing\\PhingTargetAdapter' => 'phing/PhingTargetAdapters.class.php',
		'Agavi\\Build\\Phing\\PhingTaskAdapter' => 'phing/PhingTaskAdapters.class.php',
		'Agavi\\Build\\Phing\\PhingMessageAdapter' => 'phing/PhingMessageAdapter.class.php',
		'Agavi\\Build\\Phing\\BuildLogger' => 'phing/BuildLogger.class.php',
		'Agavi\\Build\\Phing\\ProxyBuildLogger' => 'phing/ProxyBuildLogger.class.php'
	);

	/**
	 * Autoloads classes.
	 *
	 * @param      string A class name.
	 *
	 * @author     David Zülke <dz@bitxtender.com>
	 * @author     Noah Fontes <noah.fontes@bitextender.com>
	 * @since      1.0.0
	 */
	public static function __autoload($class)
	{
		if(isset(self::$autoloads[$class])) {
			require(__DIR__ . '/' . self::$autoloads[$class]);
		}

		/* If the class isn't loaded by this method, the only other
		 * sane option is to simply let PHP handle it and hope another
		 * handler picks it up. */
	}

	/**
	 * Prepares the build environment classes for use.
	 *
	 * @author     David Zülke <dz@bitxtender.com>
	 * @author     Noah Fontes <noah.fontes@bitextender.com>
	 * @since      1.0.0
	 */
	public static function bootstrap()
	{
		if(self::$bootstrapped === false) {
			spl_autoload_register(array('Agavi\\Build\\Build', '__autoload'));
		}
		
		self::$bootstrapped = true;
	}
	
	/**
	 * Retrieves whether the build system has been bootstrapped.
	 *
	 * @return     boolean True if the build system has been bootstrapped, false
	 *                     otherwise.
	 *
	 * @author     David Zülke <dz@bitxtender.com>
	 * @author     Noah Fontes <noah.fontes@bitextender.com>
	 * @since      1.0.0
	 */
	public static function isBootstrapped()
	{
		return self::$bootstrapped;
	}
}

?>