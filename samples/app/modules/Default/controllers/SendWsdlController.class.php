<?php
use Agavi\Request\RequestDataHolder;

class Default_SendWsdlController extends SampleAppDefaultBaseController
{
    public function execute(RequestDataHolder $rd)
    {
        if (Config::get('core.debug')) {
            ini_set('soap.wsdl_cache_enabled', 0);
        }
        
        try {
            $sc = AgaviContext::getInstance('soap');
            $wsdl = $sc->getRouting()->getWsdlPath();
            if ($wsdl && is_readable($wsdl)) {
                $this->setAttribute('wsdl', $wsdl);
                return 'Success';
            }
        } catch (Exception $e) {
        }
        return 'Error';
    }
}
