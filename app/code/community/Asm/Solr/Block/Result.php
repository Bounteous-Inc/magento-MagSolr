<?php


class Asm_Solr_Block_Result extends Mage_Core_Block_Template
{

	protected $productCollection;

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

	/**
	 * Retrieve search result count
	 *
	 * @return string
	 */
	public function getResultCount()
	{
		if (!$this->getData('result_count')) {
			$size = $this->getProductCollection()->getSize();
#			$this->helper('solr')->getQuery()->setNumResults($size);
			$this->setResultCount($size);
		}
		return $this->getData('result_count');
	}

	/**
	 * Retrieve No Result or Minimum query length Text
	 *
	 * @return string
	 */
	public function getNoResultText()
	{
		return $this->_getData('no_result_text');
	}

}

?>