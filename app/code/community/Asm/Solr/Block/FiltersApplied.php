<?php

class Asm_Solr_Block_FiltersApplied extends Mage_Core_Block_Template
{

	/**
	 *
	 *
	 * @return Asm_Solr_Model_Solr_Facet_FacetOption[]
	 */
	public function getAppliedFilters()
	{
        $result = Mage::registry('solr_result');

		$filters  = array();
		$facets   = $result->getResponse()->getFacetFields();
		/** @var Asm_Solr_Model_Solr_Facet_Facet[] $facets */
		$urlQuery = Mage::getModel('core/url')->getRequest()->getQuery();

		foreach ($facets as $facet) {
			$attributeCode = $facet->getAttributeCode();
			if (array_key_exists($attributeCode, $urlQuery)) {
				$optionValue = $urlQuery[$attributeCode];
				$facetOption = $facet->getOption($optionValue);

				$filters[$attributeCode] = $facetOption;
			}
		}

		return $filters;
	}

}