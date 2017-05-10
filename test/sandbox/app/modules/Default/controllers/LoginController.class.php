<?php
use Agavi\Request\RequestDataHolder;

class Default_LoginController extends SandboxDefaultBaseController
{
	public function execute(RequestDataHolder $rd)
	{
		// remove this execute() method and create executeRead() and executeWrite() methods or equivalents
		throw new Exception('Default_LoginController is not yet implemented. ' .
			'This is only a stub that serves as a reminder for you to do this.');
	}

	public function getDefaultViewName()
	{
		return 'Success';
	}
}

?>