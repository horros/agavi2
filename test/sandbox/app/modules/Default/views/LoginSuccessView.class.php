<?php

class Default_LoginSuccessView extends SandboxDefaultBaseView
{
	public function executeHtml(\Agavi\Request\RequestDataHolder $rd)
	{
		$this->setupHtml($rd);

		$this->setAttribute('title', 'Login');
	}
}

?>