<?php

class Disabled_IndexSuccessView extends AgaviSampleAppDisabledBaseView
{
	public function executeHtml(RequestDataHolder $rd)
	{
		$this->setupHtml($rd);

		// set the title
		$this->setAttribute('_title', 'Index Action');
	}
}

?>