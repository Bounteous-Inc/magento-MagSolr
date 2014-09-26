<?php

/**
 * Solr schema helper
 *
 * @category Asm
 * @package Asm_Solr
 * @author Ingo Renner <ingo@infielddesign.com>
 */
class Asm_Solr_Helper_Schema
{
	/**
	 * A map of  Solr document fields to Magento product attributes.
	 *
	 * Contains all fixed indexed fields/attributes, other product attributes
	 * are added to Solr documents using dynamic fields.
	 *
	 * @var array
	 */
	protected static $fieldToAttributeMap = array(
		'appKey' => '',
		'type' => '',
		'id' => '',
		'site' => '',
		'siteHash' => '',
		'created' => 'created_at',
		'changed' => 'changed_at',
		'sku' => 'sku',
		'productId' => 'entity_id',
		'storeId' => '',
		'categoryId' => 'category_ids',
		'inStock' => '',
		'isSalable' => 'is_salable',
		'isVisible' => 'status',
		'isVisibleInCatalog' => '',
		'title' => 'name',
		'content' => 'description',
		'keywords' => 'meta_keywords',
		'url' => '',
		'price' => 'price',
		'manufacturer' => 'manufacturer',
		'priceCurrency' => '',
		'indexed' => '',
		'_version_' => '',
		'score' => ''
	);

	/**
	 * A list of dynamic field suffixes. There are more dynamic field types,
	 * but these are the ones that are currently used.
	 *
	 * Used to automatically map dynamic fields back to product attributes.
	 *
	 * @var array
	 */
	protected static $dynamicFieldSuffixes = array(
		'dateS',
		'doubleS',
		'intS',
		'stringS',
		'textS'
	);

	/**
	 * Gets a map of Solr document fields to Magento product attributes.
	 *
	 * @return array
	 */
	public function getFieldToAttributeMap() {
		return self::$fieldToAttributeMap;
	}

	/**
	 * Gets a list of Solr dynamic field suffixes
	 *
	 * @return array
	 */
	public function getDynamicFieldSuffixes() {
		return self::$dynamicFieldSuffixes;
	}

	/**
	 * Generates a (dynamic) field name for a given attribute
	 *
	 * @param string|Mage_Eav_Model_Entity_Attribute_Abstract $attribute Maybe an attribute object or an attribute code
	 * @param bool $multiValue If true, generates a multi value field name, single value if false/by default
	 * @return bool|string Field name or false if the attribute's backend type cannot be matched to a Solr field type
	 * @throws UnexpectedValueException if $attribute is not an attribute code or an attribute instance
	 */
	public function getFieldNameByAttribute($attribute, $multiValue = false) {
		$fieldName = false;

		if (is_string($attribute)) {
			$fieldName = array_search($attribute, self::$fieldToAttributeMap, true);
			if ($fieldName !== false) {
				// early return if we can find the field in the field/attribute map
				return $fieldName;
			}

			// turn attribute code into attribute instance
			$attribute = Mage::getSingleton('eav/config')
				->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attribute);
		}

		if (!($attribute instanceof Mage_Eav_Model_Entity_Attribute_Abstract)) {
			throw new UnexpectedValueException(
				'$attribute must either be an attribute code or an instance of Mage_Eav_Model_Entity_Attribute_Abstract',
				1395360135
			);
		}

		$attributeCode = $attribute->getAttributeCode();

		$countFieldType = 'S'; // single value
		if ($multiValue || $attribute->getIsConfigurable()) {
			$countFieldType = 'M';
		}

		if (array_key_exists($attributeCode, self::$fieldToAttributeMap)) {
			return self::$fieldToAttributeMap[$attributeCode];
		}

		switch ($attribute->getBackendType()) {
			case 'datetime':
				$fieldName = $attributeCode . '_date' . $countFieldType;
				break;
			case 'decimal':
				$fieldName = $attributeCode . '_double' . $countFieldType;
				break;
			case 'int':
				// FIXME must use correct field type if it's actually int
				// $fieldName = $attributeCode . '_int' . $countFieldType;
				$fieldName = $attributeCode . '_string' . $countFieldType;
				break;
			case 'text':
			case 'varchar':
				// TODO there might be cases when you want a string instead,
				// might need a configuration option
				$fieldName = $attributeCode . '_text' . $countFieldType;
				break;
		}

		return $fieldName;
	}

}