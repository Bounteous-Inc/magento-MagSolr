<?php
/**
 * Created by PhpStorm.
 * User: ian
 * Date: 8/28/14
 * Time: 12:30 PM
 */
abstract class Asm_Solr_Block_Abstract extends Mage_Core_Block_Template
{

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

    public function getOffset()
    {
        if (!$this->getData('offset'))
        {
            $limit = $this->getRequest()->getParam('offset', 0);

            $this->setData('offset', $limit);
        }

        return $this->getData('offset');
    }

    public function getLimit()
    {
        if (!$this->getData('limit'))
        {
            $limit = $this->getRequest()->getParam('limit', 25);

            $this->setData('limit', $limit);
        }

        return $this->getData('limit');
    }

    public function getKeywords()
    {
        if (!$this->getData('keywords'))
        {
            $keywords = $this->getRequest()->getParam('q', '');

            $this->setData('keywords', $keywords);
        }

        return $this->getData('keywords');
    }

    public function getKeywordsCleaned()
    {
        return Asm_Solr_Model_Solr_Query::cleanKeywords($this->getKeywords());
    }

}