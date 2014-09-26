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
 * Attributes helper
 *
 * @author Ingo Renner <ingo@infielddesign.com>
 */
class Asm_Solr_Helper_Attribute {

	/**
	 * @var Mage_Catalog_Model_Resource_Eav_Attribute[]
	 */
	protected $indexableAttributes;

	/**
	 * @var Mage_Catalog_Model_Resource_Eav_Attribute[]
	 */
	protected $searchableAttributes;


	/**
	 * Gets an array of attributes marked as indexable
	 *
	 * @return Mage_Catalog_Model_Resource_Eav_Attribute[]
	 */
	public function getIndexableAttributes()
	{
		if (empty($this->indexableAttributes)) {
			$productAttributeCollection = Mage::getResourceModel('catalog/product_attribute_collection');
			/** @var Mage_Catalog_Model_Resource_Product_Attribute_Collection $productAttributeCollection */

			$productAttributeCollection->addToIndexFilter(true);
			$attributes = $productAttributeCollection->getItems();

			$entity = Mage::getSingleton('eav/config')
				->getEntityType(Mage_Catalog_Model_Product::ENTITY)
				->getEntity();
			/** @var Mage_Catalog_Model_Resource_Product $entity */

			foreach ($attributes as $attribute) {
				/** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
				$attribute->setEntity($entity);
			}

			$this->indexableAttributes = $attributes;
		}

		return $this->indexableAttributes;
	}

	/**
	 * Gets attributes by backend type
	 *
	 * @param $type string backend type; can be static, int, varchar, text, decimal, datetime
	 * @return Mage_Catalog_Model_Resource_Eav_Attribute[]
	 */
	public function getIndexableAttributesByType($type)
	{
		$typedAttributes      = array();
		$searchableAttributes = Mage::helper('solr/attribute')->getIndexableAttributes();

		foreach ($searchableAttributes as $attributeId => $attribute) {
			if ($attribute->getBackendType() == $type) {
				$typedAttributes[$attributeId] = $attribute;
			}
		}

		return $typedAttributes;
	}

	/**
	 * Gets a indexable attribute by name
	 *
	 * @param $attributeName string attribute name
	 * @return \Mage_Catalog_Model_Resource_Eav_Attribute|\Mage_Eav_Model_Entity_Attribute_Abstract
	 */
	public function getIndexableAttributeByName($attributeName)
	{
		$searchableAttribute = Mage::getSingleton('eav/config')
			->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attributeName);

		$attributes = Mage::helper('solr/attribute')->getIndexableAttributes();
		if (is_numeric($attributeName) && isset($attributes[$attributeName])) {
			$searchableAttribute = $attributes[$attributeName];
		} elseif (is_string($attributeName)) {
			foreach ($attributes as $attributeModel) {
				if ($attributeModel->getAttributeCode() == $attributeName) {
					$searchableAttribute = $attributeModel;
					break;
				}
			}
		}

		return $searchableAttribute;
	}

	/**
	 * Gets an array of attributes marked as searchable
	 *
	 * @return Mage_Catalog_Model_Resource_Eav_Attribute[]
	 */
	public function getSearchableAttributes()
	{
		if (empty($this->searchableAttributes)) {
			$productAttributeCollection = Mage::getResourceModel('catalog/product_attribute_collection');
			/** @var Mage_Catalog_Model_Resource_Product_Attribute_Collection $productAttributeCollection */

			$productAttributeCollection->addSearchableAttributeFilter();
			$attributes = $productAttributeCollection->getItems();

			$entity = Mage::getSingleton('eav/config')
				->getEntityType(Mage_Catalog_Model_Product::ENTITY)
				->getEntity();
			/** @var Mage_Catalog_Model_Resource_Product $entity */

			foreach ($attributes as $attribute) {
				/** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
				$attribute->setEntity($entity);
			}

			$this->searchableAttributes = $attributes;
		}

		return $this->searchableAttributes;
	}

	/**
	 * Creates a map of attribute codes associated to their numerical IDs
	 *
	 * @return array attributeCode => attributeId
	 */
	public function getAttributeCodeToIdMap()
	{
		if (empty($this->attributeCodeToIdMap)) {
			$attributeNameToIdMap = array();
			$searchableAttributes = Mage::helper('solr/attribute')->getIndexableAttributes();

			foreach ($searchableAttributes as $attributeId => $attribute) {
				$attributeCode = $attribute->getAttributeCode();
				$attributeNameToIdMap[$attributeCode] = $attributeId;
			}

			$this->attributeCodeToIdMap = $attributeNameToIdMap;
		}

		return $this->attributeCodeToIdMap;
	}

}