<?php

class NonMatchingRoutingCallback extends \Agavi\Routing\RoutingCallback
{
	public function onMatched(array &$parameters, \Agavi\Controller\ExecutionContainer $container)
	{
		return false;
	}
}

?>