<?php

class Products_Product_ViewErrorView extends AgaviSampleAppProductsBaseView
{
	/**
	 * Execute any presentation logic and set template attributes.
	 *
	 */
	public function executeHtml(RequestDataHolder $rd)
	{
		return $this->createForwardContainer(Config::get('actions.error_404_module'), Config::get('actions.error_404_action'));
	}

	/**
	 * Execute any presentation logic for JSON requests.
	 */
	public function executeJson(RequestDataHolder $rd)
	{
		return json_encode(
			array(
				'_error' => 404,
			)
		);
	}
	
	public function executeText(RequestDataHolder $rd)
	{
		$this->getResponse()->setExitCode(1);
		
		return 'No such product';
	}

	/**
	 * Execute any presentation logic for SOAP requests.
	 */
	public function executeSoap(RequestDataHolder $rd)
	{
		// fault code must be "Server", check the SOAP spec
		// do not throw the exception please. it can be done with some fiddling, but returning it is a much better idea
		return new SoapFault('Server', 'Unknown Product ' . $rd->getParameter('id'));
	}

	/**
	 * Execute any presentation logic for XMLRPC requests.
	 */
	public function executeXmlrpc(RequestDataHolder $rd)
	{
		return array('faultCode' => 101, 'faultString' => 'Unknown Product ' . $rd->getParameter('id'));
	}
}

?>