<?php

class Asm_Solr_Model_GarbageCollector
{
	/**
	 * Listens for event catalog_product_delete_after
	 *
	 * @param Varien_Event_Observer $event
	 */
	public function deleteProduct($event)
	{
		$productId = $event->getProduct()->getEntityId();
		$productDocumentId = Mage::helper('solr')->getProductDocumentId($productId);

		$this->deleteIndexDocument($productDocumentId);
	}

	/**
	 * Listens for event indexqueue_file_delete_after
	 *
	 * @param Varien_Event_Observer $event
	 */
	public function deleteFile($event)
	{
		$fileId = $event->getIndexqueueFile()->getEntityId();
		$fileDocumentId = Mage::helper('solr')->getFileDocumentId($fileId);

		$this->deleteIndexDocument($fileDocumentId);
	}

	protected function deleteIndexDocument($documentId)
	{
		$connection = Mage::helper('solr/connectionManager')->getConnection();
		/** @var $connection Asm_Solr_Model_Solr_Connection */

		$connection->deleteById($documentId);
		$connection->commit();
	}
}