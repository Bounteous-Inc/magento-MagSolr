<?php

class Asm_Solr_Model_Indexer_Cms extends Mage_Index_Model_Indexer_Abstract
{

	protected $_matchedEntities = array(
		Asm_Solr_Model_Cms_Page::ENTITY => array(
			Mage_Index_Model_Event::TYPE_SAVE,
			Mage_Index_Model_Event::TYPE_MASS_ACTION,
			Mage_Index_Model_Event::TYPE_REINDEX,
			Mage_Index_Model_Event::TYPE_DELETE
		),
	);


	protected function _construct()
	{
		$this->_init('solr/indexer_cms');
	}

	/**
	 * Get Indexer name
	 *
	 * @return string
	 */
	public function getName()
	{
		return Mage::helper('solr')->__('Solr CMS Search Index');
	}

	/**
	 * Get Indexer description
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return Mage::helper('solr')->__('Rebuild Solr CMS search index');
	}

	/**
	 * Register indexer required data inside event object
	 *
	 * @param Mage_Index_Model_Event $event
	 */
	protected function _registerEvent(Mage_Index_Model_Event $event)
	{
		if ($event->getEntity() == Asm_Solr_Model_Cms_Page::ENTITY
			&& $event->getType() == Mage_Index_Model_Event::TYPE_SAVE
		) {
			$event->setData('solr_update_page_id', $event->getDataObject()->getId());
		}
	}

	/**
	 * Process event based on event state data
	 *
	 * @param Mage_Index_Model_Event $event
	 */
	protected function _processEvent(Mage_Index_Model_Event $event)
	{
		if ($event->getData('solr_update_page_id')) {
			$this->callEventHandler($event);
		}
	}

	public function reindexAll()
	{
		$resource = $this->_getResource();
		$resource->rebuildIndex();
	}
}