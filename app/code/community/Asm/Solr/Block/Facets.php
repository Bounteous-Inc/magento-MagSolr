<?php


class Asm_Solr_Block_Facets extends Mage_Core_Block_Template
{
	protected $displayProductCount = null;

	/**
	 * Get the rendered block for the applied filters
	 *
	 * @return string rendered applied filters block
	 */
	public function getAppliedFiltersHtml() {
		return $this->getChildHtml('solr.filters_applied');
	}

	/**
	 * Whether to show the number of products that will be returned when
	 * applying a filter.
	 *
	 * @return bool
	 */
	public function shouldDisplayProductCount()
	{
		if ($this->displayProductCount === null) {
			$this->displayProductCount = Mage::helper('catalog')->shouldDisplayProductCountOnLayer();
		}

		return $this->displayProductCount;
	}

	/**
	 * Gets the facets returned for the current/last query
	 *
	 * @return Asm_Solr_Model_Solr_Facet_Facet[]
	 */
	public function getFacets() {
		return Mage::helper('solr')->getResponse()->getFacetFields();
	}
}