<?php


class Asm_Solr_Block_Facets extends Mage_Core_Block_Template
{
	protected $displayProductCount = null;

    /**
     * @var Apache_Solr_Response
     */
    protected $rawResponse;

	/**
	 * Get the rendered block for the applied filters
	 *
	 * @return string rendered applied filters block
	 */
	public function getAppliedFiltersHtml() {
		return $this->getChildHtml('solr.filters_applied');
	}

//	/**
//	 * Whether to show the number of products that will be returned when
//	 * applying a filter.
//	 *
//	 * @return bool
//	 */
//	public function shouldDisplayProductCount()
//	{
//		if ($this->displayProductCount === null) {
//			$this->displayProductCount = Mage::helper('catalog')->shouldDisplayProductCountOnLayer();
//		}
//
//		return $this->displayProductCount;
//	}

	/**
	 * Gets the facets returned for the current/last query
	 *
	 * @return Asm_Solr_Model_Solr_Facet_Facet[]
	 */
	public function getFacets() {
        $result = Mage::registry('solr_result');
        $facets   = $this->getResult()->getResponse()->getFacetFields();

        return $facets;
	}

    public function getKeywords()
    {
        if (!$this->getData('keywords'))
        {
            $keywords = $this->getRequest()->getParam('q', '');

            $this->setData('keywords', $keywords);
        }

        return $this->getData('keywords');
    }

    public function getFilterQuery()
    {
        if (!$this->getData('filter_query'))
        {
            $keywords = $this->getRequest()->getParam('fq', '');

            $this->setData('filter_query', $keywords);
        }

        return $this->getData('filter_query');
    }

    public function getResult()
    {
        // if we don't have a result set for us, let's make one
        if (!$this->getData('result'))
        {
            /** @var Asm_Solr_Model_Result $result */
            $result = Mage::getModel('solr/result');

            $keywords = $this->getKeywords();
            $filterQuery = $this->getFilterQuery();
            $solrType = 'catalog/product';
            $limit = 0;
            $offset = 0;

            $query = $result->getQuery();
            $query->setFaceting(true);
            $query->setKeywords($keywords);
            $query->addFilter('type', $solrType);

            $query->addQueryParameter("facet.range","price");
            $query->addQueryParameter("facet.range.start","0");
            $query->addQueryParameter("facet.range.end","100");
            $query->addQueryParameter("facet.range.gap","10");

            $result->load($limit, $offset);

            $this->setData('result', $result);
        }

        return $this->getData('result');
    }

    public function getFacetRanges()
    {
        return $this->getResult()->getResponse()->getFacetRanges();
    }
}