<?php


/**
 * Solr facet
 *
 * @category Asm
 * @package Asm_Solr
 * @author Ingo Renner <ingo@infielddesign.com>
 */
class Asm_Solr_Model_Solr_Facet_Facet
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
	 * The facet's name
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The attribute the facet belongs to
	 *
	 * @var string
	 */
	protected $attributeCode;

	/**
	 * The index field the facet is built from.
	 *
	 * @var string
	 */
	protected $field;

	/**
	 * Facet options
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * Constructor
	 *
	 * @param array $parameters Must contain keys 'name' and 'field
	 * @throws RuntimeException if $parameters does not contain key 'name'
	 * @throws RuntimeException if $parameters does not contain key 'field'
	 */
	public function __construct(array $parameters = array())
	{
		if (empty($parameters['attributeCode'])) {
			throw new RuntimeException('Must provide attributeCode parameter for facet', 1395872280);
		}

		if (empty($parameters['field'])) {
			throw new RuntimeException('Must provide field parameter for facet', 1395872289);
		}

		$this->attributeCode = $parameters['attributeCode'];
		$this->name          = $parameters['attributeCode']; // same as attribute code for now
		$this->field         = $parameters['field'];
	}

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
		$this->options[] = $option;
	}

	public function getOptions() {
		return $this->options;
	}

	/**
	 * Gets the facet's name
	 *
	 * @return string The facet's name
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Gets the field name the facet is operating on.
	 *
	 * @return string The name of the field the facet is operating on.
	 */
	public function getField()
	{
		return $this->field;
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
		return $this->name;
	}

}