<?php

/**
 * Solr facet option
 *
 * @category Asm
 * @package Asm_Solr
 * @author Ingo Renner <ingo@infielddesign.com>
 */
class Asm_Solr_Model_Solr_Facet_FacetOption
{
	/**
	 * Facet name
	 *
	 * @var string
	 */
	protected $facetName;

	/**
	 * Facet option value
	 *
	 * @var integer|string
	 */
	protected $value;

	/**
	 * Facet option value encoded for use in URLs
	 *
	 * @var string
	 */
	protected $urlValue;

	/**
	 * Number of results that will be returned when applying this facet
	 * option's filter to the query.
	 *
	 * @var integer
	 */
	protected $numberOfResults;


	/**
	 * Constructor.
	 *
	 * @param array $parameters Requires keys facetName, optionValue, and numberOfResults
	 * @throws RuntimeException if any of the required parameters is missing
	 */
	public function __construct(array $parameters = array())
	{
		if (empty($parameters['facetName'])) {
			throw new RuntimeException('Must provide facetName parameter for facet option', 1395872359);
		}
		if (empty($parameters['optionValue'])) {
			throw new RuntimeException('Must provide optionValue parameter for facet option', 1395872360);
		}
		if (!isset($parameters['numberOfResults'])) { // FIXME use empty()
			throw new RuntimeException('Must provide numberOfResults parameter for facet option', 1395872361);
		}

		$this->facetName       = $parameters['facetName'];
		$this->value           = $parameters['optionValue'];
		$this->numberOfResults = intval($parameters['numberOfResults']);
	}

	/**
	 * Gets the option's label
	 *
	 * @return string facet option label
	 */
	public function getLabel()
	{
		return $this->value;
	}

	/**
	 * Gets the option's value.
	 *
	 * @return integer|string The option's value.
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * Sets the option's value for use in URLs
	 *
	 * @param string $urlValue The option's URL value.
	 */
	public function setUrlValue($urlValue)
	{
		$this->urlValue = $urlValue;
	}

	/**
	 * Gets the option's value for use in URLs
	 *
	 * @return string The option's URL value.
	 */
	public function getUrlValue()
	{
		$urlValue = $this->urlValue;

		if (empty($urlValue)) {
			$urlValue = $this->value;
		}

		return $urlValue;
	}

	/**
	 * Gets the number of results this option yields when applied to the query.
	 *
	 * @return integer Number of results
	 */
	public function getNumberOfResults()
	{
		return $this->numberOfResults;
	}

}