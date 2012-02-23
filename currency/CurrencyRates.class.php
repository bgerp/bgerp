<?php



/**
 * Задаване на основна валута
 */
defIfNot('BGERP_BASE_CURRENCY', 'BGN');


/**
 * Клас 'currency_CurrencyRates' -
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
class currency_CurrencyRates extends core_Manager
{
    
    
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
    var $listItemsPerPage = 500;
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валутa,chart=diff');
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
    function on_AfterSetupMvc($mvc, &$res)
    {
        $Cron = cls::get('core_Cron');
        
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
    function on_AfterPrepareListToolbar($mvc, $data)
    {
        $data->toolbar->addBtn('Зареди от ECB', array($mvc, 'RetrieveCurrencies'));
    }
}