<?php

class Default_SecureSuccessView extends SandboxDefaultBaseView
{
	public function executeHtml(RequestDataHolder $rd)
	{
		$this->setupHtml($rd);
		
		$this->getResponse()->setHttpStatusCode('403');
	}
}

?>
