<?php
namespace Sandbox\Modules\ControllerTests\Views;

use Sandbox\Modules\ControllerTests\Lib\View\SandboxControllerTestsBaseView;

class SimpleControllerSuccessView extends SandboxControllerTestsBaseView
{
	public function executeHtml(\Agavi\Request\RequestDataHolder $rd)
	{
		$this->setupHtml($rd);

		$this->setAttribute('_title', 'SimpleAction');
	}
}
