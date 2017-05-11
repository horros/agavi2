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

/**
 * AgaviILogger is the interface for all Logger implementations
 *
 * @package    agavi
 * @subpackage logging
 *
 * @author     David Zülke <dz@bitxtender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.11.0
 *
 * @version    $Id$
 */
interface LoggerInterface
{
    /**
     * Fatal level.
     *
     * @since      0.9.0
     */
    const FATAL = 1;

    /**
     * Error level.
     *
     * @since      0.9.0
     */
    const ERROR = 2;

    /**
     * Warning level.
     *
     * @since      0.9.0
     */
    const WARN = 4;

    /**
     * Information level.
     *
     * @since      0.9.0
     */
    const INFO = 8;

    /**
     * Debug level.
     *
     * @since      0.9.0
     */
    const DEBUG = 16;

    /**
     * All levels. (2^32-1)
     *
     * @since      0.11.0
     */
    const ALL = 4294967295;

    /**
     * Log a message.
     *
     * @param      LoggerMessage $message A Message instance.
     *
     * @author     Sean Kerr <skerr@mojavi.org>
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function log(LoggerMessage $message);

    /**
     * Set an appender.
     *
     * If an appender with the name already exists, an exception will be thrown.
     *
     * @param      string         $name     An appender name.
     * @param      LoggerAppender $appender An Appender instance.
     *
     * @throws     <b>AgaviLoggingException</b> If an appender with the name
     *                                          already exists.
     *
     * @author     Sean Kerr <skerr@mojavi.org>
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function setAppender($name, LoggerAppender $appender);

    /**
     * Returns a list of appenders for this logger.
     *
     * @return     array An associative array of appender names and instances.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function getAppenders();

    /**
     * Set the level.
     *
     * @param      int $level A log level.
     *
     * @author     Sean Kerr <skerr@mojavi.org>
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function setLevel($level);

    /**
     * Get the level.
     *
     * @author     Peter Limbach <peter.limbach@gmail.com>
     * @since      1.1.0
     */
    public function getLevel();

    /**
     * Execute the shutdown procedure.
     *
     * @author     Sean Kerr <skerr@mojavi.org>
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function shutdown();
}
