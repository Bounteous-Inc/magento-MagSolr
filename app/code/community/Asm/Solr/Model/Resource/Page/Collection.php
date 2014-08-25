<?php

class Asm_Solr_Model_Resource_Page_Collection extends Mage_Cms_Model_Resource_Page_Collection
{

	/**
	 * Overwrites the _isCollectionLoaded, setting it to true to prevent it
	 * from loading.
	 *
	 * @param string $model
	 * @param unknown_type $entityModel
	 * @return Mage_Catalog_Model_Resource_Product_Collection
	 */
	protected function _init($model, $entityModel = null)
	{
		$this->_isCollectionLoaded = true;

		return parent::_init($model, $entityModel);
	}

	/**
	 * @param integer $size Collection size / total number of results found
	 */
	public function setSize($size)
	{
		$this->_totalRecords = intval($size);
	}

}

?>