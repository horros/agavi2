<?php
use Agavi\Request\RequestDataHolder;

class Products_IndexController extends SampleAppProductsBaseController
{
    public function execute(RequestDataHolder $rd)
    {
        $products = $this->getContext()->getModel('ProductFinder')->retrieveAll();
        
        $this->setAttribute('products', $products);
        
        return 'Success';
    }
}
