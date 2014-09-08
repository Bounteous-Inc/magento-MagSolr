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
 * A tokenizing field processor.
 *
 * Takes the field's value and tokenizes it using the delimiter as configured.
 * The delimiter is used as configured, there is no trim() applied to it.
 * Delimiter defaults to , (comma).
 *
 * Example:
 * <fieldMap>
 *     <magento_foo_field>
 *         <type>string<type>
 *         <processor>
 *             <tokenizer>
 *                 <delimiter>,</delimiter>
 *             <tokenizer>
 *         </processor>
 *     </magento_foo_field>
 * </fieldMap>
 *
 * @author Ingo Renner <ingo@infielddesign.com>
 */
class Asm_Solr_Model_Resource_Indexer_Fieldprocessor_Tokenizer extends Asm_Solr_Model_Resource_Indexer_Fieldprocessor_Abstract {

	/**
	 * @var string
	 */
	protected $delimiter = ',';


	/**
	 * @inheritdoc
	 */
	public function __construct($parameters) {
		$delimiter = (string) Mage::getConfig()->getNode('global/index/fieldMap/' . $parameters['attributeCode'] . '/processor/tokenizer/delimiter');
		if (!empty($delimiter)) {
			$this->delimiter = $delimiter;
		}

		parent::__construct($parameters);
	}

	/**
	 * @inheritdoc
	 */
	public function getFieldName() {
		return $this->attribute->getAttributeCode() . '_' . $this->fieldType . 'M';
	}

	/**
	 * @inheritdoc
	 */
	public function getFieldValue() {
		return explode($this->delimiter, $this->attributeValue);
	}

}