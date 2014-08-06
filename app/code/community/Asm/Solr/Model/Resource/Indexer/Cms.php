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
 * An indexer for Magento CMS pages
 *
 * @category Asm
 * @package Asm_Solr
 * @author Ingo Renner <ingo@infielddesign.com>
 */
class Asm_Solr_Model_Resource_Indexer_Cms extends Mage_Core_Model_Resource_Db_Abstract
{
	protected $excludedPages = array('no-route', 'enable-cookies');


	/**
	 * Resource initialization
	 */
	protected function _construct() {
		$this->_setResource('core');
	}

	/**
	 * Event handler for CMS page save events
	 *
	 * @param Mage_Index_Model_Event $event
	 */
	public function CmsPageSave(Mage_Index_Model_Event $event) {
		$storeIds = $event->getDataObject()->getStores();
		$pageId   = $event->getData('solr_update_page_id');

		foreach ($storeIds as $storeId) {
			$this->rebuildStoreIndex($storeId, $pageId);
		}
	}

	/**
	 * Rebuild the index for all stores at once or just one specific store.
	 *
	 * @param int|null $storeId Store to re-index single int store ID to re-index single store, null to re-index all stores
	 * @param int|array|null $pageIds Page to re-index, single int page ID to re-index a single page, array of page IDs to re-index multiple pages, null to re-index all pages
	 */
	public function rebuildIndex($storeId = null, $pageIds = null)
	{
		if (is_null($storeId)) {
			// re-index all stores
			$storeIds = array_keys(Mage::app()->getStores());
			foreach ($storeIds as $storeId) {
				$this->rebuildStoreIndex($storeId, $pageIds);
			}
		} else {
			// re-index specific store
			$this->rebuildStoreIndex($storeId, $pageIds);
		}
	}

	public function rebuildStoreIndex($storeId, $pageIds = null)
	{
		$pages = $this->getSearchablePagesByStore($storeId, $pageIds);
		$solr = Mage::helper('solr/connectionManager')->getConnectionByStore($storeId);

		foreach ($pages as $page) {
			$document = $this->buildPageDocument($storeId, $page);
			$solr->addDocument($document);

			Mage::dispatchEvent('solr_index_page_after', array(
				'store_id'      => $storeId,
				'page'          => $page,
				'page_document' => $document
			));
		}
	}

	protected function getSearchablePagesByStore($storeId, $pageIds = null)
	{
		$collection = Mage::getModel('cms/page')->getCollection()
			->addStoreFilter($storeId)
			->addFieldToFilter('is_active', 1)
			->addFieldToFilter('identifier', array(array('nin' => $this->excludedPages)));

		if (!is_null($pageIds)) {
			$collection->addFieldToFilter('page_id', array(array('in' => $pageIds)));
		}

		$collection->load();

		return $collection;
	}

	/**
	 * Build a Solr document for a given page
	 *
	 * @param integer $storeId Store ID
	 * @param Mage_Cms_Model_Page $page Page instance
	 * @return Apache_Solr_Document
	 */
	protected function buildPageDocument($storeId, $page)
	{
		$helper  = Mage::helper('solr');
		$baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
		$host    = parse_url($baseUrl, PHP_URL_HOST);

		$document = new Apache_Solr_Document();

		$document->setField('appKey', 'Asm_Solr');
		$document->setField('type',   'cms/page');

		$document->setField('id',       $helper->getPageDocumentId($page->getId()));
		$document->setField('site',     $host);
		$document->setField('siteHash', $helper->getSiteHashForDomain($host));
		$document->setField('storeId',  $storeId);

		$document->setField('created',  $helper->dateToIso($page->getCreationTime()));
		$document->setField('changed',  $helper->dateToIso($page->getUpdateTime()));

		$document->setField('sku',       'cms/page');
		$document->setField('productId', 0);
		$document->setField('pageId',    $page->getId());

		$document->setField('title',    $page->getTitle());
		$document->setField('content',  Mage::helper('solr/contentExtractor')->getIndexableContent($page->getContent()));
		$document->setField('keywords', $helper->trimExplode(',', $page->getMetaKeywords(), true));
		$document->setField('url',      Mage::helper('cms/page')->getPageUrl($page->getId()));

		return $document;
	}

}
