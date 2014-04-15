<?php

class Asm_Solr_Model_Resource_Indexer_Catalog extends Mage_Core_Model_Resource_Db_Abstract
{

	/**
	 * Searchable attributes cache
	 *
	 * @var array
	 */
	protected $searchableAttributes = array();

	protected $attributeCodeToIdMap = array();

	/**
	 * Product attributes that have fixed schema fields and thus do not need
	 * to be added when processing dynamic fields.
	 *
	 * @var array
	 */
	protected $fixedSchemaFieldAttributes = array('name', 'description', 'meta_keyword', 'price', 'status', 'visibility');

	/**
	 * Product Type Instances cache
	 *
	 * @var array
	 */
	protected $productTypes = array();

	/**
	 * Resource initialization
	 */
	protected function _construct() {
		$this->_setResource('core');
	}

	/**
	 * Rebuild the index for all stores at once or just one specific store.
	 *
	 * @param int|null $storeId Store to re-index
	 * @param int|array|null $productIds List of specific products to re-index
	 */
	public function rebuildIndex($storeId = null, $productIds = null)
	{
		if (is_null($storeId)) {
			// re-index all stores
			$storeIds = array_keys(Mage::app()->getStores());
			foreach ($storeIds as $storeId) {
				$this->rebuildStoreIndex($storeId, $productIds);
				Mage::helper('solr/connectionManager')->getConnectionByStore($storeId)->commit();
			}
		} else {
			// re-index specific store
			$this->rebuildStoreIndex($storeId, $productIds);
			Mage::helper('solr/connectionManager')->getConnectionByStore($storeId)->commit();
		}
	}

