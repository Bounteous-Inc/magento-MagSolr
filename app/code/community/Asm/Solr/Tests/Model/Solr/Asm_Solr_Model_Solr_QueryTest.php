<?php

include __DIR__ . '/../../../../../../../Mage.php';




class Asm_Solr_Model_Solr_QueryTest extends PHPUnit_Framework_TestCase {

	public function setUp()
	{
		Mage::app('default');
	}

	/**
	 * @test
	 */
	public function addFilterAddsFilter()
	{
#		$query = Mage::getModel('solr/query', 'test');
#		$this->assertEmpty($query->getFilters());
	}


}
 