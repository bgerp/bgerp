<?php
/**
 * catpr_Pricelists test case.
 */
class catpr_PricelistsTest extends PHPUnit_Framework_TestCase
{
	
	/**
	 * @var catpr_Pricelists
	 */
	private $catpr_Pricelists;


	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		
		// TODO Auto-generated catpr_PricelistsTest::setUp()
		

		$this->catpr_Pricelists = new catpr_Pricelists(/* parameters */);
		
//		print_r($this->catpr_Pricelists);
	
	}


	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		// TODO Auto-generated catpr_PricelistsTest::tearDown()
		

		$this->catpr_Pricelists = null;
		
		parent::tearDown();
	}


	/**
	 * Constructs the test case.
	 */
	public function __construct()
	{
		// TODO Auto-generated constructor
	}


	/**
	 * Tests catpr_Pricelists->description()
	 */
	public function testDescription()
	{
		// TODO Auto-generated catpr_PricelistsTest->testDescription()
		$this->markTestIncomplete("description test not implemented");
		
		$this->catpr_Pricelists->description(/* parameters */);
	
	}


	/**
	 * Tests catpr_Pricelists->on_AfterSave()
	 */
	public function testOn_AfterSave()
	{
		// TODO Auto-generated catpr_PricelistsTest->testOn_AfterSave()
		$this->markTestIncomplete("on_AfterSave test not implemented");
		
		$this->catpr_Pricelists->on_AfterSave(/* parameters */);
	
	}

}

