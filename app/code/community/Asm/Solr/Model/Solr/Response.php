<?php

/**
 * Solr response
 *
 * @category Asm
 * @package Asm_Solr
 * @author Ingo Renner <ingo@infielddesign.com>
 */
class Asm_Solr_Model_Solr_Response
{
	/**
	 * @var Apache_Solr_Response
	 */
	protected $rawResponse;

	/**
	 * Constructor
	 *
	 */
	public function __construct(array $responseParameters = array()) {
		$this->rawResponse = $responseParameters['rawResponse'];
	}

	/**
	 * Gets the number of results found
	 *
	 * @return integer Number of results found
	 */
	public function getNumberOfResults() {
		return $this->rawResponse->response->numFound;
	}

	/**
	 * Gets the result documents
	 *
	 * @return Apache_Solr_Document[] Array of Apache_Solr_Document
	 */
	public function getDocuments() {
		return $this->rawResponse->response->docs;
	}

	/**
	 * Gets the field facets
	 *
	 * @return Asm_Solr_Model_Solr_Facet_Facet[] Array of Asm_Solr_Model_Solr_Facet_Facet
	 */
	public function getFacetFields() {

	}

	/* TODO add range and query facet support
	public function getFacetRanges() {

	}

	public function getFacetQueries() {

	}
	*/
}