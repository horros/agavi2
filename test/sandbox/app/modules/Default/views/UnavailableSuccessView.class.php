<?php

class Default_UnavailableSuccessView extends SandboxDefaultBaseView
{
	public function executeHtml(RequestDataHolder $rd)
	{
		$this->setupHtml($rd);
		
		$this->getResponse()->setHttpStatusCode('503');
	}
}

?>