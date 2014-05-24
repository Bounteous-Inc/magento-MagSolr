<?php

class Asm_Solr_Model_Indexer_Cms extends Mage_Index_Model_Indexer_Abstract
{

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
		// TODO: Implement _registerEvent() method.
	}

	/**
	 * Process event based on event state data
	 *
	 * @param Mage_Index_Model_Event $event
	 */
	protected function _processEvent(Mage_Index_Model_Event $event)
	{
		// TODO: Implement _processEvent() method.
	}

	public function reindexAll()
	{
		$resource = $this->_getResource();
		$resource->rebuildIndex();

		$connection = Mage::helper('solr/connectionManager')->getConnection();
		$connection->commit();
	}
}