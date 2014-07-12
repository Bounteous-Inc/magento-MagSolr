<?php

$installer = $this;
/* @var $this Mage_Core_Model_Resource_Setup */
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();


$table = $installer->getConnection()
	->newTable($installer->getTable('solr/indexqueue_file'))
	->addColumn('file_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'identity' => true,
		'unsigned' => true,
		'nullable' => false,
		'primary'  => true,
		), 'File ID')
	->addColumn('cms_page_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
		'unsigned' => true,
		'nullable' => false,
		'default'  => '0',
		), 'CMS page ID')
	->addColumn('file_path', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
		'nullable' => false,
		), 'File path')
	->addColumn('indexed', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
		'unsigned' => true,
		'nullable' => false,
		'default'  => '0',
		), 'File indexed at')
	->setComment('Keeps track of which files have been indexed for which page');
$installer->getConnection()->createTable($table);


$installer->endSetup();

