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
use Agavi\Exception\AgaviException;
use Agavi\Exception\LoggingException;

/**
 * StreamLoggerAppender appends AgaviLoggerMessages to a given stream.
 *
 * @package    agavi
 * @subpackage logging
 *
 * @author     David Zülke <dz@bitxtender.com>
 * @author     Bob Zoller <bob@agavi.org>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.11.2
 *
 * @version    $Id$
 */
class StreamLoggerAppender extends LoggerAppender
{
    /**
     * @var        resource The resource of the stream this appender is writing to.
     */
    protected $handle = null;

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
        parent::initialize($context, $parameters);

        if (!isset($parameters['destination'])) {
            throw new AgaviException('No destination given for appending');
        }
    }

    /**
     * Retrieve the handle for this stream appender.
     *
     * @throws     LoggingException if stream cannot be opened for
     *                                          appending.
     *
     * @return     resource The opened resource handle.
     *
     * @author     Bob Zoller <bob@agavi.org>
     * @since      0.10.0
     */
    protected function getHandle()
    {
        $destination = $this->getParameter('destination');
        if (is_null($this->handle)) {
            $this->handle = fopen($destination, $this->getParameter('mode', 'a'));
            if (!$this->handle) {
                throw new LoggingException('Cannot open stream "' . $destination . '".');
            }
        }
        return $this->handle;
    }

    /**
     * Execute the shutdown procedure.
     *
     * If open, close the stream handle.
     *
     * @author     Bob Zoller <bob@agavi.org>
     * @since      0.10.0
     */
    public function shutdown()
    {
        if (!is_null($this->handle)) {
            fclose($this->handle);
        }
    }

    /**
     * Write log data to this appender.
     *
     * @param      LoggerMessage $message Log data to be written.
     *
     * @throws     LoggingException if no Layout is set or the stream
     *                                          cannot be written.
     *
     *
     * @author     Bob Zoller <bob@agavi.org>
     * @since      0.10.0
     */
    public function write(LoggerMessage $message)
    {
        if (($layout = $this->getLayout()) === null) {
            throw new LoggingException('No Layout set');
        }

        $str = sprintf("%s\n", $this->getLayout()->format($message));
        if (fwrite($this->getHandle(), $str) === false) {
            throw new LoggingException('Cannot write to stream "' . $this->getParameter('destination') . '".');
        }
    }
}
