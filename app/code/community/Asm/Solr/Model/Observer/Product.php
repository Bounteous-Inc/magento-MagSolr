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
		$indexer = Mage::getModel('solr/resource_indexer_catalog');
		/* @var $indexer Asm_Solr_Model_Resource_Indexer_Catalog */
		$indexer->rebuildIndex(null, $event->getProduct()->getId());
	}

}