<?php


/**
 * Валутни курсове
 *
 *
 * @category  bgerp
 * @package   currency
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class currency_CurrencyRates extends core_Detail
{
    
	/**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'currencyId';
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, Currencies=currency_Currencies, currency_Wrapper, plg_Sorting, plg_Chart';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "currencyId, date, rate, baseCurrencyId";
    
    
    /**
     * Заглавие
     */
    var $title = 'Исторически валутни курсове';
    
    
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 20;
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута,chart=diff');
        $this->FLD('baseCurrencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Към основна валута');
        $this->FLD('date', 'date', 'caption=Курс->дата,chart=ax');
        $this->FLD('rate', 'double', 'caption=Курс->стойност,chart=ay');
        
        $this->setDbUnique('currencyId,baseCurrencyId,date');
    }
    
    
    /**
     * Зареждане на валути от xml файл от ECB
     *
     * @return string
     */
    function retrieveCurrenciesFromEcb()
    {
        $euroId = $this->Currencies->fetchField("#code='EUR'", 'id');
        
        $this->data = new stdClass();

        $this->data->rates = array();
        $XML = simplexml_load_file("http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml");
        $now = $XML->Cube->Cube['time']->__toString();
        
        $countCurrencies = 0;
        
        foreach($XML->Cube->Cube->Cube as $item){
            $rate = $item['rate']->__toString();
            $currency = $item['currency']->__toString();
            
            // $currencyId = $this->Currencies->fetchField("#code='{$currency}'", 'id');
            $currencyId = $this->Currencies->fetchField(array("#code='[#1#]'", $currency), 'id');
            
            if(!$currencyId) continue;
            
            $state = $this->Currencies->fetchField($currencyId, "state");
            
            if ($state == "closed") continue;
            
            // Проверка дали имаме такъв запис за текуща дата 
            if ($this->fetch("#currencyId={$currencyId} AND #baseCurrencyId={$euroId} AND #date='{$now}'")) {
                continue;
            }
            $rec = new stdClass();
            $rec->currencyId = $currencyId;
            $rec->baseCurrencyId = $euroId;
            $rec->date = $now;
            $rec->rate = $rate;
            
            $currenciesRec = new stdClass();
            $currenciesRec->id = $rec->currencyId;
            $currenciesRec->lastUpdate = $rec->date;
            $currenciesRec->lastRate = $rec->rate;
            
            $this->Currencies->save($currenciesRec, 'lastUpdate,lastRate');
            
            $this->save($rec);
            
            $countCurrencies++;
        }
        
        if($countCurrencies == '0') {
            $res = "Няма нови курсове за валути.";
        } else {
            $res = "Извлечени са курсове за {$countCurrencies} валути.";
        }
        
        return $res;
    }
    
    
    /**
     * Метод за Cron за зареждане на валутите
     */
    function cron_RetrieveCurrencies()
    {
        return $this->retrieveCurrenciesFromEcb();
    }
    
    
    /**
     * Action за тестване зареждането на валутите в debug mode.
     * В production mode този метод не се използва.
     */
    function act_RetrieveCurrencies()
    {
        return new Redirect (array('currency_CurrencyRates', 'default'), $this->retrieveCurrenciesFromEcb());
    }
    
    
    /**
     * Зареждане на Cron задачите за валутите след setup на класа
     *
     * @param core_MVC $mvc
     * @param string $res
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        $Cron = cls::get('core_Cron');
        
        $rec = new stdClass();
        $rec->systemId = "update_currencies_afternoon";
        $rec->description = "Зарежда валутни курсове";
        $rec->controller = "currency_CurrencyRates";
        $rec->action = "RetrieveCurrencies";
        $rec->period = 24 * 60;
        $rec->offset = 17 * 60;
        $Cron->addOnce($rec);
        
        unset($rec->id);
        $rec->systemId = "update_currencies_night";
        $rec->offset = 21 * 60;
        
        $Cron->addOnce($rec);
        
        $res .= "<li style='color:#660000'>На Cron са зададени update_currencies_afternoon и update_currencies_night</li>";
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, $data)
    {
        $data->toolbar->addBtn('Зареди от ECB', array($mvc, 'RetrieveCurrencies'));
    }
    

    /**
     *  Изчислява обменния курс от една валута в друга, за дадена дата
     *  @param date $from - Трибуквения код на валутата, която ще обменяме
     *  @param date $to - Трибуквения код на валутата, към която ще изчисляваме
     *  @param date $date - датата към която ще изчисляваме курса, ако няма
     *  дата, взима последната дата за която има запис и е най-близо.
     *  @return double - Курса по който се обменя едната валута към другата
     */
    static function getRate($from, $to, $date = NULL)
    {
    	// Обръща,е датата в правилен формат, ако е NULL  взимаме текущата
    	$date = dt::verbal2mysql($date);
    	
    	// Проверяваме дали подадените валути са в правилния формат 'XXX'
    	// където 'XXX' е стринг точно с 3 символа, които са Главни букви 
    	expect(preg_match('/^[A-Z]{3}$/', $from), 'Валутния Код трябва да е в правилен формат');
    	expect(preg_match('/^[A-Z]{3}$/', $to), 'Валутния Код трябва да е в правилен формат');
    	
    	// Ако подадените валути са еднакви, то обменния им курс е 1
    	if($from == $to) return 1;
    	
    	// Очакваме да има запис в мениджъра за подадените валути
    	expect($fromId = currency_Currencies::getIdByCode($from), 'Няма такава валута');
    	expect($toId = currency_Currencies::getIdByCode($to), 'Няма такава валута');
    	
    	// Проверяваме дали има директен запис за обменния курс от едната
    	// валута към другата, ако има го връщаме
    	$checkQuery = static::getQuery();
    	$checkQuery->where("#currencyId = '{$fromId}'");
    	$checkQuery->where("#baseCurrencyId = '{$toId}'");
    	$checkQuery->where("#date <= '{$date}'");
	    $checkQuery->orderBy("#date");
	    $checkQuery->limit(1);
    	if($rate = $checkQuery->fetch()->rate) return $rate;
	    
    	// Ако няма директен запис то изчисляваме курса на двете валути, към
    	// основната валута за подадения период, ако няма запис за този
    	// период  връща последния запис най-близо до подадената дата
    	$rates = array($fromId, $toId);
    	foreach($rates as &$element) {
	    	$query = static::getQuery();
	    	$query->where("#currencyId = '{$element}'");
	    	$query->where("#date <= '{$date}'");
	    	$query->orderBy("#date");
	    	$query->limit(1);
	    	$rate = $query->fetch()->rate;
	    	if(!$rate){
	    		$rate  = 1;
	    	}
	    	$element = $rate;
    	}
    	
    	// Връщаме обменния курс, като разделяме единия курс на другия
    	return round($rates[1] / $rates[0], 4);
    }
}