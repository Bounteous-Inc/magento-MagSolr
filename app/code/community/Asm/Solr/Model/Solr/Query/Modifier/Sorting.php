<?php

class Asm_Solr_Model_Solr_Query_Modifier_Sorting
{

	/**
	 *
	 *
	 * @var array
	 */
	protected $sortAttributes = array();

	/**
	 * Sort directions whitelist
	 *
	 * @var array
	 */
	protected $directions = array('asc', 'desc');


	/**
	 * Init sorting query modifier
	 *
	 */
	public function __construct()
	{
		$availableSortAttributes = $this->getCatalogConfig()->getAttributeUsedForSortByArray();
		unset($availableSortAttributes['position']);

		$this->sortAttributes = array_merge(array(
			'relevance' => 'Relevance' // the actual label does not matter here, we're only using the keys
		), $availableSortAttributes);
	}

	/**
	 * Called before a query is executed. Modifies the query to add sorting
	 * parameters.
	 *
	 * @param Varien_Event_Observer $observer Observer/event data
	 * @throws InvalidArgumentException for invalid dir and order parameters
	 */
	public function modifyQuery(Varien_Event_Observer $observer)
	{
		// get query part from current URL
		$urlQuery = Mage::getModel('core/url')->getRequest()->getQuery();

		if (!array_key_exists('order', $urlQuery)) {
			return;
		}

		$direction = $urlQuery['dir'];
		$field     = $urlQuery['order'];

		if (!in_array($direction, $this->directions)) {
			throw new InvalidArgumentException(
				'Invalid sort direction parameter "' . $direction . '", must be asc or desc.',
				1397868721
			);
		}

		if (array_key_exists($field, $this->sortAttributes)) {
			if ($field == 'relevance') {
				$sortField = 'relevance';
			} else {
				$sortField = Mage::helper('solr/schema')->getFieldNameByAttribute($field);
			}

			/** @var Asm_Solr_Model_Solr_Query $query */
			$query = $observer->getQuery();
			$query->setSorting($sortField . ' ' . $direction);
		} else {
			throw new InvalidArgumentException(
				'No sortable attribute found for sort parameter ' . $field,
				1397868606
			);
		}
	}

	/**
	 * Retrieve Catalog Config object
	 *
	 * @return Mage_Catalog_Model_Config
	 */
	protected function getCatalogConfig() {
		return Mage::getSingleton('catalog/config');
	}

}