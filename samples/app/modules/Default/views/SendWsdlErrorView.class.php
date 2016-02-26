<?php

class Default_SendWsdlErrorView extends AgaviSampleAppDefaultBaseView
{
	public function executeWsdl(RequestDataHolder $rd)
	{
		$this->getResponse()->setHttpStatusCode(404);
	}
}

?>