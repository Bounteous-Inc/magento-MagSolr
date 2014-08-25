<?php
/**
 * Created by PhpStorm.
 * User: ian
 * Date: 8/15/14
 * Time: 11:44 AM
 */
class Asm_Solr_Block_Result_File extends Asm_Solr_Block_Result
{
    protected $_type        = 'file';
    protected $_solrType    = 'solr/indexqueue_file';

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
            return Mage::getModel('solr/indexqueue_file')->getCollection();

        if (!$this->getData('collection')) // if there isn't a file collection set...
        {
            /** @var Asm_Solr_Model_Resource_file_Collection $collection */
            $collection = Mage::getModel('solr/indexqueue_file')->getCollection();
            $collection->setSize($this->getResultCount());

            $resultDocuments = $this->getResultDocuments();

            $fileIds = array();

            $fieldToAttributeMap = Mage::helper('solr/schema')->getFieldToAttributeMap();

            foreach ($resultDocuments as $document)
            {
                $file = Mage::getModel('solr/indexqueue_file');

                $unmappedDocumentFields = $document->getFieldNames();

                // map "core/static" Solr document fields to Magento product attributes
                foreach ($fieldToAttributeMap as $field => $attribute) {
                    $file->setData($field, $document->{$field});

                    // remove fields that have been mapped, leaves dynamic fields
                    $fieldKey = array_search($field, $unmappedDocumentFields);
                    if ($fieldKey !== false) {
                        unset($unmappedDocumentFields[$fieldKey]);
                    }
                }

                // set up highlights...
                $highlight = $this->getResult()->getHighlighting($file->getData('id'));

                if ($highlight && property_exists($highlight,'content'))
                    $file->setHighlights($highlight->content);

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
                        $file->setData($attributeName, $document->{$dynamicField});
                    }
                }

                $fileIds[] = $document->fileId;

                $collection->addItem($file);
            }

            $collection->addFieldToFilter('file_id', array('in' => $fileIds));

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
