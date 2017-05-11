<?php
namespace Sandbox\Modules\ControllerTests\Views;

class ControllerTestErrorView extends \Agavi\View\View
{
    public function execute(\Agavi\Request\RequestDataHolder $rd)
    {
        $this->loadLayout();
    }
}
