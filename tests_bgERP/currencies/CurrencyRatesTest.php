<?php
/**
 * Мокъп клас за данни от текущия период
 * 
 * @author developer
 *
 */
class acc_Periods
{
    public static function getBaseCurrencyId($date)
    {
        return 1; // BGN (виж фикстурата по-долу)
    }
}

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
        $RON_EUR = round(1 / 4.4203, 4); // към 03.01.2013 (записано)
        
        // Колко евро е 1 румънска лея?
        $rate = $this->CurrencyRates->getRate('2013-01-03', 'RON', 'EUR');
        $this->assertEquals($RON_EUR, $rate);
    }
    
    
    /**
     * Курс, директно записан в БД но към по-стара от исканата дата
     */
    public function testExistingHistory()
    {
        $USD_EUR = round(1 / 1.3102, 4); // към 02.01.2013 (наследено от 01.01.2013)
        
        // Колко евро е 1 долар?
        $rate = $this->CurrencyRates->getRate('2013-01-02', 'USD', 'EUR');
        $this->assertEquals($USD_EUR, $rate);
    }
    
    
    /**
     * Кръстосан курс към дата, за която има данни и за двете валути
     */
    public function testCrossRate()
    {
        $EUR_BGN = 1.9558; // към 1.1.2013
        $EUR_RON = 4.3;    // към 1.1.2013
        $BGN_RON = round($EUR_RON / $EUR_BGN, 4);
        
        $rate = $this->CurrencyRates->getRate('2013-01-01', 'BGN', 'RON');
        $this->assertEquals($BGN_RON, $rate);
    }
    
    
    /**
     * Кръстосан курс към дата, за която данните за едната от валутите са със стара дата
     */
    public function testCrossRateHistory()
    {
        $EUR_BGN = 1.9558; // към 3.1.2013 (наследено от 1.1.2013)
        $EUR_RON = 4.4203; // към 3.1.2013 (записано)
        $BGN_RON = round($EUR_RON / $EUR_BGN, 4);
                
        $rate = $this->CurrencyRates->getRate('2013-01-03', 'BGN', 'RON');
        $this->assertEquals($BGN_RON, $rate);
    }
    
    
	/**
     * Курс, на еврото към друга валута
     */
    public function testEuroToOther()
    {
        $USD_EUR = 1.2102; // към 03.01.2013 (записано)
        $rate = $this->CurrencyRates->getRate('2013-01-23','EUR', 'USD');
        $this->assertEquals($USD_EUR, $rate);
    }
    

    public function testEuroToBgnAndBack()
    {
        $BGN_EUR = 1.9558;
        $EUR_BGN = round(1 / 1.9558, 4);
        
        $rate_BGN_EUR = $this->CurrencyRates->getRate('2013-01-23', 'EUR', 'BGN');
        $rate_EUR_BGN = $this->CurrencyRates->getRate('2013-01-23', 'BGN', 'EUR');
        
        $this->assertEquals($BGN_EUR, $rate_BGN_EUR);
        $this->assertEquals($EUR_BGN, $rate_EUR_BGN);
    }
    
    
    /**
     * Курса на всяка валута към самата нея винаги е 1 (независимо от данните в БД)
     */
    public function testSameCurrency()
    {
        $rate = $this->CurrencyRates->getRate('2011-04-08', 'CHF', 'CHF');
        
        $this->assertEquals(1, $rate);
    }
    
    
    /**
     * Курс на валута, за която нямаме данни
     * 
     * @expectedException core_exception_Expect
     */
    public function testMissingRate()
    {
        $rate = $this->CurrencyRates->getRate('2013-01-01', 'CHF', 'EUR');
    }
    
    
   	/**
     * Курс на валута, за която нямаме данни
     */
    public function testConvertFromEuroLastRecord()
    {
    	$BGN_EUR = '1.9558';
    	$expAmount = round(100 * $BGN_EUR, 2);
    	$amount = $this->CurrencyRates->convertAmount('100', '2013-01-23', 'EUR', 'BGN');
    	$this->assertEquals($expAmount, $amount);
    }
    
    
    /**
     * Конвертираме сума от някаква валута към Евро
     */
	public function testConvertToEuroLastRecord()
    {
    	$EUR_BGN = 1.9558;
    	$expAmount = round(100 / $EUR_BGN, 2);
    	$amount = $this->CurrencyRates->convertAmount(100, '2013-01-23', 'BGN', 'EUR');
    	$this->assertEquals($expAmount, $amount);
    }
    
    
    /**
     * Конвертираме сума в Лева към друга валута
     */
	public function testConvertBGNtoOtherLastRecord()
    {
    	$EUR_BGN = 1.9558; // 01.01.2013
        $EUR_RON = 4.4203; // 01.01.2013
        $BGN_RON = round($EUR_RON / $EUR_BGN, 4);
        $expAmount = round(100 * $BGN_RON, 2);
    	$amount = $this->CurrencyRates->convertAmount(100, '2013-01-23', 'BGN', 'RON');
    	$this->assertEquals($expAmount, $amount);
    }
   
    
    /**
     * Конвертираме сума в някаква валута към Лева
     */
	public function testConvertOtherToBGNLastRecord()
    {
    	$EUR_BGN = 1.9558; // 03.01.2013
        $EUR_RON = 4.4203; // 03.01.2013
        $BGN_RON = round($EUR_RON / $EUR_BGN, 4);
        $expAmount = round(100 / $BGN_RON, 2);
    	$amount = $this->CurrencyRates->convertAmount(100, '2013-01-23', 'RON', 'BGN');
    	$this->assertEquals($expAmount, $amount);
    }
    
    
    /**
     * Конвертира сума от една валута в друга, и двете валути не са ЕВРО
     */
	public function testConvertOtherToOther()
    {
    	$EUR_USD = 1.2102; // 03.01.2013
        $EUR_RON = 4.4203; // 03.01.2013
        $USD_RON = round($EUR_RON / $EUR_USD, 4); // цената на 1 долар в RON
        $expAmount = round(100 * $USD_RON, 2);
        
        // Цената на 100 USD в RON 
    	$amount = $this->CurrencyRates->convertAmount(100, '2013-01-23', 'USD', 'RON');
    	$this->assertEquals($expAmount, $amount);
    }
    
    
    /**
     * Конвертира сума от една валута в друга, и двете валути не са ЕВРО
     */
	public function testConvertSpeed()
    {
    	$EUR_USD = 1.2102; // 03.01.2013
        $EUR_RON = 4.4203; // 03.01.2013
        $USD_RON = round($EUR_RON / $EUR_USD, 4); // цената на 1 долар в RON
        $expAmount = round(100 * $USD_RON, 2);
        
        // Цената на 100 USD в RON
        foreach (range(1, 1000) as $i) { 
    	    $amount = $this->CurrencyRates->convertAmount(100, '2013-01-23', 'USD', 'RON');
        }
        
    	$this->assertEquals($expAmount, $amount);
    }
    
    
    /**
     * Конвертира сума от една валута в друга, и двете валути не са ЕВРО
     * @expectedException core_exception_Expect
     */
	public function testConvertNonExisting()
    {
    	$amount = $this->CurrencyRates->convertAmount('100', '2013-01-23', 'CHF', 'RON');
    }
}
