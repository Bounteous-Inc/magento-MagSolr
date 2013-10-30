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

	/**
	 * Generates a document id for documents representing product records.
	 *
	 * @param integer $productId Product ID
	 * @return string The document id for that product
	 */
	public function getProductDocumentId($productId) {
		$baseUrl  = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
		$host     = parse_url($baseUrl, PHP_URL_HOST);
		$siteHash = $this->getSiteHashForDomain($host);

		$documentId = $siteHash . '/' . Mage_Catalog_Model_Product::ENTITY . '/' . $productId;

		return $documentId;
	}

	/**
	 * Gets the site hash for a domain
	 *
	 * @param string $domain Domain to calculate the site hash for.
	 * @return string site hash for $domain
	 */
	public function getSiteHashForDomain($domain) {
		$encryptionKey = Mage::getStoreConfig('global/crypt/key');

		$siteHash = sha1(
			$domain .
			$encryptionKey .
			'Asm_Solr'
		);

		return $siteHash;
	}

}
