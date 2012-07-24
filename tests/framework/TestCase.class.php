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
}