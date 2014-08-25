<?php
/**
 * Created by PhpStorm.
 * User: ian
 * Date: 8/15/14
 * Time: 11:44 AM
 */
class Asm_Solr_Block_Result_Page_List extends Mage_Core_Block_Template
{
    protected $_type        = 'page';
    protected $_solrType    = 'cms/page';

    public function getCollection()
    {
        if (!$this->getData('collection')) // if there isn't a page collection set...
        {
            $collection = Mage::getModel('solr/page')->getCollection(); // todo honestly this should not happen.

            $this->setData('collection', $collection);
        }

        return $this->getData('collection');
    }
}
