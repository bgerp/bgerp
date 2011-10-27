<?php


/**
 * Задаване на основна валута
 */
defIfNot('BGERP_BASE_CURRENCY', 'BGN');


/**
 * Клас 'currency_CurrencyRates' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    bank
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class currency_CurrencyRates extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, Currencies=currency_Currencies, currency_Wrapper, plg_Sorting, plg_Chart';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = "currencyId, date, rate, baseCurrencyId";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Исторически валутни курсове';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listItemsPerPage = 500;
    
    
    /**
     *  Описание на модела (таблицата)
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
        $XML=simplexml_load_file("http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml");
        $now = $XML->Cube->Cube['time']->__toString();
        
        $countCurrencies = 0;
        
        foreach($XML->Cube->Cube->Cube as $item){
            $rate = $item['rate']->__toString();
            $currency = $item['currency']->__toString();
            
            // $currencyId = $this->Currencies->fetchField("#code='{$currency}'", 'id');
            $currencyId = $this->Currencies->fetchField(array("#code='[#1#]'", $currency), 'id');
            
   
            
            $state = $this->Currencies->fetchField($currencyId, "state");
            
            if ($state == "closed"){
                continue;
            }
            
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
     *
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
        $rec->period = 24*60;
        $rec->offset = 17*60;
        $Cron->addOnce($rec);
        
        unset($rec->id);
        $rec->systemId = "update_currencies_night";
        $rec->offset = 21*60;
        
        $Cron->addOnce($rec);
        
        $res .= "<li style='color:#660000'>На Cron са зададени update_currencies_afternoon и update_currencies_night</li>";
    }
    
    
    /**
     *  Извиква се след подготовката на toolbar-а за табличния изглед
     */
    function on_AfterPrepareListToolbar($mvc, $data)
    {
        $data->toolbar->addBtn('Зареди от ECB', array($mvc, 'RetrieveCurrencies'));
    }
}