<?php

class Asm_Solr_ResultController extends Mage_Core_Controller_Front_Action {

	public function indexAction()
	{
		$this->loadLayout();
		$layout = $this->getLayout();

		/** @var Asm_Solr_Model_Solr_Query $query */
		$query = Mage::getModel('solr/solr_query', array(
			'keywords' => $this->getRequest()->getParam('q')
		));

		$listToolbar = $layout->getBlock('product_list_toolbar');
		$limit  = $listToolbar->getLimit();
		$offset = $limit * ($listToolbar->getCurrentPage() - 1);


		$search   = Mage::getResourceModel('solr/search');
		$response = $search->search($query, $offset, $limit);

		$numberOfResults = $response->response->numFound;
		$resultDocuments = $response->response->docs;


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


		/** @var Mage_Catalog_Block_Product_List $listBlock */
		$listBlock = $layout->getBlock('search_result_list');
		$listBlock->setCollection($collection);

		$this->renderLayout();
	}


}