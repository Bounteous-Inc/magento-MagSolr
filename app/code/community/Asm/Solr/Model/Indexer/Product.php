<?php


class Asm_Solr_Model_Indexer_Product extends Asm_Solr_Model_Indexer_Abstract
{

	public function indexProduct($productId)
	{
		$document = $this->getDocument($productId);

		$connection = Mage::helper('solr/connectionManager')->getConnection();
		/** @var $connection Asm_Solr_Model_Solr_Connection */

		$connection->addDocument($document);
		$connection->commit();
	}


	protected function getDocument($productId)
	{
		$helper   = Mage::helper('solr');
		$product  = Mage::getModel('catalog/product')->load($productId);
		/** @var $product Mage_Catalog_Model_Product */
		$document = new Apache_Solr_Document();

		$document->setField('appKey',    'Asm_Solr');
		$document->setField('type',      'product');

		$document->setField('id',        $helper->getProductDocumentId($product->getEntityId()));
		$document->setField('sku',       $product->getSku());
		$document->setField('productId', $product->getEntityId());

		// TODO What if product in multiple stores?
		// @see Websites / "Product in Websites"
		$document->setField('storeId',   Mage::app()->getStore()->getStoreId());

		$document->setField('title',     $product->getName());
		$document->setField('content',   $product->getDescription());
		$document->setField('url',       $product->getProductUrl());


		// TODO Add product type

		return $document;
	}

}