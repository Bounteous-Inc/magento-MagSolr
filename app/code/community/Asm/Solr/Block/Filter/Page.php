<?php
/**
 * Created by PhpStorm.
 * User: ian
 * Date: 8/28/14
 * Time: 12:30 PM
 */
class Asm_Solr_Block_Filter_Page extends Asm_Solr_Block_Filter
{
    protected $_type        = 'product'; // default result type is product search
    protected $_solrType    = 'cms/page';

    public function getType()
    {
        return $this->_type;
    }

    public function getSolrType()
    {
        return $this->_solrType;
    }

    public function getTitle(){

        $title = 'Pages';

        return $title;
    }

    public function getResultCount()
    {
        return $this->getResult()->getCount();
    }

    /**
     * @return Asm_Solr_Model_Result
     */
    public function getResult()
    {
        // if we don't have a result set for us, let's make one
        if (!$this->getData('result')) // todo should we be running like 30 searches?
        {
            /** @var Asm_Solr_Model_Result $result */
            $result = Mage::getModel('solr/result');

            $query = $result->getQuery();
            $query->setKeywords($this->getKeywords());
            $query->addFilter('type', $this->getSolrType());

            $result->load($this->getLimit(), $this->getOffset()); // todo is this even useful?

            $this->setData('result', $result);
        }

        return $this->getData('result');
    }


}