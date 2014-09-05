<?php


class Asm_Solr_Model_Resource_Indexer_Fieldprocessor_Tokenizer extends Asm_Solr_Model_Resource_Indexer_Fieldprocessor_Abstract {

	protected $delimiter;

	protected $fieldType;


	public function __construct($parameters) {
		$this->delimiter = (string) Mage::getConfig()->getNode('global/index/fieldMap/' . $parameters['attributeCode'] . '/processor/tokenizer/delimiter');

		parent::__construct($parameters);
	}

	public function getFieldName() {
		return $this->attribute->getAttributeCode() . '_' . $this->fieldType . 'M';
	}

	public function getFieldValue() {
		return explode($this->delimiter, $this->attributeValue);
	}

}