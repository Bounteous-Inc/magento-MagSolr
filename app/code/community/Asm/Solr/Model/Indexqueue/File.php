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
 * Model for files to index linked on pages
 *
 * @category Asm
 * @package Asm_Solr
 * @author Ingo Renner <ingo@infielddesign.com>
 *
 * @method string getFilePath()
 * @method integer getCmsPageId()
 * @method integer getStoreId()
 * @method integer getIndexed()
 * @method integer getChanged()
 */
class Asm_Solr_Model_Indexqueue_File extends Mage_Core_Model_Abstract {

	const ENTITY = 'indexqueue_file';

	/**
	 * File extensions supported to extract text from.
	 *
	 * Since we're using Tika, this list is actually a lot longer but this will
	 * keep life easy for now.
	 *
	 * @var array
	 */
	protected $supportedTextFileExtensions = array('pdf', 'doc', 'docx', 'txt', 'odf');

	/**
	 * Prefix of model events names
	 *
	 * @var string
	 */
	protected $_eventPrefix = self::ENTITY;

	/**
	 * Parameter name in event
	 *
	 * In observe method you can use $observer->getEvent()->getIndexqueueFile() in this case
	 *
	 * @var string
	 */
	protected $_eventObject = self::ENTITY;

	/**
	 * Internal cache in case the content is requested multiple times
	 *
	 * @var string
	 */
	private $content = null;


	protected function _construct()
	{
		$this->_init('solr/indexqueue_file');
	}

	/**
	 * Tries to return the file's content if it's a text file or the file's
	 * textual representation otherwise.
	 *
	 * @return string The file's text content
	 */
	public function getContent()
	{
		if (is_null($this->content)) {
			$fileContent = '';
			$mimeType    = $this->getMimeType();

			if ($mimeType == 'text/plain') {
					// we can read text files directly
				$fileContent = file_get_contents($this->getFileAbsolutePath());
			} else if (in_array($this->getExtension(), $this->supportedTextFileExtensions)) {
				$fileContent = $this->extractContent();
			} else {
				$fileContent = '';
			}

			$this->content = Mage::helper('solr/contentExtractor')->cleanContent($fileContent);
		}

		return $this->content;
	}

	/**
	 * Extracts textual content from a file using Solr's extract response handler
	 *
	 * @return string The file's text content
	 * @throws Apache_Solr_InvalidArgumentException
	 */
	protected function extractContent()
	{
		$solr     = Mage::helper('solr/connectionManager')->getConnection();
		$response = $solr->extract($this->getFileAbsolutePath(), array(
			'resource.name' => 'file_content',
			'extractOnly'   => 'true',
			'extractFormat' => 'text'
		));

		$content = $response->_empty_;
		//$metadata = (array) $response->null_metadata;

		return $content;
	}

	/**
	 * Determines the Internet Media Type, or MIME type.
	 *
	 * @return string The file's MIME type.
	 */
	public function getMimeType()
	{
		$mimeType = '';

		if (function_exists('finfo_file')) {
			$fileInfo = new finfo(FILEINFO_MIME_TYPE);
			if ($fileInfo) {
				$mimeType = $fileInfo->file($this->getFileAbsolutePath());
			}
		} else {
			$mimeType = mime_content_type($this->getFileAbsolutePath());
		}

		return $mimeType;
	}

	/**
	 * Gets the file's basename
	 *
	 * @return string
	 */
	public function getName()
	{
		return basename($this->getFilePath());
	}

	/**
	 * Gets the file's extension
	 *
	 * @return string
	 */
	public function getExtension()
	{
		$filePath = $this->getFilePath();
		$fileName = basename($filePath);

		$fileNameParts = explode('.', $fileName);

		return array_pop($fileNameParts);
	}

	public function getUrl()
	{
		return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . substr($this->getFilePath(), 1);
	}

	/**
	 * Gets the file's absolute path
	 *
	 * @return string
	 */
	public function getFileAbsolutePath()
	{
		return Mage::getBaseDir('base') . $this->getFilePath();
	}

	/**
	 * Gets the (unix)time the file was last modified.
	 *
	 * @return integer Unix timestamp of the last modification of the file
	 */
	public function getFileLastChangedTime()
	{
		return filemtime($this->getFileAbsolutePath());
	}

}