	protected function rebuildStoreIndex($storeId, $productIds = null)
	{
		$staticFields = array();
		foreach ($this->getSearchableAttributesByType('static') as $attribute) {
			/** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
			$staticFields[] = $attribute->getAttributeCode();
		}

		$dynamicFields = array(
			'int'      => array_keys($this->getSearchableAttributesByType('int')),
			'varchar'  => array_keys($this->getSearchableAttributesByType('varchar')),
			'text'     => array_keys($this->getSearchableAttributesByType('text')),
			'decimal'  => array_keys($this->getSearchableAttributesByType('decimal')),
			'datetime' => array_keys($this->getSearchableAttributesByType('datetime')),
		);

		// status and visibility
		$visibility       = $this->getSearchableAttribute('visibility');
		$visibilityValues = Mage::getSingleton('catalog/product_visibility')->getVisibleInSearchIds();
		$status           = $this->getSearchableAttribute('status');
		$statusValues     = Mage::getSingleton('catalog/product_status')->getVisibleStatusIds();

		$lastProductId = 0;
		while (true) {
			$products = $this->getSearchableProducts($storeId, $staticFields, $productIds, $lastProductId);
			if (!$products) {
				// everything indexed, stop
				break;
			}

			$productAttributes = array();
			$productRelations  = array();
			$productDocuments  = array();

			foreach ($products as $productData) {
				$lastProductId = $productData['entity_id'];
				$productAttributes[$productData['entity_id']] = $productData['entity_id'];
				$productChildren = $this->getProductChildIds($productData['entity_id'], $productData['type_id']);
				$productRelations[$productData['entity_id']] = $productChildren;

				if ($productChildren) {
					foreach ($productChildren as $productChildId) {
						$productAttributes[$productChildId] = $productChildId;
					}
				}
			}


			$productAttributes = $this->getProductAttributes($storeId, $productAttributes, $dynamicFields);
			foreach ($products as $productData) {
				if (!isset($productAttributes[$productData['entity_id']])) {
					continue;
				}

				$singleProductAttributes = $productAttributes[$productData['entity_id']];
				if (!isset($singleProductAttributes[$visibility->getId()])
					|| !in_array($singleProductAttributes[$visibility->getId()], $visibilityValues)
				) {
					continue;
				}

				if (!isset($singleProductAttributes[$status->getId()])
					|| !in_array($singleProductAttributes[$status->getId()], $statusValues)
				) {
					continue;
				}

				// FIXME find better name for $productIndex
#				$productIndex = array($productData['entity_id'] => $singleProductAttributes);


				// convert numeric product attribute IDs keys to names



#				if ($productChildren = $productRelations[$productData['entity_id']]) {
#					foreach ($productChildren as $productChildId) {
#						if (isset($productAttributes[$productChildId])) {
#							$productIndex[$productChildId] = $productAttributes[$productChildId];
#						}
#					}
#				}

				$productDocument = $this->buildProductDocument($storeId, $productData['entity_id'], $singleProductAttributes);
				$productDocuments[] = $productDocument;
			}

			$this->addProductDocuments($storeId, $productDocuments);
		}
	}


	protected function buildProductDocument($storeId, $productId, $searchableAttributes)
	{
		$helper = Mage::helper('solr');

		$searchableAttributes = $this->getNamedProductAttributes($searchableAttributes);
		$product              = Mage::getModel('catalog/product')
			->setStoreId($storeId)
			->load($productId); /** @var Mage_Catalog_Model_Product $product */

		$baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
		$host    = parse_url($baseUrl, PHP_URL_HOST);


		$document = new Apache_Solr_Document();

		$document->setField('appKey',    'Asm_Solr');
		$document->setField('type',      'catalog/product');

		$document->setField('id',        $helper->getProductDocumentId($product->getEntityId()));
		$document->setField('site',      $host);
		$document->setField('siteHash',  $helper->getSiteHashForDomain($host));

		$document->setField('created',   $helper->dateToIso($product->getCreatedAt()));
		$document->setField('changed',   $helper->dateToIso($product->getUpdatedAt()));

		$document->setField('sku',       $product->getSku());
		$document->setField('productId', $product->getEntityId());
		$document->setField('storeId',   $storeId);

		$categoryIds = $product->getCategoryIds();
		foreach ($categoryIds as $categoryId) {
			$document->addField('categoryId', $categoryId);
		}

		$document->setField('isSalable', $product->isSalable());
		$document->setField('inStock',   $product->isInStock());
		$document->setField('isVisible', $product->getStatus());
		$document->setField('isVisibleInCatalog', $product->isVisibleInCatalog());

		$document->setField('title',     $product->getName());
		$document->setField('content',   $product->getDescription());
		$document->setField('keywords',  $helper->trimExplode(',', $product->getMetaKeyword(), true));
		$document->setField('url',       $product->getProductUrl());

		$document->setField('price',     $product->getPrice());

		if ($product->getManufacturer()) {
			$document->setField('manufacturer', $product->getAttributeText('manufacturer'));
		}

		$document->setField('image_stringS',       $product->getImage());
		$document->setField('small_image_stringS', $product->getSmallImage());
		$document->setField('thumbnail_stringS',   $product->getThumbnail());

		$productType = $product->getTypeId();
		$document->setField('type_id_stringS', $productType);

		if ($productType == 'configurable') {
			$childProductAttributes = $this->getConfigurableProductChildProductAttributes($product);
			$searchableAttributes = array_merge($searchableAttributes, $childProductAttributes);
		}

		// add other searchable attributes as dynamic fields
		foreach ($searchableAttributes as $attributeCode => $attributeValue) {
			if (empty($attributeValue) // don't index empty values (for now), might result in type conflicts
			|| in_array($attributeCode, $this->fixedSchemaFieldAttributes)) { // don't add fixed schema fields twice
				continue;
			}

			// single or multivalue field type
			// default to single value, use multivalue for arrays
			$multiValue = false;
			if (is_array($attributeValue)) {
				$multiValue = true;
			}

			$attribute = Mage::getSingleton('eav/config')
				->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attributeCode);

			if ($attribute->getBackendType() == 'datetime') {
				$attributeValue = $helper->dateToIso($attributeValue);
			}
			$document->setField(
				$helper->getFieldNameByAttribute($attribute, $multiValue),
				$attributeValue
			);
		}

		return $document;
	}

