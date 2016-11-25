<?php
namespace Agavi\Renderer\EzcTemplate;

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
 * A renderer produces the output as defined by a View
 *
 * @package    agavi
 * @subpackage renderer
 *
 * @author     Felix Weis <mail@felixweis.com>
 * @author     David Zülke <dz@bitxtender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.11.0
 *
 * @version    $Id$
 */
class EzctemplateTemplate extends ezcTemplate implements EzctemplateTemplateInterface
{
	/**
	 * @var        Context The Agavi Context.
	 */
	protected $context = null;
	
	/**
	 * Retrieve the current application context.
	 *
	 * @return     Context The current Context instance.
	 *
	 * @author     David Zülke <dz@bitxtender.com>
	 * @since      0.11.0
	 */
	public final function getContext()
	{
		return $this->context;
	}
	
	/**
	 * Retrieve the current application context.
	 *
	 * @param      Context $context The current Context instance.
	 *
	 * @author     David Zülke <dz@bitxtender.com>
	 * @since      0.11.0
	 */
	public final function setContext(Context $context)
	{
		$this->context = $context;
	}
}

?>