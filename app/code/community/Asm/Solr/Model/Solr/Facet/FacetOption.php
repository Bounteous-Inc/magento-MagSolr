<?php

/**
 * Solr facet option
 *
 * @category Asm
 * @package Asm_Solr
 * @author Ingo Renner <ingo@infielddesign.com>
 */
class Asm_Solr_Model_Solr_Facet_FacetOption extends Varien_Object
{

	/**
	 * URL parameters to remove when adding/removing facet options
	 *
	 * @var array
	 */
	protected $unwantedUrlParameters;


	/**
	 * Constructor, initializes URL parameters to remove when changing
	 * facet combinations.
	 *
	 */
	public function __construct()
	{
		// Go back to page 1 when adding/removing a facet option
		$this->unwantedUrlParameters = array(
			Mage::getBlockSingleton('page/html_pager')->getPageVarName() => null // reset page browser
		);

		parent::__construct();
	}

	/**
	 * Gets the URL to add the facet option as a filter to the current query
	 *
	 * @return string URL with parameters to add a new filter to the query
	 */
	public function getAddUrl()
	{
		$query = array_merge(
			array($this->getFacet()->getName() => $this->getValue()),
			$this->unwantedUrlParameters
		);

		return Mage::getUrl('*/*', array(
			'_current'     => true,
			'_use_rewrite' => true,
			'_query'       => $query
		));
	}

	/**
	 * Gets the current URL with the facet option's filter removed
	 *
	 * @return string URL with the facet option's filter parameter removed
	 */
	public function getRemoveUrl()
	{
		$query = array_merge(
			array($this->getFacet()->getName() => null),
			$this->unwantedUrlParameters
		);

		return Mage::getUrl('*/*', array(
			'_current' => true,
			'_use_rewrite' => true,
			'_query' => $query
		));
	}

}