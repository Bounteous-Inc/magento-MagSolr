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
	 * Gets the current Solr query
	 *
	 * @return Asm_Solr_Model_Solr_Query
	 */
	public function getQuery()
	{
		return Mage::getSingleton('solr/solr_query');
	}

	/**
	 * Gets the current query's result or null if the query has not been
	 * executed yet.
	 *
	 * @return Asm_Solr_Model_Solr_Response|null
	 */
	public function getResponse() {
		return Mage::registry('solr/response');
	}

		/**
	 * Generates the result page URL
	 *
	 * @param string $keywords
	 * @return string
	 */
	public function getResultUrl($keywords = null)
	{
		return $this->_getUrl('search/result', array(
			'_query' => array(Asm_Solr_Model_Solr_Query::QUERY_PARAMETER_NAME => $keywords),
			'_secure' => Mage::app()->getFrontController()->getRequest()->isSecure()
		));
	}

	/**
	 * Generates the suggest URL
	 *
	 * @return string
	 */
	public function getSuggestUrl()
	{
		return $this->_getUrl('search/suggest', array(
			'_secure' => Mage::app()->getFrontController()->getRequest()->isSecure()
		));
	}

	/**
	 * Generates a document id for documents representing product records.
	 *
	 * @param integer $productId Product ID
	 * @return string The document id for that product
	 */
	public function getProductDocumentId($productId)
	{
		$baseUrl  = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
		$host     = parse_url($baseUrl, PHP_URL_HOST);
		$siteHash = $this->getSiteHashForDomain($host);

		$documentId = $siteHash . '/' . Mage_Catalog_Model_Product::ENTITY . '/' . $productId;

		return $documentId;
	}

	/**
	 * Generates a document id for documents representing files.
	 *
	 * @param integer $fileId File ID
	 * @return string The document ID for that file
	 */
	public function getFileDocumentId($fileId)
	{
		$baseUrl  = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
		$host     = parse_url($baseUrl, PHP_URL_HOST);
		$siteHash = $this->getSiteHashForDomain($host);

		$documentId = $siteHash . '/' . Asm_Solr_Model_Indexqueue_File::ENTITY . '/' . $fileId;

		return $documentId;
	}

	/**
	 * Generates a document id for documents representing CMS pages.
	 *
	 * @param integer $pageId Page ID
	 * @return string The document id for that page
	 */
	public function getPageDocumentId($pageId)
	{
		$baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
		$host = parse_url($baseUrl, PHP_URL_HOST);
		$siteHash = $this->getSiteHashForDomain($host);

		$documentId = $siteHash . '/' . Asm_Solr_Model_Cms_Page::ENTITY . '/' . $pageId;

		return $documentId;
	}

	/**
	 * Gets the site hash for a domain
	 *
	 * @param string $domain Domain to calculate the site hash for.
	 * @return string site hash for $domain
	 */
	public function getSiteHashForDomain($domain)
	{
		$encryptionKey = Mage::getStoreConfig('global/crypt/key');

		$siteHash = sha1(
			$domain .
			$encryptionKey .
			'Asm_Solr'
		);

		return $siteHash;
	}

	/**
	 * Gets an instance of the logger
	 *
	 * @return Asm_Solr_Helper_Logger
	 */
	public function getLogger()
	{
		return Mage::helper('solr/logger');
	}

	/**
	 * Takes a Magento date string or Zend_Date and turns it into an
	 * ISO 8601 compliant formatted string of the date.
	 *
	 * @param string|integer|Zend_Date $date Magento date string, Unix timestamp, or Zend_Date object
	 * @return string ISO date (using Z instead of +00:00)
	 */
	public function dateToIso($date)
	{
		if (is_string($date)) {
			$date = new Zend_Date($date);
		}

		if (is_int($date)) {
			$date = new Zend_Date($date, Zend_Date::TIMESTAMP);
		}

		$date = strstr($date->getIso(), '+', true); //strip timezone
                $date .= 'Z';

		return $date;
	}

	/**
	 * Explodes a string and trims all values for whitespace in the ends.
	 * If $onlyNonEmptyValues is set, then all blank ('') values are removed.
	 *
	 * @param string $delimiter Delimiter string to explode with
	 * @param string $string The string to explode
	 * @param boolean $removeEmptyValues If set, all empty values will be removed from output
	 * @return array Exploded values
	 */
	public function trimExplode($delimiter, $string, $removeEmptyValues = false)
	{
		$explodedValues = explode($delimiter, $string);
		$result = array_map('trim', $explodedValues);

		if ($removeEmptyValues) {
			$temp = array();
			foreach ($result as $value) {
				if ($value !== '') {
					$temp[] = $value;
				}
			}
			$result = $temp;
		}

		return $result;
	}

}
