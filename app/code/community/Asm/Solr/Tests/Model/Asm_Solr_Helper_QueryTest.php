<?php

class Asm_Solr_Helper_QueryTest extends PHPUnit_Framework_TestCase {


	/**
	 * @test
	 */
	public function addFilterAddsFilter() {
		$query = new Asm_Solr_Helper_Query('test');
		$this->assertAttributeEmpty('filters');
	}


}
 