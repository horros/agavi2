<?php
namespace Agavi\Logging;
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
 * StderrLoggerAppender appends a LoggerMessages to the stderr.
 *
 * @package    agavi
 * @subpackage logging
 *
 * @author     Bob Zoller <bob@agavi.org>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.10.0
 *
 * @version    $Id$
 */
class StderrLoggerAppender extends StreamLoggerAppender
{
	/**
	 * Initialize the object.
	 *
	 * @param      Context $context A Context instance.
	 * @param      array   $parameters An associative array of initialization parameters.
	 *
	 * @author     Bob Zoller <bob@agavi.org>
	 * @since      0.10.0
	 */
	public function initialize(Context $context, array $parameters = array())
	{
		$parameters['destination'] = 'php://stderr';
		// 'a' doesn't work on Linux
		// http://bugs.php.net/bug.php?id=45303
		$parameters['mode'] = 'w';
		
		parent::initialize($context, $parameters);
	}
}

?>