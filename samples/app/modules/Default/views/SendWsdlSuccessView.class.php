<?php
use Agavi\Request\RequestDataHolder;

class Default_SendWsdlSuccessView extends SampleAppDefaultBaseView
{
    public function executeWsdl(RequestDataHolder $rd)
    {
        // we return a file pointer; the response will fpassthru() this for us
        return fopen($this->getAttribute('wsdl'), 'r');
    }
}
