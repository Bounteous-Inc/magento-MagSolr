<?php
/**
 * Copyright 2014 Infield Design
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License .
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied .
 * See the License for the specific language governing permissions and
 * limitations under the License .
 */


/**
 * Abstract base class for field processors
 *
 */
abstract class Asm_Solr_Model_Resource_Indexer_Fieldprocessor_Abstract {

	/**
	 * @var \Mage_Eav_Model_Entity_Attribute_Abstract
	 */
	protected $attribute;

	/**
	 * @var mixed
	 */
	protected $attributeValue;

	/**
	 * @var string
	 */
	protected $fieldType;

	/**
	 * Constructor
	 *
	 * @param array $parameters Takes two keys: attributeCode and attributeValue
	 */
	public function __construct($parameters)
	{
		$this->attribute = Mage::getSingleton('eav/config')->getAttribute(
			Mage_Catalog_Model_Product::ENTITY,
			$parameters['attributeCode']
		);

		$this->attributeValue = $parameters['attributeValue'];

		$this->fieldType = (string) Mage::getConfig()->getNode('global/index/fieldMap/' . $parameters['attributeCode'] . '/type');
	}

	/**
	 * Generates the Solr field name for a given Magento product field
	 *
	 * @return string Solr field name
	 */
	abstract public function getFieldName();

	/**
	 * Process the Solr field value from the Magento field's value
	 *
	 * @return string|array
	 */
	abstract public function getFieldValue();
}