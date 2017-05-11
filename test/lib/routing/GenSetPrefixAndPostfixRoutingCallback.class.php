<?php

class GenSetPrefixAndPostfixRoutingCallback extends \Agavi\Routing\RoutingCallback
{
    public function onGenerate(array $defaultParameters, array &$userParameters, array &$userOptions)
    {
        $userParameters['number'] = $this->context->getRouting()->createValue('value')->setPrefix('prefix-')->setPostfix('-postfix');
        return true;
    }
}
