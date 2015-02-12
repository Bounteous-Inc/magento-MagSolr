<?php
/**
 *
 * @category    Asm
 * @package     Asm_Solr
 * @copyright   Copyright (c) 2014 Infield Design
 */

/**
 * Solr result model
 *
 * @category    Asm
 * @package     Asm_Solr
 * @author      Infield Design
 *
 * @method Asm_Solr_Model_Result setKeywords(string $keywords)
 * @method string getKeywords()
 * @method Asm_Solr_Model_Result setCount(int $count)
 * @method int getCount()
 * @method Asm_Solr_Model_Solr_Response getResponse()
 * @method Asm_Solr_Model_Result setDocuments(array $documents)
 * @method array getDocuments()
 */
class Asm_Solr_Model_Result extends Mage_Core_Model_Abstract
{
    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'solr_result';

    /**
     * Event object key name
     *
     * @var string
     */
    protected $_eventObject = 'solr_result';

    const CACHE_TAG                     = 'SEARCH_QUERY';
    const XML_PATH_MIN_QUERY_LENGTH     = 'catalog/search/min_query_length';
    const XML_PATH_MAX_QUERY_LENGTH     = 'catalog/search/max_query_length';
    const XML_PATH_MAX_QUERY_WORDS      = 'catalog/search/max_query_words';

    const DOCUMENT_TYPE_PRODUCT         = 'catalog/product';
    const DOCUMENT_TYPE_PAGE            = 'cms/page';
    const DOCUMENT_TYPE_FILE            = 'solr/indexqueue_file';

    private $_results   = array();


    /**
     * Init resource model
     *
     */
    protected function _construct()
    {
        $this->_init('solr/query');

        $this->_results[self::DOCUMENT_TYPE_PRODUCT]    = array('count' => 0, 'documents' => array());
        $this->_results[self::DOCUMENT_TYPE_PAGE]       = array('count' => 0, 'documents' => array());
        $this->_results[self::DOCUMENT_TYPE_FILE]       = array('count' => 0, 'documents' => array());
    }

    /**
     * @return Asm_Solr_Model_Solr_Query
     */
    public function getQuery()
    {
        if (!$this->getData('query'))
        {
            $query = Mage::getModel('solr/solr_query');

            $this->setData('query', $query);
        }

        return $this->getData('query');
    }

    public function getKeywordsCleaned()
    {
        return Asm_Solr_Model_Solr_Query::cleanKeywords($this->getKeywords());
    }

    /**
     * Set Store Id
     *
     * @param int $storeId
     * @return Mage_CatalogSearch_Model_Query
     */
    public function setStoreId($storeId)
    {
        $this->setData('store_id', $storeId);
    }

    /**
     * Retrieve store Id
     *
     * @return int
     */
    public function getStoreId()
    {
        if (!$storeId = $this->getData('store_id')) {
            $storeId = Mage::app()->getStore()->getId();
        }

        return $storeId;
    }


    /**
     * Retrieve minimum query length
     *
     * @deprecated after 1.3.2.3 use getMinQueryLength() instead
     * @return int
     */
    public function getMinQueryLength()
    {
        return Mage::getStoreConfig(self::XML_PATH_MIN_QUERY_LENGTH, $this->getStoreId());
    }

    /**
     * Retrieve maximum query length
     *
     * @return int
     */
    public function getMaxQueryLength()
    {
        return Mage::getStoreConfig(self::XML_PATH_MAX_QUERY_LENGTH, $this->getStoreId());
    }

    /**
     * Retrieve maximum query words for like search
     * todo is this useful
     * @return int
     */
    public function getMaxQueryWords()
    {
        return Mage::getStoreConfig(self::XML_PATH_MAX_QUERY_WORDS, $this->getStoreId());
    }

    public function setResultTypeCount($type = '', $count = 0)
    {
        if (!$type)
        {
            $type = self::DOCUMENT_TYPE_PRODUCT;
        }

        $this->_results[$type]['count'] = $count;

        return $this;
    }

    public function load($limit = 0, $offset = 0)
    {
        $query = $this->getQuery();
        $query->setQueryFieldsFromString($this->getWeightedQueryFields());

        $search   = Mage::getResourceModel('solr/search');
        $response = $search->search($query, $offset, $limit);
        $this->setResponse($response);

        // process response...
        $this->setCount($response->getNumberOfResults());
        $this->setDocuments($response->getDocuments());
        $this->setHighlighting((array) $response->getHighlighting());

        return $this;
    }

    public function getHighlighting($id = '')
    {
        $highlighting = $this->getData('highlighting');

        if ($id == '')
        {
            return $highlighting;
        }

        if ($highlighting && is_array($highlighting) && array_key_exists($id, $highlighting))
        {
            return $highlighting[$id];
        }

        return array();
    }

    /**
     * Gets the searchable product attributes and their weights/boosts
     *
     * @return string query fields with their boosts
     */
    protected function getWeightedQueryFields()
    {
        $weightedFields = array();

        $attributes = Mage::helper('solr/attribute')->getSearchableAttributes();
        foreach ($attributes as $attribute) {
            /** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
            $attributeCode = $attribute->getAttributeCode();
            $fieldName = Mage::helper('solr/schema')->getFieldNameByAttribute($attributeCode);

            $weight = floatval($attribute->getSearchWeight()) ?: 1.0;
            $formattedWeight = number_format($weight, 1, '.', '');

            $weightedFields[] = $fieldName . '^' . $formattedWeight;
        }

        return implode(', ', $weightedFields);
    }
}
