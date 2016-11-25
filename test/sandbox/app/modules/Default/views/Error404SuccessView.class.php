<?php

class Default_Error404SuccessView extends SandboxDefaultBaseView
{
	public function executeHtml(RequestDataHolder $rd)
	{
		$this->setupHtml($rd);

		$this->getResponse()->setHttpStatusCode('404');
	}
}

?>