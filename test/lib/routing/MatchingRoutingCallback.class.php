<?php

class MatchingRoutingCallback extends \Agavi\Routing\RoutingCallback
{
	public function onMatched(array &$parameters, \Agavi\Dispatcher\ExecutionContainer $container)
	{
		$parameters['callback'] = 'set';
		return true;
	}
}

?>