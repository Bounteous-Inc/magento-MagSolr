<?php
/**
 * Created by PhpStorm.
 * User: ian
 * Date: 8/15/14
 * Time: 11:44 AM
 */
class Asm_Solr_Block_Result_Page extends Asm_Solr_Block_Result
{
    protected $_type        = 'page';
    protected $_solrType    = 'cms/page';

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

            $query = $result->getQuery();
            $query->setKeywords($this->getKeywords());
            $query->addFilter('type', $this->getSolrType());
            $query->addFilter('storeId', $this->getStoreId());

            $query->addQueryParameter('hl.requireFieldMatch','true');
            $query->addQueryParameter('hl.simple.pre','<em>');
            $query->addQueryParameter('hl.simple.post','</em>');
            $query->addQueryParameter('hl.fl','content');
            $query->addQueryParameter('hl.usePhraseHighlighter','true');
            $query->addQueryParameter('hl','true');


            $result->load($this->getLimit(), $this->getOffset());

            $this->setData('result', $result);
        }

        return $this->getData('result');
    }

    public function getCollection()
    {
        // if there aren't any results, we should return nothing....
        if (!$this->getResultCount())
            return Mage::getModel('cms/page')->getCollection();

        if (!$this->getData('collection')) // if there isn't a page collection set...
        {
            /** @var Asm_Solr_Model_Resource_Page_Collection $collection */
            $collection = Mage::getModel('solr/page')->getCollection();
            $collection->setSize($this->getResultCount());

            $resultDocuments = $this->getResultDocuments();

            $pageIds = array();

            $fieldToAttributeMap = Mage::helper('solr/schema')->getFieldToAttributeMap();

            foreach ($resultDocuments as $document)
            {
                $page = Mage::getModel('cms/page');

                $unmappedDocumentFields = $document->getFieldNames();

                // map "core/static" Solr document fields to Magento product attributes
                foreach ($fieldToAttributeMap as $field => $attribute) {
                    $page->setData($field, $document->{$field});

                    // remove fields that have been mapped, leaves dynamic fields
                    $fieldKey = array_search($field, $unmappedDocumentFields);
                    if ($fieldKey !== false) {
                        unset($unmappedDocumentFields[$fieldKey]);
                    }
                }

                // set up highlights...
                $highlight = $this->getResult()->getHighlighting($page->getData('id'));

                if ($highlight && property_exists($highlight,'content'))
                    $page->setHighlights($highlight->content);

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
                        $page->setData($attributeName, $document->{$dynamicField});
                    }
                }

                $pageIds[] = $document->pageId;

                $collection->addItem($page);
            }

            $collection->addFieldToFilter('entity_id', array('in' => $pageIds));

            $this->setData('collection', $collection);
        }

        return $this->getData('collection');
    }

    public function getResultListHtml()
    {
        /** @var Mage_Core_Block_Template $list */
        $list = $this->getChild('result_list');

        if (!$list)
            return '';

        $list->setCollection($this->getCollection());

        return $list->toHtml();
    }
}
