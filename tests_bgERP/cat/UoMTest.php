<?php
class cat_UoMTest extends framework_TestCase
{
    /**
     * 
     * @var cat_Uom
     */
    protected $Uom;
    
    protected function setUp()
    {
        parent::setUp();
        
        $this->Uom = cls::get('cat_UoM');
        
        // Зареждаме тестовите данни
        $fixtureData = array(
            'cat_UoM' => array(
        	array('name' => 'метър',   // 1
                  'shortName' => 'м', 
                  'baseUnitId' => NULL,
                  'baseUnitRatio' => 1.00),
        	
        	array('name' => 'сантиметър',  // 2
                  'shortName' => 'см', 
                  'baseUnitId' => 1,
                  'baseUnitRatio' => 0.01),
        	
        	array('name' => 'километър', // 3
                  'shortName' => 'км', 
                  'baseUnitId' => 1,
                  'baseUnitRatio' => 1000.00),
        	
        	array('name' => 'секунда', // 4
                  'shortName' => 'с', 
                  'baseUnitId' => NULL,
                  'baseUnitRatio' => 1.00),
        	
        	array('name' => 'минута', // 5
                  'shortName' => 'мин', 
                  'baseUnitId' => 4,
                  'baseUnitRatio' => 60.00),
        	
        	array('name' => 'час', // 6
                  'shortName' => 'ч', 
                  'baseUnitId' => 4,
                  'baseUnitRatio' => 3600.00),
                
        ));
        
        $this->loadFixtureData($fixtureData);
    }
    
    
    /**
     * Конвертиране стойност при несъществуващи мерки
     * @expectedException core_exception_Expect
     */
    public function testBothNonExisting()
    {
    	$res = $this->Uom->convertValue('100', 15, 16);
    }
    
    
	/**
     * Конвертиране стойност към несъщестуваща мярка
     * @expectedException core_exception_Expect
     */
    public function testOneExisting()
    {
    	$res = $this->Uom->convertValue('100', 15, 1);
    }
    
    
	/**
     * Конвертиране от една мярка в друга, когато двете мерки са
     * от един род: 
     * примерно: метър, сантиметър, километър, дециметър и др.
     */
    public function testConvertRelative()
    {
    	$fromUom = 2; // сантиметър
    	$fromBaseRate = 0.01; // сантиметър към метър
    	$toUom = 3; // километър
    	$toBaseRate = 1000.00; // километър към метър
    	$rate = 0.00001; // сантиметър към километър
    	$converted = 100 * $rate; // конвертирана стойност
    	
    	$res = $this->Uom->convertValue('100', $fromUom, $toUom);
    	$this->assertEquals($converted, $res);
    }
    
    
	/**
     * Конвертиране от основна мярка към нейна производна
     */
    public function testConvertBaseToOtherSimilar()
    {
    	$fromUom = 1; // метър
    	$fromBaseRate = 1.00; // метър към метър
    	$toUom = 3; // километър
    	$toBaseRate = 1000.00; // километър към метър
    	$rate = 0.001; // метър към километър
    	$converted = 100 * $rate; // конвертирана стойност
    	
    	$res = $this->Uom->convertValue('100', $fromUom, $toUom);
    	$this->assertEquals($converted, $res);
    }
    
    
	/**
     * Конвертиране от производна мярка към нейната основна
     */
    public function testConvertOtherSimiralToBase()
    {
    	$fromUom = 3; // километър
    	$fromBaseRate = 1000.00; // километър към метър
    	$toUom = 1; // метър
    	$toBaseRate = 1.00; // метър към метър
    	$rate = 1000.00; // километър към метър
    	$converted = 100 * $rate; // конвертирана стойност
    	
    	$res = $this->Uom->convertValue('100', $fromUom, $toUom);
    	$this->assertEquals($converted, $res);
    }
    
    
	/**
     * Конвертиране от една мярка в друга, когато двете не са 
     * от един тип:
     * примерно: час към метър, секунда към кг  и др.
     * @expectedException core_exception_Expect
     */
    public function testConvertNonRelative()
    {
    	$fromUom = 6; // час
    	$toUom = 3; // километър
    	
    	$res = $this->Uom->convertValue('100', $fromUom, $toUom);
    }
    
    
	/**
     * Конвертиране от една мярка в друга, когато двете не са от
     * един тип:
     * примерно: час към метър, секунда към кг  и др.
     * @expectedException core_exception_Expect
     */
    public function testConvertNonRelative2()
    {
    	$fromUom = 3; // километър
    	$toUom = 6; // час
    	
    	$res = $this->Uom->convertValue('100', $fromUom, $toUom);
    }
    
    
    /**
     * Конвертиране на време
     */
	public function testConvertTime()
    {
    	$fromUom = 6; // час
    	$fromBaseRate = 3600.00; // час към секунда
    	$toUom = 5; // минута
    	$toBaseRate = 60.00; // минута към секунда
    	$rate = 60.00; // час към минута
    	$converted = 100 * $rate; // конвертирана стойност
    	
    	$res = $this->Uom->convertValue('100', $fromUom, $toUom);
    	$this->assertEquals($converted, $res);
    }
}