<?php
use Agavi\Request\RequestDataHolder;

class Products_Product_ViewAction extends SampleAppProductsBaseAction
{
	public function executeRead(RequestDataHolder $rd)
	{
		// the validator already pulled the product object from the database and put it into the request data
		// so there's not much we need to do here
		$this->setAttribute('product', $rd->getParameter('product'));
		
		return 'Success';
	}
}