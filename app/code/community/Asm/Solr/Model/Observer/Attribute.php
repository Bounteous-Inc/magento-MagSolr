<?php


class Asm_Solr_Model_Observer_Attribute {

	/**
	 * Weight options
	 *
	 * @todo make this configurable
	 * @var array
	 */
	protected $weights = array(1, 2, 3, 4, 5);


	/**
	 * Listens for event adminhtml_catalog_product_attribute_edit_prepare_form,
	 * add the search weight field for attributes
	 *
	 * @param Varien_Event_Observer $event
	 */
	public function addAttributeWeightField($event) {

		$form      = $event->getEvent()->getForm();
		$attribute = $event->getEvent()->getAttribute();
		$fieldset  = $form->getElement('front_fieldset');

		$fieldset->addField('search_weight', 'select', array(
			'name'   => 'search_weight',
			'label'  => Mage::helper('catalog')->__('Search Weight'),
			'values' => $this->getWeightOptions(),
		), 'is_visible_in_advanced_search');
		/**
		 * Disable default search fields
		 */
		$attributeCode = $attribute->getAttributeCode();

		if ($attributeCode == 'name') {
			$form->getElement('is_searchable')->setDisabled(1);
		}
	}

	/**
	 * Gets an array of value/label pairs for the search weight drop down field
	 *
	 * @return array
	 */
	protected function getWeightOptions() {
		$options = array();

		foreach ($this->weights as $value) {
			$options[] = array(
				'value' => $value,
				'label' => $value
			);
		}

		return $options;
	}

}