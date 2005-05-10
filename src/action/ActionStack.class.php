<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2003, 2004 Agavi Foundation.                                |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org.                              |
// +---------------------------------------------------------------------------+

/**
 * ActionStack keeps a list of all requested actions and provides accessor
 * methods for retrieving individual entries.
 *
 * @package    agavi
 * @subpackage action
 *
 * @author    Agavi Foundation (info@agavi.org)
 * @copyright (c) Agavi Foundation, {@link http://www.agavi.org}
 * @since     3.0.0
 * @version   $Id$
 */
class ActionStack extends AgaviObject
{

	// +-----------------------------------------------------------------------+
	// | PRIVATE VARIABLES                                                     |
	// +-----------------------------------------------------------------------+

	private
		$stack = array();

	// +-----------------------------------------------------------------------+
	// | METHODS                                                               |
	// +-----------------------------------------------------------------------+

	/**
	 * Add an entry.
	 *
	 * @param string A module name.
	 * @param string An action name.
	 * @param Action An action implementation instance.
	 *
	 * @return void
	 *
	 * @author Agavi Foundation (info@agavi.org)
	 * @since  3.0.0
	 */
	public function addEntry ($moduleName, $actionName, $actionInstance)
	{

		// create our action stack entry and add it to our stack
		$actionEntry = new ActionStackEntry($moduleName, $actionName,
						                    $actionInstance);

		$this->stack[] = $actionEntry;

	}

	// -------------------------------------------------------------------------

	/**
	 * Retrieve the entry at a specific index.
	 *
	 * @param int An entry index.
	 *
	 * @return ActionStackEntry An action stack entry implementation.
	 *
	 * @author Agavi Foundation (info@agavi.org)
	 * @since  3.0.0
	 */
	public function getEntry ($index)
	{

		$retval = null;

		if ($index > -1 && $index < count($this->stack))
		{

			$retval = $this->stack[$index];

		}

		return $retval;

	}

	// -------------------------------------------------------------------------

	/**
	 * Retrieve the first entry.
	 *
	 * @return ActionStackEntry An action stack entry implementation.
	 *
	 * @author Agavi Foundation (info@agavi.org)
	 * @since  3.0.0
	 */
	public function getFirstEntry ()
	{

		$count  = count($this->stack);
		$retval = null;

		if ($count > 0)
		{

			$retval = $this->stack[0];

		}

		return $retval;

	}

	// -------------------------------------------------------------------------

	/**
	 * Retrieve the last entry.
	 *
	 * @return ActionStackEntry An action stack entry implementation.
	 *
	 * @author Agavi Foundation (info@agavi.org)
	 * @since  3.0.0
	 */
	public function getLastEntry ()
	{

		$count  = count($this->stack);
		$retval = null;

		if ($count > 0)
		{

			$retval = $this->stack[$count - 1];

		}

		return $retval;

	}

	// -------------------------------------------------------------------------

	/**
	 * Retrieve the size of this stack.
	 *
	 * @return int The size of this stack.
	 *
	 * @author Agavi Foundation (info@agavi.org)
	 * @since  3.0.0
	 */
	public function getSize ()
	{

		return count($this->stack);

	}

}

?>