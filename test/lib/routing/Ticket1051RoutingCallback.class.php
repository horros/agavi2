<?php

class Ticket1051RoutingCallback extends \Agavi\Routing\RoutingCallback
{
    public function onGenerate(array $defaultParameters, array &$userParameters, array &$userOptions)
    {
        $userOptions['authority'] = 'www.agavi.org';
        
        return true;
    }
}