	protected function getConfigurableProductChildProductAttributes($product) {
		$childProductAttributes = array();
		$configurableProduct = Mage::getModel('catalog/product_type_configurable')->setProduct($product);

		$associatedProducts     = $configurableProduct->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();
		$superProductAttributes = $configurableProduct->getUsedProductAttributes();

		foreach ($associatedProducts as $simpleProduct) {
			// find/iterate over attributes that make a product configurable
			// add attribute values to super product attributes
			foreach ($superProductAttributes as $attribute) {
				$attributeCode = $attribute->attribute_code;
				if (!array_key_exists($attributeCode, $childProductAttributes)) {
					$childProductAttributes[$attributeCode] = array();
				}

				if (!in_array($simpleProduct->{$attributeCode}, $childProductAttributes[$attributeCode])) {
					$childProductAttributes[$attributeCode][] = $simpleProduct->{$attributeCode};
				}
			}
		}

		return $childProductAttributes;
	}


	protected function getSearchableAttributes()
	{
		if (empty($this->searchableAttributes)) {
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

			$this->searchableAttributes = $attributes;
		}

		return $this->searchableAttributes;
	}

	protected function getSearchableAttributesByType($type)
	{
		$typedAttributes      = array();
		$searchableAttributes = $this->getSearchableAttributes();

		foreach ($searchableAttributes as $attributeId => $attribute) {
			if ($attribute->getBackendType() == $type) {
				$typedAttributes[$attributeId] = $attribute;
			}
		}

		return $typedAttributes;
	}

	protected function getSearchableAttribute($attributeName)
	{
		$searchableAttribute = Mage::getSingleton('eav/config')
			->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attributeName);

		$attributes = $this->getSearchableAttributes();
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

	protected function getSearchableProducts($storeId, array $staticFields, $productIds = null, $lastProductId = 0, $limit = 100)
	{
		$websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
		$readAdapter = $this->_getReadAdapter();

		$select = $readAdapter->select()
			->useStraightJoin(true)
			->from(
				array('e' => $this->getTable('catalog/product')),
				array_merge(array('entity_id', 'type_id'), $staticFields)
			)
			->join(
				array('website' => $this->getTable('catalog/product_website')),
				$readAdapter->quoteInto(
					'website.product_id = e.entity_id AND website.website_id = ?',
					$websiteId
				),
				array()
			)
			->join(
				array('stock_status' => $this->getTable('cataloginventory/stock_status')),
				$readAdapter->quoteInto(
					'stock_status.product_id = e.entity_id AND stock_status.website_id = ?',
					$websiteId
				),
				array('in_stock' => 'stock_status')
			);

		if (!is_null($productIds)) {
			$select->where('e.entity_id IN(?)', $productIds);
		}

		$select->where('e.entity_id > ?', $lastProductId)
			->limit($limit)
			->order('e.entity_id');

		$result = $readAdapter->fetchAll($select);

		return $result;
	}

	protected function getProductChildIds($productId, $typeId)
	{
		$typeInstance = $this->getProductTypeInstance($typeId);
		$relation = $typeInstance->isComposite()
			? $typeInstance->getRelationInfo()
			: false;

		if ($relation && $relation->getTable() && $relation->getParentFieldName() && $relation->getChildFieldName()) {
			$select = $this->_getReadAdapter()->select()
				->from(
					array('main' => $this->getTable($relation->getTable())),
					array($relation->getChildFieldName()))
				->where("{$relation->getParentFieldName()} = ?", $productId);
			if (!is_null($relation->getWhere())) {
				$select->where($relation->getWhere());
			}
			return $this->_getReadAdapter()->fetchCol($select);
		}

		return null;
	}

