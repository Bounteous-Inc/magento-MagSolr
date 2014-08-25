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



class Asm_Solr_Model_Observer_Index_Page {

    /**
     * @param Varien_Event_Observer $event
     */
    public function trackFiles($event)
	{
		$page             = $event->getPage();
		$storeId          = $event->getStoreId();
		$pageId           = $page->getId();
		$pageContent      = $page->getContent();
		$links            = array();
		$allowedFileTypes = $this->getAllowedFileTypesRegex();

		preg_match_all('/<a href=\"\/([^\"]*)\.'.$allowedFileTypes.'\".*>.*<\/a>/iU', $pageContent, $links);

		if (empty($links[0])) {
			// bail out if we didn't find any file links, save a couple CPU cycles
			return;
		}

		$foundFiles   = array();
		$trackedFiles = $this->getTrackedFilesForPage($pageId);

		$numberOfFiles = count($links[0]);
		for ($i = 0; $i < $numberOfFiles; $i++) {
			$foundFiles[] = '/' . htmlspecialchars_decode($links[1][$i]) . '.' . $links[2][$i];
		}

		$addedFiles   = array_diff($foundFiles, $trackedFiles);
		$removedFiles = array_diff($trackedFiles, $foundFiles);

		$this->addFilesToTracking($storeId, $pageId, $addedFiles);
		$this->removeFilesFromTracking($storeId, $pageId, $removedFiles);
	}

	protected function addFilesToTracking($storeId, $pageId, $addedFiles)
	{
		foreach ($addedFiles as $addedFile) {
			$indexQueueFile = Mage::getModel('solr/indexqueue_file')->setData(array(
				'cms_page_id' => $pageId,
				'store_id'    => $storeId,
				'file_path'   => $addedFile,
				'changed'     => time(),
				'indexed'     => 0
			));
			$indexQueueFile->save();
		}
	}

	protected function removeFilesFromTracking($storeId, $pageId, $removedFiles)
	{

		// FIXME must filter by pageId, possibly also storeId
		//       file might still be linked from another page

		foreach ($removedFiles as $removedFile) {
			$indexQueueFile = Mage::getModel('solr/indexqueue_file')->load(
				$removedFile,
				'file_path'
			);
			$indexQueueFile->delete();
		}
	}

	protected function getAllowedFileTypesRegex() {
		$fileTypes = Mage::getStoreConfig('index/files/file_types');

		$fileTypes = trim($fileTypes);
		$fileTypes = str_replace(' ', '', $fileTypes);
		$fileTypes = str_replace(',', '|', $fileTypes);

		return "($fileTypes)";
	}

	protected function getTrackedFilesForPage($pageId)
	{
		$trackedFiles = Mage::getModel('solr/indexqueue_file')
			->getCollection()
			->addFieldToFilter('cms_page_id', $pageId);

		$trackedFiles = $trackedFiles->getColumnValues('file_path');

		return $trackedFiles;
	}

}