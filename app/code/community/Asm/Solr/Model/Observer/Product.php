<?php


class Asm_Solr_Model_Observer_Product
{

	/**
	 * Listens for event catalog_product_save_after,
	 * fired by product create and edit actions
	 *
	 * @param Varien_Event_Observer $event
	 */
	public function updateProduct($event)
	{
		$indexer = Mage::getModel('solr/indexer_product');
		/* @var $indexer Asm_Solr_Model_Indexer_Product */
		$indexer->indexProduct($event->getProduct()->getEntityId());
	}

}