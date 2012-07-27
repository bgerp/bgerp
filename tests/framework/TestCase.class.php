<?php
class framework_TestCase extends PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
	    parent::setUp();
	    
	    /* @var $Packs core_Packs */
		$Packs = cls::get('core_Packs');
		$Packs->checkSetup();
	}
	
	protected function assertArrayMatch($expected, $actual)
	{
	    $expected = (array)$expected;
	    $actual   = (array)$actual;
	    
	    foreach ($actual as $key => $val) {
	        if (!array_key_exists($key, $expected)) {
	            unset($actual[$key]);
	        }
	    }
	    
	    return $this->assertEquals($expected, $actual);
	}
}