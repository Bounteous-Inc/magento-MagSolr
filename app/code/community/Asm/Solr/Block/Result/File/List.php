<?php
/**
 * Created by PhpStorm.
 * User: ian
 * Date: 8/15/14
 * Time: 11:44 AM
 */
class Asm_Solr_Block_Result_File_List extends Mage_Core_Block_Template
{

    public function getCollection()
    {
        if (!$this->getData('collection')) // if there isn't a page collection set...
        {
            $collection = Mage::getModel('solr/indexqueue_file')->getCollection(); // todo honestly this should not happen.

            $this->setData('collection', $collection);
        }

        return $this->getData('collection');
    }
}