	protected function getProductTypeInstance($typeId)
	{
		if (!isset($this->productTypes[$typeId])) {
			$productEmulator = $this->getProductEmulator();
			$productEmulator->setTypeId($typeId);

			$this->productTypes[$typeId] = Mage::getSingleton('catalog/product_type')
				->factory($productEmulator);
		}

		return $this->productTypes[$typeId];
	}

	/**
	 * Retrieve Product Emulator (Varien Object)
	 *
	 * @return Varien_Object
	 */
	protected function getProductEmulator()
	{
		$productEmulator = new Varien_Object();
		$productEmulator->setIdFieldName('entity_id');

		return $productEmulator;
	}

	protected function getProductAttributes($storeId, array $productIds, array $attributeTypes)
	{
		$result  = array();
		$selects = array();
		$writeAdapter = $this->_getWriteAdapter();
		$ifStoreValue = $writeAdapter->getCheckSql('t_store.value_id > 0', 't_store.value', 't_default.value');

		foreach ($attributeTypes as $backendType => $attributeIds) {
			if ($attributeIds) {
				$tableName = $this->getTable(array('catalog/product', $backendType));
				$selects[] = $writeAdapter->select()
					->from(
						array('t_default' => $tableName),
						array('entity_id', 'attribute_id'))
					->joinLeft(
						array('t_store' => $tableName),
						$writeAdapter->quoteInto(
							't_default.entity_id=t_store.entity_id' .
							' AND t_default.attribute_id=t_store.attribute_id' .
							' AND t_store.store_id=?',
							$storeId),
						array('value' => $this->unifyField($ifStoreValue, $backendType)))
					->where('t_default.store_id=?', 0)
					->where('t_default.attribute_id IN (?)', $attributeIds)
					->where('t_default.entity_id IN (?)', $productIds);
			}
		}

		if (!empty($selects)) {
			$select = $writeAdapter->select()->union($selects, Zend_Db_Select::SQL_UNION_ALL);
			$query = $writeAdapter->query($select);
			while ($row = $query->fetch()) {
				$result[$row['entity_id']][$row['attribute_id']] = $row['value'];
			}
		}

		return $result;
	}

	protected function unifyField($field, $backendType = 'varchar')
	{
		if ($backendType == 'datetime') {
			$expr = Mage::getResourceHelper('solr')->castField(
				$this->_getReadAdapter()->getDateFormatSql($field, '%Y-%m-%d %H:%i:%s'));
		} else {
			$expr = Mage::getResourceHelper('solr')->castField($field);
		}

		return $expr;
	}



	///// ///// attribute name to id map ///// /////


	protected function getAttributeCodeToIdMap()
	{
		if (empty($this->attributeCodeToIdMap)) {
			$attributeNameToIdMap = array();
			$searchableAttributes = $this->getSearchableAttributes();

			foreach ($searchableAttributes as $attributeId => $attribute) {
				$attributeCode = $attribute->getAttributeCode();
				$attributeNameToIdMap[$attributeCode] = $attributeId;
			}

			$this->attributeCodeToIdMap = $attributeNameToIdMap;
		}

		return $this->attributeCodeToIdMap;
	}

	protected function getNamedProductAttributes(array $productAttributes)
	{
		$namedAttributes = array();

		if (empty($this->attributeCodeToIdMap)) {
			$this->getAttributeCodeToIdMap();
		}

		foreach ($productAttributes as $attributeId => $attributeValue) {
			list($attributeCode) = array_keys($this->attributeCodeToIdMap, $attributeId);
			$namedAttributes[$attributeCode] = $attributeValue;
		}

		return $namedAttributes;
	}



	///// ///// write to Solr



	protected function addProductDocuments($storeId, $productDocuments = array()) {
		$connection = Mage::helper('solr/connectionManager')->getConnectionByStore($storeId);
		$connection->addDocuments($productDocuments);
	}

}