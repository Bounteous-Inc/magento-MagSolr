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
			}
		} else {
			// re-index specific store
			$this->rebuildStoreIndex($storeId, $productIds);
		}

		// FIXME use getConnectionByStoreId($storeId)
		// FIXME move into if/else/loop above
		$connection = Mage::helper('solr/connectionManager')->getConnection();
		/** @var $connection Asm_Solr_Model_Solr_Connection */

		$connection->commit();
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
				$productIndex = array($productData['entity_id'] => $singleProductAttributes);

				if ($productChildren = $productRelations[$productData['entity_id']]) {
					foreach ($productChildren as $productChildId) {
						if (isset($productAttributes[$productChildId])) {
							$productIndex[$productChildId] = $productAttributes[$productChildId];
						}
					}
				}

				$productDocument = $this->buildProductDocument($productData['entity_id'], $storeId);
				$productDocuments[] = $productDocument;
			}

			$this->addProductDocuments($storeId, $productDocuments);
		}
	}


	protected function buildProductDocument($productId, $storeId)
	{
		$helper = Mage::helper('solr');
		/** @var $helper Asm_Solr_Helper_Data */

		// FIXME get rid of ->load(), use $productIndex from rebuildStoreIndex()
		$product  = Mage::getModel('catalog/product')->load($productId);

		$baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
		$host = parse_url($baseUrl, PHP_URL_HOST);

		/* @var $product Mage_Catalog_Model_Product */
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

		$document->setField('inStock',   $product->isInStock());
		$document->setField('isVisible', $product->getStatus());
		$document->setField('isVisibleInCatalog', $product->isVisibleInCatalog());

		$document->setField('title',     $product->getName());
		$document->setField('content',   $product->getDescription());
		$document->setField('keywords',  $product->getMetaKeyword());
		$document->setField('url',       $product->getProductUrl());

		$document->setField('manufacturer', $product->getAttributeText('manufacturer'));
		$document->setField('price',     $product->getPrice());



		// TODO iterate over other searchable/filterable attributes
		// add them as dynamic fields


//		$document->setField('name_stringS', $this->getAttributeByName($singleProduct, 'name'));


		return $document;
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
		// FIXME don't rely on catalogsearch

		if ($backendType == 'datetime') {
			$expr = Mage::getResourceHelper('catalogsearch')->castField(
				$this->_getReadAdapter()->getDateFormatSql($field, '%Y-%m-%d %H:%i:%s'));
		} else {
			$expr = Mage::getResourceHelper('catalogsearch')->castField($field);
		}

		return $expr;
	}



	///// ///// attribute name to id map ///// /////



	protected function getAttributeByName($productIndex, $attributeName)
	{
		$attributeNameToIdMap = $this->getAttributeCodeToIdMap();
		$attributeId          = $attributeNameToIdMap[$attributeName];

		return $productIndex[$attributeId];
	}

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



	///// ///// write to Solr



	protected function addProductDocuments($storeId, $productDocuments = array()) {
		// FIXME use getConnectionByStoreId($storeId)
		$connection = Mage::helper('solr/connectionManager')->getConnection();
		/** @var $connection Asm_Solr_Model_Solr_Connection */

		$connection->addDocuments($productDocuments);
	}

}