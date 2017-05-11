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
 * ConsoleRouting handles the routing for command line requests.
 *
 * @package    agavi
 * @subpackage routing
 *
 * @author     David Zülke <david.zuelke@bitextender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      1.0.0
 *
 * @version    $Id$
 */
class ConsoleRouting extends Routing
{
    /**
     * Initialize the routing instance.
     *
     * @param      Context $context A Context instance.
     * @param      array   $parameters An array of initialization parameters.
     *
     * @author     David Zülke <david.zuelke@bitextender.com>
     * @since      1.0.0
     */
    public function initialize(Context $context, array $parameters = array())
    {
        parent::initialize($context, $parameters);
        
        if (!$this->isEnabled()) {
            return;
        }
    }
    
    /**
     * Set the name of the called web service method as the routing input.
     *
     * @author     David Zülke <david.zuelke@bitextender.com>
     * @since      1.0.0
     */
    public function startup()
    {
        parent::startup();
        
        $this->input = $this->context->getRequest()->getInput();
    }
}
