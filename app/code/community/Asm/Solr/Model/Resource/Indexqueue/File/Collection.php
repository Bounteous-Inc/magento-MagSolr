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
 * Index Queue file collection
 *
 * @category Asm
 * @package Asm_Solr
 * @author Ingo Renner <ingo@infielddesign.com>
 */
class Asm_Solr_Model_Resource_Indexqueue_File_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

	/**
	 * Init model for collection
	 *
	 */
	protected function _construct()
	{
		$this->_init('solr/indexqueue_file');
	}

}