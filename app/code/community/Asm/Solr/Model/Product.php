<?php

class Asm_Solr_Model_Product extends Mage_Catalog_Model_Product
{

	/**
	 * Initialize resources
	 */
	protected function _construct()
	{
		$this->_init('catalog/product');

		// setting the resource collection to our own
		// otherwise the same as _init(). However, we're still calling _init()
		// in case the implementation higher up in the hierarchy changes
		$this->_setResourceModel('catalog/product', 'solr/product_collection');
	}
}

?>