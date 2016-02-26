<?php

class ControllerTests_SimpleActionSuccessView extends SandboxControllerTestsBaseView
{
	public function executeHtml(\Agavi\Request\RequestDataHolder $rd)
	{
		$this->setupHtml($rd);

		$this->setAttribute('_title', 'SimpleAction');
	}
}

?>