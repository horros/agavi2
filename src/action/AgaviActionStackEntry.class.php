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
 * ActionStackEntry represents information relating to a single Action request
 * during a single HTTP request.
 *
 * @package    agavi
 * @subpackage action
 *
 * @author     Sean Kerr <skerr@mojavi.org>
 * @copyright  (c) Authors
 * @since      0.9.0
 *
 * @version    $Id$
 */
class AgaviActionStackEntry
{

	// +-----------------------------------------------------------------------+
	// | PRIVATE VARIABLES                                                     |
	// +-----------------------------------------------------------------------+
	
	private
		$actionInstance = null,
		$actionName     = null,
		$microtime      = null,
		$moduleName     = null,
		$presentation   = null;
	
	// +-----------------------------------------------------------------------+
	// | METHODS                                                               |
	// +-----------------------------------------------------------------------+
	
	/**
	 * Class constructor.
	 *
	 * @param      string A module name.
	 * @param      string An action name.
	 * @param      AgaviAction An action implementation instance.
	 *
	 * @author     Sean Kerr <skerr@mojavi.org>
	 * @since      0.9.0
	 */
	public function __construct ($moduleName, $actionName, $actionInstance)
	{
		
		$this->actionName     = $actionName;
		$this->actionInstance = $actionInstance;
		$this->microtime      = microtime();
		$this->moduleName     = $moduleName;
		
	}
	
	/**
	 * Retrieve this entry's action name.
	 *
	 * @return     string An action name.
	 *
	 * @author     Sean Kerr <skerr@mojavi.org>
	 * @since      0.9.0
	 */
	public function getActionName ()
	{
		
		return $this->actionName;
	
	}
	
	/**
	 * Retrieve this entry's action instance.
	 *
	 * @return     AgaviAction An action implementation instance.
	 *
	 * @author     Sean Kerr <skerr@mojavi.org>
	 * @since      0.9.0
	 */
	public function getActionInstance ()
	{
		
		return $this->actionInstance;
	
	}
	
	/**
	 * Retrieve this entry's microtime.
	 *
	 * @return     string A string representing the microtime this entry was
	 *                    created.
	 *
	 * @author     Sean Kerr <skerr@mojavi.org>
	 * @since      0.9.0
	 */
	public function getMicrotime ()
	{
		
		return $this->microtime;
	
	}
	
	/**
	 * Retrieve this entry's module name.
	 *
	 * @return     string A module name.
	 *
	 * @author     Sean Kerr <skerr@mojavi.org>
	 * @since      0.9.0
	 */
	public function getModuleName ()
	{
		
		return $this->moduleName;
	
	}
	
	/**
	 * Retrieve this entry's rendered view presentation.
	 *
	 * This will only exist if the view has processed and the render mode
	 * is set to AgaviView::RENDER_VAR.
	 *
	 * @return     string An action name.
	 *
	 * @author     Sean Kerr <skerr@mojavi.org>
	 * @since      0.9.0
	 */
	public function & getPresentation ()
	{
		
		return $this->presentation;
	
	}
	
	/**
	 * Set the rendered presentation for this action.
	 *
	 * @param      string A rendered presentation.
	 *
	 * @author     Sean Kerr <skerr@mojavi.org>
	 * @since      0.9.0
	 */
	public function setPresentation (&$presentation)
	{
		
		$this->presentation =& $presentation;
		
	}

}

?>