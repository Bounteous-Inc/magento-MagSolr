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
        $this->loadLayout();
        $layout = $this->getLayout();

        /** @var Asm_Solr_Model_Result $result */
        $result = Mage::getModel('solr/result');
        $result->setKeywords($this->getRequest()->getParam('q'));

        $listToolbar = $layout->getBlock('product_list_toolbar');
        $limit  = $listToolbar->getLimit();
        $offset = $limit * ($listToolbar->getCurrentPage() - 1);

        $result->load($limit, $offset);

        /** @var Mage_Catalog_Block_Product_List $listBlock */
        $listBlock = $layout->getBlock('search_result_list');
        $listBlock->setCollection($result->getProductCollection());
        Mage::register('solr_result', $result);

        $this->renderLayout();
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
		$this->loadLayout();
		$layout = $this->getLayout();

        /** @var Asm_Solr_Model_Result $result */
        $result = Mage::getModel('solr/result');
        $result->setKeywords($this->getRequest()->getParam('q'));

        $listToolbar = $layout->getBlock('product_list_toolbar');
        $limit  = $listToolbar->getLimit();
        $offset = $limit * ($listToolbar->getCurrentPage() - 1);

        $result->load($limit, $offset);

		/** @var Mage_Catalog_Block_Product_List $listBlock */
		$listBlock = $layout->getBlock('search_result_list');
		$listBlock->setCollection($result->getProductCollection());
        Mage::register('solr_result', $result);

		$this->renderLayout();
	}


}