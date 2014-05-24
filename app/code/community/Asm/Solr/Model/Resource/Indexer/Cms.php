<?php

class Asm_Solr_Model_Resource_Indexer_Cms extends Mage_Core_Model_Resource_Db_Abstract
{
	/**
	 * Resource initialization
	 */
	protected function _construct() {
		$this->_setResource('core');
	}

	/**
	 * @param Mage_Index_Model_Event $event
	 */
	public function CmsPageSave(Mage_Index_Model_Event $event) {
		$this->indexPage($event->getData('solr_update_page_id'));
	}

	/**
	 * Rebuild the index for all stores at once or just one specific store.
	 *
	 * @param int|null $storeId Store to re-index single int store ID to re-index single store, null to re-index all stores
	 * @param int|array|null $pageIds Page to re-index, single int page ID to re-index a single page, array of page IDs to re-index multiple pages, null to re-index all pages
	 */
	public function rebuildIndex($storeId = null, $pageIds = null)
	{

	}

	protected function indexPage($pageId)
	{
		$helper  = Mage::helper('solr');
		$baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
		$host    = parse_url($baseUrl, PHP_URL_HOST);

		$page = Mage::getModel('cms/page')->load($pageId);

		$document = new Apache_Solr_Document();

		$document->setField('appKey', 'Asm_Solr');
		$document->setField('type',   'cms/page');

		$document->setField('id',       $helper->getPageDocumentId($pageId));
		$document->setField('site',     $host);
		$document->setField('siteHash', $helper->getSiteHashForDomain($host));

		$document->setField('created', $helper->dateToIso($page->getCreationTime()));
		$document->setField('changed', $helper->dateToIso($page->getUpdateTime()));

		$document->setField('pageId',  $pageId);

		$document->setField('title',    $page->getTitle());
		$document->setField('content',  $page->getContent());
		$document->setField('keywords', $helper->trimExplode(',', $page->getMetaKeyword(), true));
		$document->setField('url',      Mage::helper('cms/page')->getPageUrl($pageId));

	}

}