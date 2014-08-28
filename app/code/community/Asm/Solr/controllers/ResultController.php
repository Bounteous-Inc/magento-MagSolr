<?php

class Asm_Solr_ResultController extends Mage_Core_Controller_Front_Action {

    public function testAction()
    {
        /** @var Asm_Solr_Model_Result $result */
        $result = Mage::getModel('solr/result');
        $result->setKeywords($this->getRequest()->getParam('q'));

        $limit = 20;
        $offset = 0;
        $result->load($limit, $offset);

        $products = $result->getProductCollection();

        foreach ($products as $product)
        {
            echo $product->getName() . '<br/>';
        }
    }


    public function productAction()
    {
        $this->loadLayout()
            ->renderLayout();
    }

    public function pageAction()
    {
        $this
            ->loadLayout()
            ->renderLayout();

    }

    public function fileAction()
    {
        $this
            ->loadLayout()
            ->renderLayout();
    }

    public function indexAction()
	{

        $this->_redirect('*/*/product', array('_query' => $this->getRequest()->getParams()));
	}


}