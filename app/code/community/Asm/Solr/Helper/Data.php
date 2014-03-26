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
	 * A map of  Solr document fields to Magento product attributes.
	 *
	 * Contains all fixed indexed fields/attributes, other product attributes
	 * are added to Solr documents using dynamic fields.
	 *
	 * @var array
	 */
	protected static $fieldToAttributeMap = array(
		'appKey'             => '',
		'type'               => '',
		'id'                 => '',
		'site'               => '',
		'siteHash'           => '',
		'created'            => 'created_at',
		'changed'            => 'changed_at',
		'sku'                => 'sku',
		'productId'          => 'entity_id',
		'storeId'            => '',
		'categoryId'         => 'category_ids',
		'inStock'            => '',
		'isSalable'          => 'is_salable',
		'isVisible'          => 'status',
		'isVisibleInCatalog' => '',
		'title'              => 'name',
		'content'            => 'description',
		'keywords'           => 'meta_keywords',
		'url'                => '',
		'price'              => 'price',
		'manufacturer'       => 'manufacturer',
		'priceCurrency'      => '',
		'indexed'            => '',
		'_version_'          => '',
		'score'              => ''
	);

	/**
	 * A list of dynamic field suffixes. There are more dynamic field types,
	 * but these are the ones that are currently used.
	 *
	 * Used to automatically map dynamic fields back to product attributes.
	 *
	 * @var array
	 */
	protected static $dynamicFieldSuffixes = array(
		'dateS',
		'doubleS',
		'intS',
		'stringS',
		'textS'
	);

	/**
	 * Gets a map of Solr document fields to Magento product attributes.
	 *
	 * @return array
	 */
	public function getFieldToAttributeMap()
	{
		return self::$fieldToAttributeMap;
	}

	/**
	 * Gets a list of Solr dynamic field suffixes
	 *
	 * @return array
	 */
	public function getDynamicFieldSuffixes()
	{
		return self::$dynamicFieldSuffixes;
	}

	/**
	 * Generates a (dynamic) field name for a given attribute
	 *
	 * @param string|Mage_Eav_Model_Entity_Attribute_Abstract $attribute Maybe an attribute object or an attribute code
	 * @param bool $multiValue If true, generates a multi value field name, single value if false/by default
	 * @return bool|string Field name or false if the attribute's backend type cannot be matched to a Solr field type
	 * @throws UnexpectedValueException if $attribute is not an attribute code or an attribute instance
	 */
	public function getFieldNameByAttribute($attribute, $multiValue = false)
	{
		$fieldName = false;

		$countFieldType = 'S'; // single value
		if ($multiValue) {
			$countFieldType = 'M';
		}

		if (is_string($attribute)) {
			// turn attribute code into attribute instance
			$attribute = Mage::getSingleton('eav/config')
				->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attribute);
		}

		if (!($attribute instanceof Mage_Eav_Model_Entity_Attribute_Abstract)) {
			throw new UnexpectedValueException(
				'$attribute must either be an attribute code or an instance of Mage_Eav_Model_Entity_Attribute_Abstract',
				1395360135
			);
		}

		$attributeCode = $attribute->getAttributeCode();

		switch ($attribute->getBackendType()) {
			case 'datetime':
				$fieldName = $attributeCode . '_date' . $countFieldType;
				break;
			case 'decimal':
				$fieldName = $attributeCode . '_double' . $countFieldType;
				break;
			case 'int':
				$fieldName = $attributeCode . '_int' . $countFieldType;
				break;
			case 'text':
			case 'varchar':
				// TODO there might be cases when you want a string instead,
				// might need a configuration option
				$fieldName = $attributeCode . '_text' . $countFieldType;
				break;
		}

		return $fieldName;
	}


	/**
	 * Gets the current Solr query
	 *
	 * @return Asm_Solr_Model_Solr_Query
	 */
	public function getQuery()
	{
		$registryKey = 'solr/query';
		if (!Mage::registry($registryKey)) {
			Mage::register($registryKey, Mage::getModel('solr/solr_query'));
		}

		return Mage::registry($registryKey);
	}

	/**
	 * Gets the current query's result or null if the query has not been
	 * executed yet.
	 *
	 * @return Asm_Solr_Model_Solr_Response|null
	 */
	public function getResponse()
	{
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
	 * @param string|Zend_Date $date Magento date string
	 * @return string ISO date (using Z instead of +00:00)
	 */
	public function dateToIso($date)
	{
		if (is_string($date)) {
			$date = new Zend_Date($date);
		}

		return str_replace('+00:00', 'Z', $date->getIso());
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
