<?php

class NonMatchingRoutingCallback extends \Agavi\Routing\RoutingCallback
{
	public function onMatched(array &$parameters, \Agavi\Dispatcher\ExecutionContainer $container)
	{
		return false;
	}
}

?>