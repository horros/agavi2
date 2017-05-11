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
use Agavi\Util\ParameterHolder;

/**
 * AgaviLoggerAppender allows you to specify a destination for log data and
 * provide a custom layout for it, through which all log messages will be
 * formatted.
 *
 * @package    agavi
 * @subpackage logging
 *
 * @author     David Zülke <dz@bitxtender.com>
 * @author     Bob Zoller <bob@agavi.org>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.10.0
 *
 * @version    $Id$
 */
abstract class LoggerAppender extends ParameterHolder
{
    /**
     * @var        Context An Context instance.
     */
    protected $context = null;

    /**
     * @var        LoggerLayout An LoggerLayout instance.
     */
    protected $layout = null;

    /**
     * Initialize the object.
     *
     * @param      Context $context    A Context instance.
     * @param      array   $parameters An associative array of initialization parameters.
     *
     * @author     Bob Zoller <bob@agavi.org>
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.10.0
     */
    public function initialize(Context $context, array $parameters = array())
    {
        $this->context = $context;
        
        $this->setParameters($parameters);
    }

    /**
     * Retrieve the current application context.
     *
     * @return     Context A Context instance.
     *
     * @author     Sean Kerr <skerr@mojavi.org>
     * @since      0.10.0
     */
    final public function getContext()
    {
        return $this->context;
    }

    /**
     * Retrieve the layout.
     *
     * @return     LoggerLayout A Layout instance, if it has been set,
     *                               otherwise null.
     *
     * @author     Sean Kerr <skerr@mojavi.org>
     * @since      0.9.0
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * Set the layout.
     *
     * @param      LoggerLayout $layout A Layout instance.
     *
     * @return     LoggerAppender
     *
     * @author     Sean Kerr <skerr@mojavi.org>
     * @since      0.9.0
     */
    public function setLayout(LoggerLayout $layout)
    {
        $this->layout = $layout;
        return $this;
    }

    /**
     * Execute the shutdown procedure.
     *
     * @author     Sean Kerr <skerr@mojavi.org>
     * @since      0.9.0
     */
    abstract function shutdown();

    /**
     * Write log data to this appender.
     *
     * @param      LoggerMessage $message Log data to be written.
     *
     * @author     Sean Kerr <skerr@mojavi.org>
     * @since      0.9.0
     */
    abstract function write(LoggerMessage $message);
}
