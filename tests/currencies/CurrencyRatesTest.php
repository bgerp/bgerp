<?php
class currencies_CurrencyRatesTest extends framework_TestCase
{
    /**
     * 
     * @var currency_CurrencyRates
     */
    protected $CurrencyRates;
    
    protected function setUp()
    {
        parent::setUp();
        
        $this->CurrencyRates = cls::get('currency_CurrencyRates');

        // Тестови данни
        $fixtureData = array(
            'currency_Currencies' => array(
                array('code' => 'BGN',), // 1
                array('code' => 'EUR',), // 2
                array('code' => 'RON',), // 3
                array('code' => 'USD',), // 4
                array('code' => 'CHF',), // 5
            ),
            'currency_CurrencyRates' => array(
                
                /*
                 * 01.01.2012
                 */
                array(
                    'currencyId' => 1, // BGN
                    'baseCurrencyId' => 2, // EUR
                    'date' => '2012-01-01',
                    'rate' => 2,
                ),
                array(
                    'currencyId' => 4, // USD
                    'baseCurrencyId' => 2, // EUR
                    'date' => '2012-01-01',
                    'rate' => 1.1,
                ),
                
                /*
                 * 01.01.2013
                 */
                array(
                    'currencyId' => 1, // BGN
                    'baseCurrencyId' => 2, // EUR
                    'date' => '2013-01-01',
                    'rate' => 1.9558,
                ),
                array(
                    'currencyId' => 4, // USD
                    'baseCurrencyId' => 2, // EUR
                    'date' => '2013-01-01',
                    'rate' => 1.3102,
                ),
                array(
                    'currencyId' => 3, // RON
                    'baseCurrencyId' => 2, // EUR
                    'date' => '2013-01-01',
                    'rate' => 4.3,
                ),
                
                /*
                 * 03.01.2013
                 */
                array(
                    'currencyId' => 4, // USD
                    'baseCurrencyId' => 2, // EUR
                    'date' => '2013-01-03',
                    'rate' => 1.2102,
                ),
                array(
                    'currencyId' => 3, // RON
                    'baseCurrencyId' => 2, // EUR
                    'date' => '2013-01-03',
                    'rate' => 4.4203,
                ),
            ),
        );
        
        $this->loadFixtureData($fixtureData);
    }
    
    
    /**
     * Курс, директно записан в БД
     */
    public function testExisting()
    {
        $RON_EUR = 4.4203; // към 03.01.2013 (записано)
        $rate = $this->CurrencyRates->getRateBetween('RON', 'EUR', '03-01-2013');
        $this->assertEquals($RON_EUR, $rate);
    }
    
    
    /**
     * Курс, директно записан в БД но към по-стара от исканата дата
     */
    public function testExistingHistory()
    {
        $USD_EUR = 1.3102; // към 02.01.2013 (наследено от 01.01.2013)
        $rate = $this->CurrencyRates->getRateBetween('USD', 'EUR', '02-01-2013');
        $this->assertEquals(1.3102, $rate);
    }
    
    
    /**
     * Кръстосан курс към дата, за която има данни и за двете валути
     */
    public function testCrossRate()
    {
        $BGN_EUR = 1.9558; // към 1.1.2013
        $RON_EUR = 4.3;    // към 1.1.2013
        $BGN_RON = round($RON_EUR / $BGN_EUR, 4);
        
        $rate = $this->CurrencyRates->getRateBetween('BGN', 'RON', '01-01-2013');
        $this->assertEquals($BGN_RON, $rate);
    }
    
    
    /**
     * Кръстосан курс към дата, за която данните за едната от валутите са със стара дата
     */
    public function testCrossRateHistory()
    {
        $BGN_EUR = 1.9558; // към 3.1.2013 (наследено от 1.1.2013)
        $RON_EUR = 4.4203; // към 3.1.2013 (записано)
        $BGN_RON = round($RON_EUR / $BGN_EUR, 4);
                
        $rate = $this->CurrencyRates->getRateBetween('BGN', 'RON', '03-01-2013');
        $this->assertEquals($BGN_RON, $rate);
    }
    
    
    /**
     * Курса на всяка валута към самата нея винаги е 1 (независимо от данните в БД)
     */
    public function testSameCurrency()
    {
        $rate = $this->CurrencyRates->getRateBetween('CHF', 'CHF', '08-04-2011');
        
        $this->assertEquals(1, $rate);
    }
    
    /**
     * Курс на валута, за която нямаме данни
     * 
     * @expectedException core_exception_Expect
     */
    public function testMissingRate()
    {
        $rate = $this->CurrencyRates->getRateBetween('CHF', 'EUR', '01-01-2013');
    }
}