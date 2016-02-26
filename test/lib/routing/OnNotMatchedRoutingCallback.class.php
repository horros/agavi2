<?php

class OnNotMatchedRoutingCallback extends \Agavi\Routing\RoutingCallback
{
	/**
	 * Gets executed when the route of this callback route did not match.
	 *
	 * @param      ExecutionContainer The original execution container.
	 *
	 * @author     Dominik del Bondio <ddb@bitxtender.com>
	 * @since      0.11.0
	 */
	public function onNotMatched(\Agavi\Controller\ExecutionContainer $container)
	{
		throw new \Agavi\Exception\AgaviException('Not Matched');
		return;
	}
}

?>