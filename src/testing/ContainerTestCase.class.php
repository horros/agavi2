<?php
namespace Agavi\Testing;
// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2005-2010 the Agavi Project.                                |
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
use Agavi\Request\RequestDataHolder;
use Agavi\Response\Response;

/**
 * ContainerTestCase is the base class for all tests that target a specific
 * container execution and provides the necessary assertions
 * 
 * 
 * @package    agavi
 * @subpackage testing
 *
 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
 * @copyright  The Agavi Project
 *
 * @since      1.0.0
 *
 * @version    $Id: FlowTestCase.class.php 3843 2009-02-16 14:12:47Z felix $
 */
abstract class ContainerTestCase extends FragmentTestCase
{
	/**
	 * @var        string the name of the controller to use
	 */
	protected $controllerName;
	
	/**
	 * @var        string the name of the module the controller resides in
	 */
	protected $moduleName;
	
	/**
	 * @var        Response the response after the dispatch call
	 */
	protected $response;
	
	/**
	 * dispatch the request
	 *
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0 
	 */
	public function execute($arguments = null, $outputType = null, $requestMethod = null)
	{
		$context = Context::getInstance();
		
		$dispatcher = $context->getDispatcher();
		$dispatcher->setParameter('send_response', false);
		
		if(!($arguments instanceof RequestDataHolder)) {
			$arguments = $this->createRequestDataHolder(array(RequestDataHolder::SOURCE_PARAMETERS => $arguments));
		}
		
		$this->response = $dispatcher->dispatch(null, $dispatcher->createExecutionContainer($this->moduleName, $this->controllerName, $arguments, $outputType, $requestMethod));
	}
	
	/**
	 * assert that the response has a given tag
	 * 
	 * @see the documentation of PHPUnit's assertTag()
	 * 
	 * @param      array $matcher the matcher describing the tag
	 * @param      string $message an optional message
	 * @param      bool $isHtml
	 * 
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	public function assertResponseHasTag($matcher, $message = '', $isHtml = true)
	{
		$this->assertTag($matcher, $this->response->getContent(), $message, $isHtml);
	}
	
	
	/**
	 * assert that the response does not have a given tag
	 * 
	 * @see the documentation of PHPUnit's assertTag()
	 * 
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	public function assertResponseHasNotTag($matcher, $message = '', $isHtml = true)
	{
		$this->assertNotTag($matcher, $this->response->getContent(), $message, $isHtml);
	}
}

?>