<?php
/**
 * Created by PhpStorm.
 * User: ian
 * Date: 8/15/14
 * Time: 11:44 AM
 */
class Asm_Solr_Block_Result_Product extends Asm_Solr_Block_Result
{

    /**
     * Set available view mode
     *
     * @return Asm_Solr_Block_Result
     */
    public function setListModes()
    {
        $this->getListBlock()
            ->setModes(array(
                    'grid' => $this->__('Grid'),
                    'list' => $this->__('List'))
            );
        return $this;
    }

    public function getToolbar()
    {
        return $this->getListBlock()->getChild('product_list_toolbar');
    }

    public function getLimit()
    {
        return $this->getToolbar()->getLimit();
    }

    public function getOffset()
    {
        return $this->getLimit() * ($this->getToolbar()->getCurrentPage() - 1);
    }

    /**
     * @return Asm_Solr_Model_Result
     */
    public function getResult()
    {
        // if we don't have a result set for us, let's make one
        if (!$this->getData('result'))
        {
            /** @var Asm_Solr_Model_Result $result */
            $result = Mage::getModel('solr/result');
            $filteredQuery = $this->getFilteredQuery();

            $query = $result->getQuery();
            $query->setKeywords($this->getKeywords());
            $query->addFilter('type', $this->getSolrType());

            if($filteredQuery!='' or $filteredQuery!=null)
            {
                foreach(explode(',',$filteredQuery) as $fq)
                {
                    $query->addQueryParameter("fq", $fq);
                }
            }


            $result->load($this->getLimit(), $this->getOffset());

            $this->setData('result', $result);
        }

        return $this->getData('result');
    }

    public function getCollection()
    {
        // if there aren't any results, we should return nothing....
        if (!$this->getResultCount())
            return Mage::getModel('cms/product')->getCollection();

        if (!$this->getData('collection')) // if there isn't a page collection set...
        {
            /** @var Asm_Solr_Model_Resource_Page_Collection $collection */
            $collection = Mage::getModel('solr/product')->getCollection();
            $collection->setSize($this->getResultCount());

            $resultDocuments = $this->getResultDocuments();

            $productIds = array();

            $fieldToAttributeMap = Mage::helper('solr/schema')->getFieldToAttributeMap();
            // build a reverse map, remove fields that have no atrribute
            $fieldToAttributeMap = array_filter($fieldToAttributeMap);
            $attributeToFieldMap = array_flip($fieldToAttributeMap);

            foreach ($resultDocuments as $document)
            {
                $product = Mage::getModel('catalog/product');

                $unmappedDocumentFields = $document->getFieldNames();

                // map "core/static" Solr document fields to Magento product attributes
                foreach ($attributeToFieldMap as $attribute => $field) {
                    $product->setData($attribute, $document->{$field});

                    // remove fields that have been mapped, leaves dynamic fields
                    $fieldKey = array_search($field, $unmappedDocumentFields);
                    if ($fieldKey !== false) {
                        unset($unmappedDocumentFields[$fieldKey]);
                    }
                }

                $dynamicFields = array_diff(
                    $unmappedDocumentFields,
                    array_keys(Mage::helper('solr/schema')->getFieldToAttributeMap())
                );

                // map Solr dynamic fields to Magento product attributes
                $dynamicFieldSuffixes = Mage::helper('solr/schema')->getDynamicFieldSuffixes();
                foreach ($dynamicFields as $dynamicField) {
                    $fieldNameParts = explode('_', $dynamicField);

                    // do we have a valid dynamic field? If so, generate attribute name, map to Solr field
                    if (in_array($fieldNameParts[count($fieldNameParts) - 1], $dynamicFieldSuffixes)) {
                        array_pop($fieldNameParts);
                        $attributeName = implode('_', $fieldNameParts);
                        $product->setData($attributeName, $document->{$dynamicField});
                    }
                }

                $productIds[] = $document->productId;

                $collection->addItem($product);
            }

            $collection->addAttributeToFilter('entity_id', array('in' => $productIds));

            $this->setData('collection', $collection);
        }

        return $this->getData('collection');
    }

    public function getResultListHtml()
    {
        $listToolbar = $this->getListBlock()->getChild('product_list_toolbar');
        $limit  = $listToolbar->getLimit();
        $offset = $limit * ($listToolbar->getCurrentPage() - 1);

        $this->getResult()->load($limit, $offset);

        /** @var Mage_Catalog_Block_Product_List $listBlock */
        $listBlock = $this->getListBlock();
        $listBlock->setCollection($this->getCollection());

        return $this->getListBlock()->toHtml();
    }
}
