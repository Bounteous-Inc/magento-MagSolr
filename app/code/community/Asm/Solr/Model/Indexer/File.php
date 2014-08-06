<?php
/**
 * Copyright 2014 Infield Design
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License .
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied .
 * See the License for the specific language governing permissions and
 * limitations under the License .
 */


/**
 * An indexer for files linked from Magento CMS pages
 *
 *
 */
class Asm_Solr_Model_Indexer_File extends Mage_Index_Model_Indexer_Abstract
{

	protected $_matchedEntities = array(
		Asm_Solr_Model_Indexqueue_File::ENTITY => array(
			Mage_Index_Model_Event::TYPE_SAVE,
			Mage_Index_Model_Event::TYPE_MASS_ACTION,
			Mage_Index_Model_Event::TYPE_REINDEX,
			Mage_Index_Model_Event::TYPE_DELETE
		),
	);

	protected function _construct()
	{
		$this->_init('solr/indexer_file');
	}

	/**
	 * Get Indexer name
	 *
	 * @return string
	 */
	public function getName() {
		return Mage::helper('solr')->__('Solr File Search Index');
	}

	/**
	 * Get Indexer description
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return Mage::helper('solr')->__('Rebuild Solr CMS File search index');
	}

	/**
	 * Register indexer required data inside event object
	 *
	 * @param   Mage_Index_Model_Event $event
	 */
	protected function _registerEvent(Mage_Index_Model_Event $event) {
		if ($event->getEntity() == Asm_Solr_Model_Indexqueue_File::ENTITY
			&& $event->getType() == Mage_Index_Model_Event::TYPE_SAVE
		) {
			$event->setData('solr_update_file_id', $event->getDataObject()->getId());
		}
	}

	/**
	 * Process event based on event state data
	 *
	 * @param   Mage_Index_Model_Event $event
	 */
	protected function _processEvent(Mage_Index_Model_Event $event) {
		if ($event->getData('solr_update_file_id')) {
			$this->callEventHandler($event);
		}
	}

	public function reindexAll()
	{
		$resource = $this->_getResource();
		$resource->rebuildIndex();
	}
}