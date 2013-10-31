<?php

class Asm_Solr_Model_Indexer_Catalog extends Mage_Index_Model_Indexer_Abstract
{

	/**
	 * Data key for matching result to be saved in
	 */
	const EVENT_MATCH_RESULT_KEY = 'solr_catalog_match_result';


	protected $_matchedEntities = array(
		Mage_Catalog_Model_Product::ENTITY => array(
			Mage_Index_Model_Event::TYPE_SAVE,
			Mage_Index_Model_Event::TYPE_MASS_ACTION,
			Mage_Index_Model_Event::TYPE_REINDEX
		),
	);


	protected function _construct()
	{
		$this->_init('solr/indexer_catalog');
	}

	/**
	 * Get Indexer name
	 *
	 * @return string
	 */
	public function getName()
	{
		return Mage::helper('solr')->__('Solr Catalog Search Index');
	}

	/**
	 * Get Indexer description
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return Mage::helper('solr')->__('Rebuild Solr catalog search index');
	}

	/**
	 * Register indexer required data inside event object
	 *
	 * @param Mage_Index_Model_Event $event
	 */
	protected function _registerEvent(Mage_Index_Model_Event $event)
	{
		// TODO: Implement _registerEvent() method.

		$event->addNewData(self::EVENT_MATCH_RESULT_KEY, true);
		switch ($event->getEntity()) {
			case Mage_Catalog_Model_Product::ENTITY:
				$this->registerCatalogProductEvent($event);
				break;
			// TODO handle other models
		}
	}

	protected function registerCatalogProductEvent(Mage_Index_Model_Event $event)
	{
		switch ($event->getType()) {
			case Mage_Index_Model_Event::TYPE_SAVE:
				$product = $event->getDataObject();
				/** @var Mage_Catalog_Model_Product $product */
				$event->addNewData('solr_update_product_id', $product->getId());
				break;
			case Mage_Index_Model_Event::TYPE_MASS_ACTION:


				break;

		}
	}

	/**
	 * Process event based on event state data
	 *
	 * @param Mage_Index_Model_Event $event
	 */
	protected function _processEvent(Mage_Index_Model_Event $event)
	{
		// TODO: Implement _processEvent() method.

//		if ($event->getData('solr_update_product_id'))
//		{
//			$this->callEventHandler($event);
//		}


	}

	public function reindexAll()
	{
		$resource = $this->getResource();
		/** @var Asm_Solr_Model_Resource_Indexer_Catalog $resource */
		$resource->rebuildIndex();



		$connection = Mage::helper('solr/connectionManager')->getConnection();
		/** @var $connection Asm_Solr_Model_Solr_Connection */

		$connection->commit();
	}
}