<?php

class Products_IndexAction extends AgaviSampleAppProductsBaseAction
{
	public function execute(RequestDataHolder $rd)
	{
		$products = $this->getContext()->getModel('ProductFinder')->retrieveAll();
		
		$this->setAttribute('products', $products);
		
		return 'Success';
	}
}

?>