<?php

class Default_SendWsdlSuccessView extends AgaviSampleAppDefaultBaseView
{
	public function executeWsdl(RequestDataHolder $rd)
	{
		// we return a file pointer; the response will fpassthru() this for us
		return fopen($this->getAttribute('wsdl'), 'r');
	}
}

?>