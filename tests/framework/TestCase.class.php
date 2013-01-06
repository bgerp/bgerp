<?php
class framework_TestCase extends PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
	    parent::setUp();
	     
	    /* @var $BgerpSetup bgerp_Setup */
		$BgerpSetup = cls::get('bgerp_Setup');
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
    
    
	/**
	 * Зареждане тестови данни.
	 * 
	 * Всички съществуващи данни на моделите биват заличени.
	 * 
	 * @param array $fixtureData тестови данни. Структурата на масива е
	 * 
	 * array(
	 *     "име_МVC1" => array(
	 *         array( ... 'запис на MVC1' ... ),
	 *         array( ... 'запис на MVC1' ... ),
	 *         ...
	 *     ),
	 *     "име_МVC2" => array(
	 *         array( ... 'запис на MVC2' ... ),
	 *         array( ... 'запис на MVC2' ... ),
	 *         ...
	 *     ),
	 *     ...
	 * )
	 */
    protected function loadFixtureData($fixtureData)
    {
        foreach (array_keys($fixtureData) as $mvc) {
            $this->truncate($mvc);
        }
        
        foreach ($fixtureData as $mvcName => $data) {
            $mvc = cls::get($mvcName);
            
            foreach ($data as $r) {
                try {
                    $mvc->save((object)$r);
                } catch (core_exception_Expect $ex) {
                    var_dump($ex->args());
                }
            }
        }
    }
    
    /**
     * Изчиства всички данни на модел
     * 
     * @param string|core_Mvc $mvc
     */
    protected function truncate($mvc)
    {
        $mvc = cls::get($mvc);
        
        $mvc->db->query("TRUNCATE TABLE `{$mvc->dbTableName}`");
    }
}