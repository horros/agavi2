<?php

class Default_LoginErrorView extends SandboxDefaultBaseView
{
	public function executeHtml(\Agavi\Request\RequestDataHolder $rd)
	{
		$this->setupHtml($rd);

		$this->setAttribute('title', 'Login');
	}
}

?>