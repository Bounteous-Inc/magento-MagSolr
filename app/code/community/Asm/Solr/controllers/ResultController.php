<?php

class Asm_Solr_ResultController extends Mage_Core_Controller_Front_Action {

	public function indexAction()
	{

		$connection = Mage::helper('solr/connectionManager')->getConnection();

		/** @var $query Asm_Solr_Model_Solr_Query */
		$query = Mage::getModel('solr/solr_query', array(
			'keywords' => $this->getRequest()->getParam('q')
		));



		$search   = Mage::getResourceModel('solr/search');
		$response = $search->search($query);

		$resultDocuments = $response->response->docs;


		$this->loadLayout();

		/** @var Mage_Catalog_Model_Resource_Product_Collection $collection */
		$collection = Mage::getModel('catalog/product')->getCollection();

		$productIds = array();

		foreach ($resultDocuments as $doc)
		{
			$product = Mage::getModel('catalog/product');

			// set all product data here
			$product->setData('entity_id',   $doc->productId);
			$product->setData('name',        $doc->title);
			$product->setData('description', $doc->content);

			$productIds[] = $doc->productId;

			$collection->addItem($product);
		}

		$collection->_setIsLoaded(true);
		$collection->addAttributeToFilter('entity_id', array('in' => $productIds));

//		$collection = Mage::getResourceModel('catalog/product_collection')
//			->addAttributeToFilter('entity_id', array('in'=>$ids));

		$layout = $this->getLayout();
		/** @var Mage_Catalog_Block_Product_List $listBlock */
		$listBlock = $layout->getBlock('search_result_list');

		$listBlock->setCollection($collection);

		$this->renderLayout();
	}


}