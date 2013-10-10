<?php

/**
 * Solr data helper
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

	public function getConnectionByStore($storeId)
	{

	}



}
