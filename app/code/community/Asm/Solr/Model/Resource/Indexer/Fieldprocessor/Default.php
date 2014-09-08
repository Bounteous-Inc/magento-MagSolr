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
 * Field processor default implementation.
 *
 * @author Ingo Renner <ingo@infielddesign.com>
 */
class Asm_Solr_Model_Resource_Indexer_Fieldprocessor_Default extends Asm_Solr_Model_Resource_Indexer_Fieldprocessor_Abstract {

	/**
	 * @inheritdoc
	 */
	public function getFieldName() {
		$multiValue = false;
		if (is_array($this->attributeValue)) {
			$multiValue = true;
		}

		return Mage::helper('solr/schema')->getFieldNameByAttribute($this->attribute, $multiValue);
	}

	/**
	 * @inheritdoc
	 */
	public function getFieldValue() {
		$attributeValue = $this->attributeValue;

		if ($this->attribute->getBackendType() == 'datetime') {
			$attributeValue = Mage::helper('solr')->dateToIso($this->attributeValue);
		}

		return $attributeValue;
	}

}