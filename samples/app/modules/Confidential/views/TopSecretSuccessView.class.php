<?php
use Agavi\Request\RequestDataHolder;
class Confidential_TopSecretSuccessView extends SampleAppConfidentialBaseView
{
	public function executeHtml(RequestDataHolder $rd)
	{
		$this->setupHtml($rd);

		// set the title
		$this->setAttribute('_title', $this->tm->_('Secure Controller', 'default.Login'));

	}

}

?>