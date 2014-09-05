<?php


class Asm_Solr_Model_Resource_Indexer_Fieldprocessor_Factory {

	/**
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