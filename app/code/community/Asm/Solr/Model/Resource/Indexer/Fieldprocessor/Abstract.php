<?php

abstract class Asm_Solr_Model_Resource_Indexer_Fieldprocessor_Abstract {

	protected $attribute;

	protected $attributeValue;

	protected $fieldType;


	public function __construct($parameters)
	{
		$this->attribute = Mage::getSingleton('eav/config')->getAttribute(
			Mage_Catalog_Model_Product::ENTITY,
			$parameters['attributeCode']
		);

		$this->attributeValue = $parameters['attributeValue'];

		$this->fieldType = (string) Mage::getConfig()->getNode('global/index/fieldMap/' . $parameters['attributeCode'] . '/type');
	}

	public function getFieldName() {
		$multiValue = false;
		if (is_array($this->attributeValue)) {
			$multiValue = true;
		}

		return Mage::helper('solr/schema')->getFieldNameByAttribute($this->attribute, $multiValue);
	}

	public function getFieldValue() {
		$attributeValue = $this->attributeValue;

		if ($this->attribute->getBackendType() == 'datetime') {
			$attributeValue = Mage::helper('solr')->dateToIso($this->attributeValue);
		}

		return $attributeValue;
	}
}