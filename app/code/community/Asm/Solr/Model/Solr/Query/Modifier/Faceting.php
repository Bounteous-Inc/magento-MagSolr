<?php

/**
 * Faceting Query Modifier, adds faceting parameters to a query
 *
 * @category Asm
 * @package Asm_Solr
 * @author Ingo Renner <ingo@infielddesign.com>
 */
class Asm_Solr_Model_Solr_Query_Modifier_Faceting
{

	/**
	 * @var Mage_Catalog_Model_Resource_Eav_Attribute[]
	 */
	protected $filterableAttributes;

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

		$this->filterableAttributes = $this->getFilterableAttributes();

		// set facet.* query parameters / which facets to generate
		foreach ($this->filterableAttributes as $attribute) {
			$query->addFacetField(Mage::helper('solr/schema')->getFieldNameByAttribute($attribute));
		}

		// set filter query (fq) parameters / actually filtering results
		$filters = $this->getQueryFilters();
		foreach ($filters as $fieldName => $value) {
			$query->addFilter($fieldName, $value);
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

	/**
	 * Generate filters from current URL query
	 *
	 * @return array Filters as field name => value pairs
	 */
	protected function getQueryFilters()
	{
		$filters = array();
		$helper  = Mage::helper('solr');

		// get query part from current URL
		$urlQuery = Mage::getModel('core/url')->getRequest()->getQuery();

		foreach ($this->filterableAttributes as $attribute) {
			$attributeCode = $attribute->getAttributeCode();

			// match attribute codes
			if (array_key_exists($attributeCode, $urlQuery)) {
				// generate filters from matches
				$fieldName = Mage::helper('solr/schema')->getFieldNameByAttribute($attribute);
				$filters[$fieldName] = '"' . $urlQuery[$attributeCode] . '"';
			}
		}

		return $filters;
	}

}