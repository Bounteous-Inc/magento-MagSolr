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
 * Field processor factory
 *
 * Depending on the given attribute code tries to find a configured field
 * processor. If no field processor is configured, the default one is returned.
 *
 * @author Ingo Renner <ingo@infielddesign.com>
 */
class Asm_Solr_Model_Resource_Indexer_Fieldprocessor_Factory {

	/**
	 * Factory method for field processors
	 *
	 * @param string $attributeCode
	 * @param mixed $value Attribute value
	 * @return Asm_Solr_Model_Resource_Indexer_Fieldprocessor_Abstract
	 */
	public function getFieldProcessor($attributeCode, $value)
	{
		$fieldProcessor = null;

		$fieldProcessorParameters = array(
			'attributeCode' => $attributeCode,
			'attributeValue' => $value
		);

		$fieldMap = Mage::getConfig()->getNode('global/index/fieldMap')->asArray();
		if (array_key_exists($attributeCode, $fieldMap)) {
			$processors = array_keys($fieldMap[$attributeCode]['processor']);
			$processorName = $processors[0];

			$fieldProcessor     = Mage::getResourceModel(
				'solr/indexer_fieldprocessor_' . $processorName,
				$fieldProcessorParameters
			);
		} else {
			$fieldProcessor = Mage::getResourceModel(
				'solr/indexer_fieldprocessor_default',
				$fieldProcessorParameters
			);
		}

		return $fieldProcessor;
	}

}