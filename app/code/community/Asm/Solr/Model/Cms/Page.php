<?php

/**
 * Extends the core Magento CMS page model to trigger index events when pages
 * are modified like in the product model. Also sets the ENTITY constant that
 * is missing as compared to other models.
 *
 */
class Asm_Solr_Model_Cms_Page extends Mage_Cms_Model_Page
{
	const ENTITY = 'cms_page';


	protected function _afterSave()
	{
		$result = parent::_afterSave();

		Mage::getSingleton('index/indexer')->processEntityAction(
			$this, self::ENTITY, Mage_Index_Model_Event::TYPE_SAVE
		);

		return $result;
	}

	protected function _beforeDelete()
	{
		Mage::getSingleton('index/indexer')->logEvent(
			$this, self::ENTITY, Mage_Index_Model_Event::TYPE_DELETE
		);
		return parent::_beforeDelete();
	}

	protected function _afterDelete()
	{
		parent::_afterDelete();
		Mage::getSingleton('index/indexer')->indexEvents(
			self::ENTITY, Mage_Index_Model_Event::TYPE_DELETE
		);
	}

	/**
	 * Init indexing process after cms page delete commit
	 *
	 */
	protected function _afterDeleteCommit()
	{
		parent::_afterDeleteCommit();
		Mage::getSingleton('index/indexer')->indexEvents(
			self::ENTITY, Mage_Index_Model_Event::TYPE_DELETE
		);
	}
}