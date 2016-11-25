<?php

class MatchingRoutingCallback extends \Agavi\Routing\RoutingCallback
{
	public function onMatched(array &$parameters, \Agavi\Controller\ExecutionContainer $container)
	{
		$parameters['callback'] = 'set';
		return true;
	}
}

?>