<?php


class Asm_Solr_Block_Result extends Mage_Core_Block_Template
{

    protected $_type        = 'product'; // default result type is product search
    protected $_solrType    = 'catalog/product';

	protected $productCollection;


    /**
     * Set Store Id
     *
     * @param int $storeId
     * @return Mage_CatalogSearch_Model_Query
     */
    public function setStoreId($storeId)
    {
        $this->setData('store_id', $storeId);
    }

    /**
     * Retrieve store Id
     *
     * @return int
     */
    public function getStoreId()
    {
        if (!$storeId = $this->getData('store_id')) {
            $storeId = Mage::app()->getStore()->getId();
        }
        return $storeId;
    }

    public function getOffset()
    {
        if (!$this->getData('offset'))
        {
            $limit = $this->getRequest()->getParam('offset', 0);

            $this->setData('offset', $limit);
        }

        return $this->getData('offset');
    }

    public function getLimit()
    {
        if (!$this->getData('limit'))
        {
            $limit = $this->getRequest()->getParam('limit', 25);

            $this->setData('limit', $limit);
        }

        return $this->getData('limit');
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

    public function getKeywordsCleaned()
    {
        return Asm_Solr_Model_Solr_Query::cleanKeywords($this->getKeywords());
    }

    /**
     * @return Asm_Solr_Model_Solr_Query
     */
    public function getQuery()
    {
        return Mage::getModel('solr/solr_query');
    }

    public function getType()
    {
        return $this->_type;
    }


    public function getSolrType()
    {
        return $this->_solrType;
    }

    /**
     * @return Asm_Solr_Model_Result
     */
    public function getResult()
    {
        // if we don't have a result set for us, let's make one
        if (!$this->getData('result'))
        {
            /** @var Asm_Solr_Model_Result $result */
            $result = Mage::getModel('solr/result');

            $query = $result->getQuery();
            $query->setKeywords($this->getKeywords());
            $query->addFilter('type', $this->getSolrType());

            $result->load($this->getLimit(), $this->getOffset());

            $this->setData('result', $result);
        }

        return $this->getData('result');
    }

    public function getResultCount()
    {
        return $this->getResult()->getCount();
    }

    public function getResultDocuments()
    {
        return $this->getResult()->getDocuments();
    }

	/**
	 * Prepare layout
	 *
	 * @return Asm_Solr_Block_Result
	 */
	protected function prepareLayout()
	{
		// add Home breadcrumb
		$breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
		if ($breadcrumbs) {
			$title = $this->__("Search results for: '%s'", $this->helper('solr')->getQuery()->getKeywordsCleaned());

			$breadcrumbs->addCrumb('home', array(
				'label' => $this->__('Home'),
				'title' => $this->__('Go to Home Page'),
				'link' => Mage::getBaseUrl()
			))->addCrumb('search', array(
					'label' => $title,
					'title' => $title
				));
		}

		// modify page title
		$title = $this->__("Search results for: '%s'", $this->helper('solr')->getQuery()->getKeywordsCleaned());
		$this->getLayout()->getBlock('head')->setTitle($title);

		return parent::_prepareLayout();
	}

	/**
	 * Retrieve additional blocks html
	 *
	 * @return string
	 */
	public function getAdditionalHtml()
	{
		return $this->getLayout()->getBlock('search_result_list')->getChildHtml('additional');
	}

	/**
	 * Retrieve search list toolbar block
	 *
	 * @return Mage_Catalog_Block_Product_List
	 */
	public function getListBlock()
	{
		return $this->getChild('search_result_list');
	}

	/**
	 * Set search available list orders
	 *
	 * @return Asm_Solr_Block_Result
	 */
	public function setListOrders()
	{
		$currentCategory = Mage::getSingleton('catalog/layer')
			->getCurrentCategory();
		/* @var $currentCategory Mage_Catalog_Model_Category */
		$availableOrders = $currentCategory->getAvailableSortByOptions();
		unset($availableOrders['position']);

		$availableOrders = array_merge(array(
			'relevance' => $this->__('Relevance')
		), $availableOrders);

		$this->getListBlock()
			->setAvailableOrders($availableOrders)
			->setDefaultDirection('desc')
			->setSortBy('relevance');

		return $this;
	}

	/**
	 * Set available view mode
	 *
	 * @return Asm_Solr_Block_Result
	 */
	public function setListModes()
	{
		$this->getListBlock()
			->setModes(array(
					'grid' => $this->__('Grid'),
					'list' => $this->__('List'))
			);
		return $this;
	}

	/**
	 * Set Search Result collection
	 *
	 * @return Asm_Solr_Block_Result
	 */
	public function setListCollection()
	{
//        $this->getListBlock()
//           ->setCollection($this->_getProductCollection());
		return $this;
	}

	/**
	 * Retrieve Search result list HTML output
	 *
	 * @return string
	 */
	public function getProductListHtml()
	{
		return $this->getChildHtml('search_result_list');
	}

	/**
	 * Retrieve loaded category collection
	 *
	 * @return Mage_CatalogSearch_Model_Resource_Fulltext_Collection
	 */
	protected function getProductCollection()
	{
		if (is_null($this->productCollection)) {
			$this->productCollection = $this->getListBlock()->getLoadedProductCollection();
		}

		return $this->productCollection;
	}

    public function getHeaderText()
    {
        if (!$this->getData('header_text'))
        {
            // supply a default
            $text = $this->__("%s search results for '%s'", uc_words($this->getType()), $this->getKeywordsCleaned());

            $this->setData('header_text', $text);
        }

        return $this->getData('header_text');
    }
    /**
     * Retrieve No Result or Minimum query length Text
     *
     * @return string
     */
    public function getNoResultText()
    {
        if (!$this->getData('no_result_text'))
        {
            // supply a default
            $text = $this->__('Your %s search returned no results.', uc_words($this->getType()));

            $this->setData('no_result_text', $text);
        }

        return $this->_getData('no_result_text');
    }

    public function getResultListHtml()
    {
        return $this->getChildHtml('search_result_list');
    }

}
?>