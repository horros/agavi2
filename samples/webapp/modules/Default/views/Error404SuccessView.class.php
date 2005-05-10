<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2003, 2004 Agavi Foundation.                                |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org.                              |
// +---------------------------------------------------------------------------+

class Error404SuccessView extends PHPView
{

	// +-----------------------------------------------------------------------+
	// | METHODS                                                               |
	// +-----------------------------------------------------------------------+

	/**
	 * Execute any presentation logic and set template attributes.
	 *
	 * @return void
	 *
	 * @author Agavi Foundation (info@agavi.org)
	 * @since  1.0.0
	 */
	public function execute ()
	{

		// get the request
		$request = $this->getContext()->getRequest();

		// set our template
		$this->setTemplate('Error404Success.php');

		// set the title
		$this->setAttribute('title', 'Error 404 Action');

		// set originally requested module/action attributes
		// these attributes are provided by the controller in the event
		// of a 404 error
		$this->setAttribute('requested_module', $request->getAttribute('requested_module'));
		$this->setAttribute('requested_action', $request->getAttribute('requested_action'));

		// build our menu
		require_once(MO_MODULE_DIR . '/Default/lib/build_menu.php');

	}

}

?>