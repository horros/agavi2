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

namespace Agavi\Config;

use Agavi\Core\Context;
use Agavi\Config\Util\Dom\XmlConfigDomDocument;

/**
 * XmlConfigHandlerInterface is the interface that config handlers may implement to
 * indicate that they wish to process a DOMDocument directly.
 *
 * @package    agavi
 * @subpackage config
 *
 * @author     David Zülke <dz@bitxtender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.11.0
 *
 * @version    $Id$
 */
interface XmlConfigHandlerInterface
{
    /**
     * Initialize this ConfigHandler.
     *
     * @param      Context $context    The context to work with (if available).
     * @param      array   $parameters An associative array of initialization parameters.
     *
     * @throws     <b>InitializationException</b> If an error occurs while
     *                                                 initializing the
     *                                                 ConfigHandler
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function initialize(Context $context = null, $parameters = array());
    
    /**
     * Execute this configuration handler.
     *
     * @param      XmlConfigDomDocument $document The document to parse.
     *
     * @return     string Data to be written to a cache file.
     *
     * @throws     <b>ParseException</b> If a requested configuration file is
     *                                        improperly formatted.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function execute(XmlConfigDomDocument $document);
}
