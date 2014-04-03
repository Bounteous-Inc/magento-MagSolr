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

	protected $unwantedUrlParameters;


	public function __construct()
	{
		$this->unwantedUrlParameters = array(
			Mage::getBlockSingleton('page/html_pager')->getPageVarName() => null // reset page browser
		);

		parent::__construct();
	}


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