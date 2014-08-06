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
 * File indexer resource
 *
 * @category Asm
 * @package Asm_Solr
 * @author Ingo Renner <ingo@infielddesign.com>
 */
class Asm_Solr_Model_Resource_Indexer_File extends Mage_Core_Model_Resource_Db_Abstract
{

	/**
	 * Resource initialization
	 */
	protected function _construct() {
		$this->_setResource('core');
	}

	/**
	 * Rebuild the index for all stores at once or just one specific store.
	 *
	 * @param int|null $storeId Store to re-index single int store ID to re-index single store, null to re-index all stores
	 */
	public function rebuildIndex($storeId = null)
	{
		if (is_null($storeId)) {
			// re-index all stores
			$storeIds = array_keys(Mage::app()->getStores());
			foreach ($storeIds as $storeId) {
				$this->rebuildStoreIndex($storeId);
			}
		} else {
			// re-index specific store
			$this->rebuildStoreIndex($storeId);
		}
	}

	public function rebuildStoreIndex($storeId)
	{
		$files = $this->getFilesByStore($storeId);
		$solr = Mage::helper('solr/connectionManager')->getConnectionByStore($storeId);

		foreach ($files as $file) {
			$document = $this->buildFileDocument($storeId, $file);
			$solr->addDocument($document);
		}
	}


	protected function getFilesByStore($storeId)
	{
		$collection = Mage::getModel('solr/indexqueue_file')->getCollection()
			->addFilter('store_id', $storeId)
			->load();

		return $collection;
	}

	/**
	 * Build a Solr document for a specific file
	 *
	 * @param integer $storeId Store ID the file belongs to/where it is linked on a page
	 * @param Asm_Solr_Model_Indexqueue_File $file The file to index
	 * @return Apache_Solr_Document
	 */
	protected function buildFileDocument($storeId, Asm_Solr_Model_Indexqueue_File $file)
	{
		$helper  = Mage::helper('solr');
		$baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
		$host    = parse_url($baseUrl, PHP_URL_HOST);

		$document = new Apache_Solr_Document();

		$document->setField('appKey',    'Asm_Solr');
		$document->setField('type',      'solr/indexqueue_file');

		$document->setField('id',        $helper->getFileDocumentId($file->getId()));
		$document->setField('site',      $host);
		$document->setField('siteHash',  $helper->getSiteHashForDomain($host));
		$document->setField('storeId',   $storeId);
		$document->setField('changed',   $helper->dateToIso($file->getFileLastChangedTime()));

		$document->setField('productId', 0);
		$document->setField('sku',       'solr/indexqueue_file');

		$document->setField('title',     $file->getName());
		$document->setField('content',   $file->getContent());
		$document->setField('url',       $file->getUrl());

		return $document;
	}


}