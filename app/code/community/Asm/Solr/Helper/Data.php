<?php

/**
 * Solr data helper
 *
 * @category Asm
 * @package Asm_Solr
 * @author Ingo Renner <ingo@infielddesign.com>
 */
class Asm_Solr_Helper_Data extends Mage_Core_Helper_Abstract
{

	public function getProductDocumentId($productId) {
		// TODO replace ### with site hash
		$documentId = '###/' . Mage_Catalog_Model_Product::ENTITY . '/' . $productId;

		return $documentId;
	}

}
