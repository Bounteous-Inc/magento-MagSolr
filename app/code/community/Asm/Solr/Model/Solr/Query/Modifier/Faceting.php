<?php

class Asm_Solr_Model_Solr_Query_Modifier_Faceting
{

	/**
	 * Called before a query is executed. Modifies the query to add faceting
	 * parameters.
	 *
	 * @param Varien_Event_Observer $observer Observer/event data
	 */
	public function modifyQuery(Varien_Event_Observer $observer)
	{
		/** @var Asm_Solr_Model_Solr_Query $query */
		$query = $observer->getQuery();
		$query->setFaceting();

		$filterAttributes = $this->getFilterableAttributes();
		foreach ($filterAttributes as $attribute) {
			$query->addFacetField(Mage::helper('solr')->getFieldNameByAttribute($attribute));
		}
	}

	/**
	 * Get collection of all filterable attributes for layer products set
	 *
	 * @return Mage_Catalog_Model_Resource_Product_Attribute_Collection
	 */
	protected function getFilterableAttributes()
	{
		$collection = Mage::getResourceModel('catalog/product_attribute_collection');
		$collection
			->setItemObjectClass('catalog/resource_eav_attribute')
			->addStoreLabel(Mage::app()->getStore()->getId())
			->setOrder('position', 'ASC')
			->addIsFilterableFilter();
		$collection->load();

		return $collection;
	}

}