<?php

class Asm_Solr_Model_GarbageCollector
{
	/**
	 * Listens for event
	 *
	 * @param Varien_Event_Observer $event
	 */
	public function deleteProduct($event)
	{
		$productId = $event->getProduct()->getEntityId();
		$productDocumentId = Mage::helper('solr')->getProductDocumentId($productId);

		$this->deleteIndexDocument($productDocumentId);
	}

	protected function deleteIndexDocument($documentId)
	{
		$connection = Mage::helper('solr/connectionManager')->getConnection();
		/** @var $connection Asm_Solr_Model_Solr_Connection */

		$connection->deleteById($documentId);
		$connection->commit();
	}
}