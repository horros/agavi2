<?php
namespace Agavi\Filter;

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2005-2011 the Agavi Project.                                |
// | Based on the Mojavi3 MVC Framework, Copyright (c) 2003-2005 Sean Kerr.    |
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
use Agavi\Dispatcher\ExecutionContainer;
use Agavi\Util\ParameterHolder;

/**
 * Filter provides a way for you to intercept incoming requests or outgoing
 * responses.
 *
 * @package    agavi
 * @subpackage filter
 *
 * @author     Sean Kerr <skerr@mojavi.org>
 * @author     David Zülke <dz@bitxtender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.9.0
 *
 * @version    $Id$
 */
abstract class Filter extends ParameterHolder implements FilterInterface
{
    /**
     * @var        Context A Context instance.
     */
    protected $context = null;

    /**
     * Retrieve the current application context.
     *
     * @return     Context The current Context instance.
     *
     * @author     Sean Kerr <skerr@mojavi.org>
     * @since      0.9.0
     */
    final public function getContext()
    {
        return $this->context;
    }

    /**
     * Initialize this Filter.
     *
     * @param      Context $context    The current application context.
     * @param      array   $parameters An associative array of initialization parameters.
     *
     * @throws     <b>AgaviInitializationException</b> If an error occurs while
     *                                                 initializing this Filter.
     *
     * @author     Sean Kerr <skerr@mojavi.org>
     * @since      0.9.0
     */
    public function initialize(Context $context, array $parameters = array())
    {
        $this->context = $context;

        $this->setParameters($parameters);
    }
    
    /**
     * The default "execute" method, which just calls continues in the chain.
     *
     * @param      FilterChain        $filterChain A FilterChain instance.
     * @param      ExecutionContainer $container   The current execution container.
     *
     * @author     David Zülke <dz@bitxtender.com>
     * @since      0.11.0
     */
    public function execute(FilterChain $filterChain, ExecutionContainer $container)
    {
        $filterChain->execute($container);
    }
}
