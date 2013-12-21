<?php


/**
 * Search Resource
 *
 * @category Asm
 * @package Asm_Solr
 * @author Ingo Renner <ingo@infielddesign.com>
 */

class Asm_Solr_Model_Resource_Search
{

	/**
	 * An instance of the Solr service
	 *
	 * @var Asm_Solr_Model_Solr_Connection
	 */
	protected $connection = NULL;

	/**
	 * The search query
	 *
	 * @var Asm_Solr_Model_Solr_Query
	 */
	protected $query = NULL;

	/**
	 * The search response
	 *
	 * @var Apache_Solr_Response
	 */
	protected $response = NULL;

	/**
	 * Flag for marking a search
	 *
	 * @var boolean
	 */
	protected $hasSearched = FALSE;


	public function __construct(array $searchParameters = array())
	{
		if (isset($searchParameters['connection'])) {
			$this->connection = $searchParameters['connection'];
		} else {
			$this->connection = Mage::helper('solr/connectionManager')->getConnection();
		}
	}

	/**
	 * Executes a query against a Solr server.
	 *
	 * 1) Gets the query string
	 * 2) Conducts the actual search
	 * 3) Checks debug settings
	 *
	 * @param Asm_Solr_Model_Solr_Query $query The query with keywords, filters, and so on.
	 * @param integer $offset Result offset for pagination.
	 * @param integer $limit Maximum number of results to return. If set to NULL, this value is taken from the query object.
	 * @return Apache_Solr_Response Solr response
	 */
	public function search(Asm_Solr_Model_Solr_Query $query, $offset = 0, $limit = 10) {
		$this->query = $query;

		if (empty($limit)) {
			$limit = $query->getResultsPerPage();
		}

		try {
			$response = $this->connection->search(
				$query->getKeywords(),
				$offset,
				$limit,
				$query->getQueryParameters()
			);
		} catch (RuntimeException $e) {
			$response = $this->connection->getResponse();
		}

		$this->response    = $response;
		$this->hasSearched = TRUE;

		return $this->response;
	}

}