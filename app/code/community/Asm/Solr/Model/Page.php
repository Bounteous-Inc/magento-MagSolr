<?php

class Asm_Solr_Model_Page extends Mage_Cms_Model_Page
{

	/**
	 * Initialize resources
	 */
	protected function _construct()
	{
		$this->_init('cms/page');

		// setting the resource collection to our own
		// otherwise it is the same as _init(). However, we're still calling _init()
		// in case the implementation higher up in the hierarchy changes
		$this->_setResourceModel('cms/page', 'solr/page_collection');
	}
}

?>