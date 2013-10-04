<?php

/**
 * Solr Connection
 *
 * @category Asm
 * @package Asm_Solr
 * @author Ingo Renner <ingo@infielddesign.com>
 */
class Asm_Solr_Model_SolrConnection extends Apache_Solr_Service {

	const LUKE_SERVLET    = 'admin/luke';
	const PLUGINS_SERVLET = 'admin/plugins';

	const SCHEME_HTTP  = 'http';
	const SCHEME_HTTPS = 'https';

	/**
	 * Server connection scheme. http or https.
	 *
	 * @var string
	 */
	protected $_scheme = self::SCHEME_HTTP;

	/**
	 * Constructed servlet URL for Luke
	 *
	 * @var string
	 */
	protected $_lukeUrl;

	/**
	 * Constructed servlet URL for plugin information
	 *
	 * @var string
	 */
	protected $_pluginsUrl;

	/**
	 * Response cache, holds the last response
	 *
	 * @var Apache_Solr_Response
	 */
	protected $responseCache = NULL;

	/**
	 * Indicator whether a search has been executed already.
	 *
	 * @var bool
	 */
	protected $hasSearched = FALSE;

	protected $lukeData       = array();
	protected $systemData     = NULL;

	protected $schemaName     = NULL;
	protected $solrconfigName = NULL;


	/**
	 * Constructor for class tx_solr_SolrService.
	 *
	 * @param string $host Solr host
	 * @param string $port Solr port
	 * @param string $path Solr path
	 * @param string $scheme Scheme, defaults to http, can be https
	 */
	public function __construct($host = '', $port = '8080', $path = '/solr/', $scheme = 'http') {
		$this->setScheme($scheme);

		parent::__construct($host, $port, $path);
	}

	/**
	 * initializes various URLs, including the Luke URL
	 *
	 * @return void
	 */
	protected function _initUrls() {
		parent::_initUrls();

		$this->_lukeUrl = $this->_constructUrl(
			self::LUKE_SERVLET,
			array(
				'numTerms' => '0',
				'wt' => self::SOLR_WRITER
			)
		);

		$this->_pluginsUrl  = $this->_constructUrl(
			self::PLUGINS_SERVLET,
			array('wt' => self::SOLR_WRITER)
		);
	}

	/**
	 * Generates a valid URL given this server's scheme, host, port, path
	 * and servlet name.
	 *
	 * @param string $servlet Servlet name
	 * @param array $params Additional URL parameters to attach to the end of the URL
	 * @return string Servlet URL
	 */
	protected function _constructUrl($servlet, $params = array()) {
		$url = parent::_constructUrl($servlet, $params);

		if (!(strpos($url, $this->_scheme) === 0)) {
			$parsedUrl = parse_url($url);

			// unfortunately can't use str_replace as it replaces all
			// occurrences of $needle and can't be limited to replace only once
			$url = $this->_scheme . substr($url, strlen($parsedUrl['scheme']));
		}

		return $url;
	}

	/**
	 * Performs a search.
	 *
	 * @param string $query query string / search term
	 * @param integer $offset result offset for pagination
	 * @param integer $limit number of results to retrieve
	 * @param array $params additional HTTP GET parameters
	 * @return Apache_Solr_Response Solr response
	 * @throws RuntimeException if Solr returns a HTTP status code other than 200
	 */
	public function search($query, $offset = 0, $limit = 10, $params = array()) {
		$response = parent::search($query, $offset, $limit, $params);
		$this->hasSearched = TRUE;

		$this->responseCache = $response;

		if ($response->getHttpStatus() != 200) {
			throw new RuntimeException(
				'Invalid query. Solr returned an error: '
				. $response->getHttpStatus() . ' '
				. $response->getHttpStatusMessage(),
				1293109870
			);
		}

		return $response;
	}

	/**
	 * Returns the set scheme
	 *
	 * @return string Scheme, http or https
	 */
	public function getScheme() {
		return $this->_scheme;
	}

