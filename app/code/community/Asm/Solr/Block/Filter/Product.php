<?php
/**
 * Created by PhpStorm.
 * User: ian
 * Date: 8/28/14
 * Time: 12:30 PM
 */
class Asm_Solr_Block_Filter_Product extends Asm_Solr_Block_Filter
{
    protected $_type        = 'product';
    protected $_solrType    = 'catalog/product';

    public function getResultLink()
    {
        echo $this->getUrl('*/*/'.$this->getType(), array('_query' => $this->getRequest()->getParams()));
    }

    public function getType()
    {
        return $this->_type;
    }

    public function getSolrType()
    {
        return $this->_solrType;
    }

    public function getTitle(){
        $title = 'Products';

        return $title;
    }

}