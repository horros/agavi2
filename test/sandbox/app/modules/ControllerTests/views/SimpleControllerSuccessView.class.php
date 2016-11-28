<?php

class ControllerTests_SimpleControllerSuccessView extends SandboxControllerTestsBaseView
{
	public function executeHtml(\Agavi\Request\RequestDataHolder $rd)
	{
		$this->setupHtml($rd);

		$this->setAttribute('_title', 'SimpleAction');
	}
}

?>