<?php
namespace Agavi\Routing;
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
use Agavi\Core\Context;

/**
 * WebserviceRouting handles the routing for Web Service requests.
 *
 * @package    agavi
 * @subpackage routing
 *
 * @author     David Zülke <dz@bitxtender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.11.0
 *
 * @version    $Id$
 */
class WebserviceRouting extends Routing
{
	/**
	 * Initialize the routing instance.
	 *
	 * @param      Context $context A Context instance.
	 * @param      array   $parameters An array of initialization parameters.
	 *
	 * @author     David Zülke <dz@bitxtender.com>
	 * @since      0.11.0
	 */
	public function initialize(Context $context, array $parameters = array())
	{
		parent::initialize($context, $parameters);
		
		if(!$this->isEnabled()) {
			return;
		}
		
		$this->updateInput();
	}
	
	/**
	 * Set the name of the called web service method as the routing input.
	 *
	 * @author     David Zülke <dz@bitxtender.com>
	 * @since      0.11.0
	 */
	public function updateInput()
	{
		$this->input = $this->context->getRequest()->getInvokedMethod();
	}
}

?>