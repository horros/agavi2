<?php
use Agavi\Request\RequestDataHolder;

class Disabled_IndexSuccessView extends SampleAppDisabledBaseView
{
	public function executeHtml(RequestDataHolder $rd)
	{
		$this->setupHtml($rd);

		// set the title
		$this->setAttribute('_title', 'Index Action');
	}
}

?>