<?php
namespace Sandbox\Modules\ControllerTests\Views;

class ControllerTestSuccessView extends \Agavi\View\View
{
	public function execute(\Agavi\Request\RequestDataHolder $rd)
	{
		$this->loadLayout();
	}
}

?>