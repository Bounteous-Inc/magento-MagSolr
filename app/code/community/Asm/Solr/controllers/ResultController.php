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
		/** @var Apache_Solr_Document[] $resultDocuments */
		$resultDocuments = $response->response->docs;

		/** @var Asm_Solr_Model_Resource_Product_Collection $collection */
		$collection = Mage::getModel('solr/product')->getCollection();
		$collection->setSize($numberOfResults);

		$productIds = array();

		$fieldToAttributeMap = Mage::helper('solr')->getFieldToAttributeMap();
		// build a reverse map, remove fields that have no atrribute
		$fieldToAttributeMap = array_filter($fieldToAttributeMap);
		$attributeToFieldMap = array_flip($fieldToAttributeMap);

		foreach ($resultDocuments as $document)
		{
			$product = Mage::getModel('catalog/product');

			$unmappedDocumentFields = $document->getFieldNames();

			foreach ($attributeToFieldMap as $attribute => $field) {
				$product->setData($attribute, $document->{$field});

				// remove fields that have been mapped, should leave dynamic fields
				$fieldKey = array_search($field, $unmappedDocumentFields);
				if ($fieldKey !== false) {
					unset($unmappedDocumentFields[$fieldKey]);
				}
			}

			$dynamicFields = array_diff(
				$unmappedDocumentFields,
				Mage::helper('solr')->getFieldToAttributeMap()
			);

			// TODO map dynamic fields to attributes

			$productIds[] = $document->productId;

			$collection->addItem($product);
		}

		$collection->addAttributeToFilter('entity_id', array('in' => $productIds));


		/** @var Mage_Catalog_Block_Product_List $listBlock */
		$listBlock = $layout->getBlock('search_result_list');
		$listBlock->setCollection($collection);

		$this->renderLayout();
	}


}