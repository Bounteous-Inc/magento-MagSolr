<?php



class Asm_Solr_Adminhtml_Solr_System_Config_UtilityController extends Mage_Adminhtml_Controller_Action
{

	/**
	 * Action to empty indexes depending on the current scope
	 *
	 */
	public function emptyindexAction()
	{
		$result = true;

		$section = $this->getRequest()->getParam('section');
		$website = $this->getRequest()->getParam('website');
		$store   = $this->getRequest()->getParam('store');

		$logger = Mage::helper('solr')->getLogger();
		$logger->debug("section: $section, website: $website, store: $store");

		if (!empty($section) && empty($website) && empty($store)) {
			// empty ALL indexes

			$connections = Mage::helper('solr/connectionManager')->getAllConnections();
			foreach ($connections as $connection) {
				/** @var $connection Asm_Solr_Model_Solr_Connection */
				$connection->deleteByQuery('*:*');
				$result = ($connection->commit()->getHttpStatus() == 200);

				if ($result) {
					$logger->info('Empty index', array('core' => $connection->getPath()));
				} else {
					$logger->error('Empty index', array('core' => $connection->getPath()));
					break;
				}
			}
		} elseif (!empty($section) && !empty($website) && empty($store)) {
			// empty a website's stores' indexes

			$connections = Mage::helper('solr/connectionManager')->getConnectionsBySite($website);
			foreach ($connections as $connection) {
				/** @var $connection Asm_Solr_Model_Solr_Connection */
				$connection->deleteByQuery('*:*');
				$result = ($connection->commit()->getHttpStatus() == 200);

				if ($result) {
					$logger->info('Empty index', array('core' => $connection->getPath()));
				} else {
					$logger->error('Empty index', array('core' => $connection->getPath()));
					break;
				}
			}
		} else {
			// empty a specific store's index

			$connection = Mage::helper('solr/connectionManager')->getConnectionByStore($store);
			$connection->deleteByQuery('*:*');
			$result = ($connection->commit()->getHttpStatus() == 200);

			if ($result) {
				$logger->info('Empty index', array('core' => $connection->getPath()));
			} else {
				$logger->error('Empty index', array('core' => $connection->getPath()));
			}
		}

		echo $result ? 1 : 0;
	}

	public function syncsynonymsAction() {
		$result = true;

		// FIXME does not support per store synonyms yet, syncing for all
		$connections = Mage::helper('solr/connectionManager')->getAllConnections();
		$connection = $connections[0];

		$synonymHandler = Mage::getModel('solr/synonymHandler');

		// remove existing synonyms
		$oldSynonyms = $connection->getSynonyms();
		$oldSynonyms = (array) $oldSynonyms;
		$oldSynonyms = array_keys($oldSynonyms);
		foreach ($oldSynonyms as $synonym) {
			$connection->deleteSynonym($synonym);
		}

		// write new synonyms
		$queries = Mage::getModel('catalogsearch/query')
			->getCollection()
			->addFieldToFilter('synonym_for', array("notnull"=>true));

		foreach ($queries as $query) {
			$baseWord = $query->getData('query_text');
			$synonyms = $query->getData('synonym_for');

			if (!empty($synonyms)) {
				$synonyms = trim($synonyms, " \"\n\r\t");
				$newSynonyms = Mage::helper('solr')->trimExplode(',', $synonyms);
				$connection->addSynonym($baseWord, $newSynonyms);
			}
		}

		echo $result ? 1 : 0;
	}

	/**
	 * Action to test the configured connection (ping) depending on the current scope
	 *
	 */
	public function testconnectionAction()
	{
		$store      = $this->getRequest()->getParam('store');
		$connection = Mage::helper('solr/connectionManager')->getConnectionByStore($store);

		echo $connection->ping() ? 1 : 0;
	}

	public function playgroundAction()
	{
#		var_dump(Mage::getConfig()->getNode('solr/connection', 'stores', 'german'));


		var_dump(Mage::helper('solr/connectionManager')->getConnectionByStore('german'));
	}

}


?>