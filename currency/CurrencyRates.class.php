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
     *  @param varchar(3) $from - Трибуквения код на валутата, която ще обменяме
     *  @param varchar(3) $to - Трибуквения код на валутата, към която ще изчисляваме
     *  @param date $date - датата към която ще изчисляваме курса, ако няма
     *  дата, взима последната дата за която има запис и е най-близо.
     *  @return double - Курса по който се обменя едната валута към другата
     */
    static function getRateBetween($from, $to, $date = NULL)
    {
    	// Обръща,е датата в правилен формат, ако е NULL  взимаме текущата
    	if(!$date) {
    		$date = dt::verbal2mysql();
    	}
    	
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
	    $checkQuery->orderBy("#date", "DESC");
    	if($rate = $checkQuery->fetch()->rate) return $rate;
	    
    	// Изчислява курса на двете валути към основната валута
    	$fromRate = static::getBaseRate($fromId, $date);
    	$toRate = static::getBaseRate($toId, $date);
    	
    	if($fromRate == '1'){
    		
    		// Ако обръщаме от основната валута към друга, 
    		$res = round($toRate, 4);
    	} else {
    		
    		// Ако обръщаме от някаква валута към друга, 
    		$res = round($toRate / $fromRate, 4);
    	}
    	
    	// Връщаме обменния курс, като разделяме единия курс на другия
    	return $res;
    }
    
    
    /**
     * Функция която изчислява обменния курс на валута към основната валута
     * за даден период. Ако няма запис за посочения период връща последния
     * запис който е най-близо до подадената дата, ако не е подадена дата
     * взима по подразбиране последната дата. последния запис най-близо до подадената дата
     * @param int $currencyId - Ид на валутата която ще обръщаме
     * @param date $date - Дата към която търсим обменния курс
     * @return double $rate - Обменния курс към основната валута за периода
     */
    static function getBaseRate($currencyId, $date = NULL)
    {
    	if(!$date) {
    		$date = dt::verbal2mysql();
    	}
    	
    	// Провряваме дали някоя от валутите е основната валута за съответния
    	// период ако е то нейния рейт е 1
    	$checkQuery = static::getQuery();
	    $checkQuery->where("#date <= '{$date}'");
	    $checkQuery->orderBy("#date", "DESC");
	    if($checkQuery->fetch()->baseCurrencyId == $currencyId) {
	    	
	    	return $rate = 1;
	    }
	    
	    // Ако валутата не е основната за периода, извличаме нейния запис
	    $query = static::getQuery();
	    $query->where("#date <= '{$date}'");
	    $query->orderBy("#date", "DESC");
	    $query->where("#currencyId = '{$currencyId}'");
	    
	    // Очакваме да има запис на валутата( в случай че не е базовата валута)
	    expect($rec = $query->fetch(), "Нямаме запис за тази валута");
    	$rate = $rec->rate;
	    	
    	return $rate;
    }
}