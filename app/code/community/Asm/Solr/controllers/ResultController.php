<?php

class Asm_Solr_ResultController extends Mage_Core_Controller_Front_Action {

	public function indexAction()
	{
		$this->loadLayout();
		$layout = $this->getLayout();

		/** @var Asm_Solr_Model_Solr_Query $query */
		$query = Mage::helper('solr')->getQuery();
		$query->setKeywords($this->getRequest()->getParam('q'));
		$query->addFilter('type', 'catalog/product');

		$listToolbar = $layout->getBlock('product_list_toolbar');
		$limit  = $listToolbar->getLimit();
		$offset = $limit * ($listToolbar->getCurrentPage() - 1);


		$search   = Mage::getResourceModel('solr/search');
		$response = $search->search($query, $offset, $limit);
		Mage::register('solr/response', $response);


		$numberOfResults = $response->getNumberOfResults();
		/** @var Apache_Solr_Document[] $resultDocuments */
		$resultDocuments = $response->getDocuments();

		/** @var Asm_Solr_Model_Resource_Product_Collection $collection */
		$collection = Mage::getModel('solr/product')->getCollection();
		$collection->setSize($numberOfResults);

		$productIds = array();

		$fieldToAttributeMap = Mage::helper('solr/schema')->getFieldToAttributeMap();
		// build a reverse map, remove fields that have no atrribute
		$fieldToAttributeMap = array_filter($fieldToAttributeMap);
		$attributeToFieldMap = array_flip($fieldToAttributeMap);

		foreach ($resultDocuments as $document)
		{
			$product = Mage::getModel('catalog/product');

			$unmappedDocumentFields = $document->getFieldNames();

			// map "core/static" Solr document fields to Magento product attributes
			foreach ($attributeToFieldMap as $attribute => $field) {
				$product->setData($attribute, $document->{$field});

				// remove fields that have been mapped, leaves dynamic fields
				$fieldKey = array_search($field, $unmappedDocumentFields);
				if ($fieldKey !== false) {
					unset($unmappedDocumentFields[$fieldKey]);
				}
			}

			$dynamicFields = array_diff(
				$unmappedDocumentFields,
				array_keys(Mage::helper('solr/schema')->getFieldToAttributeMap())
			);

			// map Solr dynamic fields to Magento product attributes
			$dynamicFieldSuffixes = Mage::helper('solr/schema')->getDynamicFieldSuffixes();
			foreach ($dynamicFields as $dynamicField) {
				$fieldNameParts = explode('_', $dynamicField);

				// do we have a valid dynamic field? If so, generate attribute name, map to Solr field
				if (in_array($fieldNameParts[count($fieldNameParts) - 1], $dynamicFieldSuffixes)) {
					array_pop($fieldNameParts);
					$attributeName = implode('_', $fieldNameParts);
					$product->setData($attributeName, $document->{$dynamicField});
				}
			}

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