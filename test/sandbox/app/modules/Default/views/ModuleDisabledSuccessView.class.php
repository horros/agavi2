<?php

class Default_ModuleDisabledSuccessView extends SandboxDefaultBaseView
{
	public function executeHtml(\Agavi\Request\RequestDataHolder $rd)
	{
		$this->setupHtml($rd);
		
		$this->getResponse()->setHttpStatusCode('503');
	}
}

?>
