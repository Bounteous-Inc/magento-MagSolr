<?php


/**
 * Solr facet
 *
 * @category Asm
 * @package Asm_Solr
 * @author Ingo Renner <ingo@infielddesign.com>
 *
 * @method string getName()
 * @method Asm_Solr_Model_Solr_Facet_Facet setName(string $name)
 * @method Asm_Solr_Model_Solr_Facet_Facet setAttributeCode(string $attributeCode)
 * @method Asm_Solr_Model_Solr_Facet_Facet setField(string $field)
 */
class Asm_Solr_Model_Solr_Facet_Facet extends Varien_Object
{
	const TYPE_FIELD = 'field';

	const TYPE_QUERY = 'query';

	const TYPE_RANGE = 'range';

	/**
	 * Facet type, defaults to field facet.
	 *
	 * @var string
	 */
	protected $type = self::TYPE_FIELD;

	/**
	 * Facet options
	 *
	 * @var array
	 */
	protected $options = array();



	/**
	 * Checks whether an option of the facet has been selected by the user by
	 * checking the URL GET parameters.
	 *
	 * @return boolean TRUE if any option of the facet is applied, FALSE otherwise
	 */
	public function isActive()
	{
		$isActive = FALSE;

		$selectedOptions = $this->getSelectedOptions();
		if (!empty($selectedOptions)) {
			$isActive = TRUE;
		}

		return $isActive;
	}

	/**
	 * Gets the facet's currently user-selected options
	 *
	 * @return array An array with user-selected facet options.
	 */
	public function getSelectedOptions()
	{
		$selectedOptions = array();

#FIXME
		$resultParameters = t3lib_div::_GET('tx_solr');
		$filterParameters = array();
		if (isset($resultParameters['filter'])) {
			$filterParameters = (array)array_map('urldecode', $resultParameters['filter']);
		}

		foreach ($filterParameters as $filter) {
			list($facetName, $filterValue) = explode(':', $filter);

			if ($facetName == $this->name) {
				$selectedOptions[] = $filterValue;
			}
		}

		return $selectedOptions;
	}

	/**
	 * Adds a facet option
	 *
	 * @param Asm_Solr_Model_Solr_Facet_FacetOption $option
	 */
	public function addOption(Asm_Solr_Model_Solr_Facet_FacetOption $option) {
		$this->options[$option->getValue()] = $option;
	}

	public function getOptions() {
		return $this->options;
	}

	/**
	 * Gets an option by its value
	 *
	 * @param string $optionValue Option value
	 * @return Asm_Solr_Model_Solr_Facet_FacetOption
	 */
	public function getOption($optionValue)
	{
		return $this->options[$optionValue];
	}

	/**
	 * Gets the facet's internal type. One of field, range, or query.
	 *
	 * @return string Facet type.
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Gets the facet's label
	 *
	 * @return string
	 */
	public function getLabel()
	{
		return $this->getName();
	}

}