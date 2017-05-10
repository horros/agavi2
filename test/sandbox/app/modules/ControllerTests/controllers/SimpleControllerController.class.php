<?php

namespace Sandbox\Modules\ControllerTests\Controllers;

use Sandbox\Modules\ControllerTests\Lib\Controller\SandboxControllerTestsBaseController;

class SimpleControllerController extends SandboxControllerTestsBaseController
{
	
	public function isSimple()
	{
		return true;
	}
	
	/**
	 * Returns the default view if the controller does not serve the request
	 * method used.
	 *
	 * @return     mixed <ul>
	 *                     <li>A string containing the view name associated
	 *                     with this controller; or</li>
	 *                     <li>An array with two indices: the parent module
	 *                     of the view to be executed and the view to be
	 *                     executed.</li>
	 *                   </ul>
	 */
	public function getDefaultViewName()
	{
		return 'Success';
	}
}

?>