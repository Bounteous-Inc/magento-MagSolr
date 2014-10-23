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


class Asm_Solr_Model_SynonymHandler
{

	public function __construct() {

	}

	public function managedSynonymsRestEndpointExists() {
		$connection = Mage::helper('solr/connectionManager')->getConnection();
		$resources = $connection->getManagedSchemaResources();

		return (in_array(
			'/' . $connection::SYNONYMS_SERVLET . $connection->getManagedLanguage(),
			$resources
		));
	}

	public function createManagedSynonymsRestEndpoint() {
		$connection = Mage::helper('solr/connectionManager')->getConnection();

		$response = $connection->addManagedSynonymResource();

		// todo return something
	}


	// TODO remove synonyms when deleting query in AdminHtml
	// TODO initial import of synonyms

	public function updateSynonyms($event)
	{
		$connection = Mage::helper('solr/connectionManager')->getConnection();

		$query = $event->getDataObject();
		/* @var $query Mage_CatalogSearch_Model_Query */

		$baseWord    = $query->getData('query_text');
		$oldSynonyms = $query->getOrigData('synonym_for');
		$newSynonyms = $query->getData('synonym_for');

		$solrSynonyms = $connection->getSynonyms($baseWord);
		if (!empty($solrSynonyms) && $newSynonyms != $oldSynonyms) {
			// since there's no update/edit, simply remove previous mapping
			// otherwise synonyms only get added, but never removed to/from a base word
			$connection->deleteSynonym($baseWord);
		}

		$newSynonyms = Mage::helper('solr')->trimExplode(',', $newSynonyms);
		$connection->addSynonym($baseWord, $newSynonyms);
	}


}