	/**
	 * Set the scheme/protocol to use for request. If empty will fallback to constants.
	 *
	 * @param string $scheme Either http or https
	 * @throws UnexpectedValueException
	 */
	public function setScheme($scheme) {
		// Use the provided scheme or use the default
		if (empty($scheme)) {
			throw new UnexpectedValueException('Scheme parameter is empty', 1380756390);
		} else {
			if (in_array($scheme, array(self::SCHEME_HTTP, self::SCHEME_HTTPS))) {
				$this->_scheme = $scheme;
			} else {
				throw new UnexpectedValueException('Unsupported scheme parameter, scheme must be http or https', 1380756442);
			}
		}

		if ($this->_urlsInited) {
			$this->_initUrls();
		}
	}

	/**
	 * Retrieves meta data about the index from the luke request handler
	 *
	 * @param integer Number of top terms to fetch for each field
	 * @return array An array of index meta data
	 */
	public function getLukeMetaData($numberOfTerms = 0) {
		if (!isset($this->lukeData[$numberOfTerms])) {
			$lukeUrl = $this->_constructUrl(
				self::LUKE_SERVLET,
				array(
					'numTerms' => $numberOfTerms,
					'wt'       => self::SOLR_WRITER
				)
			);

			$this->lukeData[$numberOfTerms] = $this->_sendRawGet($lukeUrl);
		}

		return $this->lukeData[$numberOfTerms];
	}

	/**
	 * get field meta data for the index
	 *
	 * @param integer Number of top terms to fetch for each field
	 * @return array
	 */
	public function getFieldsMetaData($numberOfTerms = 0) {
		return $this->getLukeMetaData($numberOfTerms)->fields;
	}

	/**
	 * Returns whether a search has been executed or not.
	 *
	 * @return bool TRUE if a search has been executed, FALSE otherwise
	 */
	public function hasSearched() {
		return $this->hasSearched;
	}

	/**
	 * Gets the most recent response (if any)
	 *
	 * @return Apache_Solr_Response Most recent response, or NULL if a search has not been executed yet.
	 */
	public function getResponse() {
		return $this->responseCache;
	}

	/**
	 * Gets information about the Solr server
	 *
	 * @return array A nested array of system data.
	 */
	public function getSystemInformation() {

		if (empty($this->systemData)) {
			$systemInformation = $this->system();

			// access a random property to trigger response parsing
			$systemInformation->responseHeader;
			$this->systemData = $systemInformation;
		}

		return $this->systemData;
	}

	/**
	 * Gets the name of the schema.xml file installed and in use on the Solr
	 * server.
	 *
	 * @return string Name of the active schema.xml
	 */
	public function getSchemaName() {
		if (is_null($this->schemaName)) {
			$systemInformation = $this->getSystemInformation();
			$this->schemaName = $systemInformation->core->schema;
		}

		return $this->schemaName;
	}

	/**
	 * Gets the name of the solrconfig.xml file installed and in use on the Solr
	 * server.
	 *
	 * @return string Name of the active solrconfig.xml
	 */
	public function getSolrconfigName() {
		if (is_null($this->solrconfigName)) {
			$solrconfigXmlUrl = $this->_scheme . '://'
				. $this->_host . ':' . $this->_port
				. $this->_path . 'admin/file/?file=solrconfig.xml';

			$solrconfigXml = simplexml_load_file($solrconfigXmlUrl);
			$this->solrconfigName = (string) $solrconfigXml->attributes()->name;
		}

		return $this->solrconfigName;
	}

	/**
	 * Gets the Solr server's version number.
	 *
	 * @return string Solr version number
	 */
	public function getSolrServerVersion() {
		$systemInformation = $this->getSystemInformation();

		// don't know why $systemInformation->lucene->solr-spec-version won't work
		$luceneInformation = (array) $systemInformation->lucene;
		return $luceneInformation['solr-spec-version'];
	}

	/**
	 * Deletes all index documents of a certain type and does a commit
	 * afterwards.
	 *
	 * @param string $type The type of documents to delete, usually a table name.
	 * @param boolean $commit Will commit immediately after deleting the documents if set, defaults to TRUE
	 */
	public function deleteByType($type, $commit = TRUE) {
		$this->deleteByQuery('type:' . trim($type));

		if ($commit) {
			$this->commit(FALSE, FALSE, FALSE);
		}
	}

}
