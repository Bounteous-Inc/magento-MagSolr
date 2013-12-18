<?php

/**
 * Solr connection manager.
 *
 * Can retrieve a Solr connection for different contexts.
 *
 * @category Asm
 * @package Asm_Solr
 * @author Ingo Renner <ingo@infielddesign.com>
 */
class Asm_Solr_Helper_ConnectionManager
{

	/**
	 * Gets the default connection / connection for the current store
	 *
	 * @return Asm_Solr_Model_Solr_Connection
	 */
	public function getConnection()
	{
		// get current store Solr configuration
		$solrConnectionParameters = Mage::getStoreConfig('solr/connection');
		$connection = Mage::getModel('solr/solr_connection', $solrConnectionParameters);

		return $connection;
	}

	/**
	 * Gets the Solr connection for a specific store
	 *
	 * @param string|int $store Store code or store ID
	 * @return Asm_Solr_Model_Solr_Connection
	 */
	public function getConnectionByStore($store)
	{
		$storeCode = $store;

		if (is_numeric($store)) {
			$storeCode = Mage::getModel('core/store')
				->getCollection()
				->addIdFilter($store)
				->getFirstItem()
				->getCode();
		}

		$solrConnectionParameters = Mage::getConfig()->getNode('solr/connection', 'stores', $storeCode);
		$connection = Mage::getModel('solr/solr_connection', $solrConnectionParameters);

		return $connection;
	}

	/**
	 * Gets the Solr connections for all stores of a website.
	 *
	 * @param int|string $site Website code or ID
	 * @return Asm_Solr_Model_Solr_Connection[] Array of Asm_Solr_Model_Solr_Connection objects
	 */
	public function getConnectionsBySite($site)
	{
		$connections = array();

		if (is_string($site) && !is_numeric($site)) {
			// website code
			$collection = Mage::getModel('core/website')->getCollection()->addFieldToFilter('code', $site);
			$website    = $collection->getFirstItem();
		} else {
			// website id
			$website = Mage::getModel('core/website')->getCollection()->addIdFilter($site)->getFirstItem();
		}

		$stores = $website->getStoreCollection();
		foreach ($stores as $store) {
			/** @var Mage_Core_Model_Store $store */
			$storeConnection = $this->getConnectionByStore($store->getId());
			$connections[] = $storeConnection;
		}

		return $connections;
	}

	/**
	 * Gets all connections for all websites and their stores
	 *
	 * @return Asm_Solr_Model_Solr_Connection[]
	 */
	public function getAllConnections()
	{
		$connections = array();
		$websites = Mage::getModel('core/website')->getCollection()->addFieldToFilter('website_id', array("neq" => '0'));

		foreach ($websites as $website) {
			/** @var Mage_Core_Model_Website $website */
			$id = $website->getId();
			$websiteConnections = $this->getConnectionsBySite($website->getId());
			$connections = array_merge($connections, $websiteConnections);
		}

		return $connections;
	}

}

?>