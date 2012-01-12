<?php


/**
 * Клас 'drdata_Countries' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    drdata
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class drdata_Countries extends core_Manager {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'ISO информация за страните по света';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $recTitleTpl = '[#commonName#]';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'drdata_Wrapper,csv_Lib,plg_Sorting';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('commonName', 'varchar', 'caption=Наименование');
        $this->FLD('formalName', 'varchar', 'caption=Формално име');
        $this->FLD('type', 'varchar', 'caption=Тип');
        $this->FLD('sovereignty', 'varchar', 'caption=Суверинитет');
        $this->FLD('capital', 'varchar', 'caption=Столица');
        $this->FLD('currencyCode', 'varchar(3)', 'caption=валута->код');
        $this->FLD('currencyName', 'varchar', 'caption=валута->име');
        $this->FLD('telCode', 'varchar(3)', 'caption=ITU-T телефонен код');
        $this->FLD('letterCode2', 'varchar(3)', 'caption=2,rem=ISO 3166-1 2 буквен код');
        $this->FLD('letterCode3', 'varchar(3)', 'caption=3, rem=ISO 3166-1 3 буквен код');
        $this->FLD('isoNumber', 'int', 'caption=3, rem=ISO Номер');
        $this->FLD('domain', 'varchar(4)', 'caption=разширение, rem=IANA Country Code TLD');
        $this->load('plg_RowTools');
        
        $this->setDbUnique('commonName');
        $this->setDbIndex('letterCode2');
        $this->setDbIndex('letterCode3');
    }
    
    
    /**
     * Връща id-то на държавата от която посоченото или текущото ip
     */
    function getByIp($ip = NULL)
    {
        $cCode2 = drdata_IpToCountry::get($ip);
        
        $me = cls::get(__CLASS__);
        
        $id = $me->fetchField("#letterCode2 = '{$cCode2}'", 'id');
        
        return $id;
    }
    
    
    /**
     * Изпълнява се преди запис в модела
     * Премахва не-цифровите символи в кода
     */
    function on_BeforeSave($mvc, $id, $rec)
    {
        $rec->telCode = preg_replace('/[^0-9]+/', '', $rec->telCode);
    }
    
    
    /**
     *  Извиква се след SetUp-а на таблицата за модела
     */
    function on_AfterSetupMVC($mvc, $res)
    {
        if(!$mvc->fetch("1=1") || Request::get('Full')) {

            // Подготвяме пътя до файла с данните
            $dataCsvFile = dirname (__FILE__) ."/data/countrylist.csv";
            
            // Кои колонки ще вкарваме
            $fields = array(
                1 => "commonName",
                2 => "formalName",
                3 => "type",
                5 => "sovereignty",
                6 => "capital",
                7 => "currencyCode",
                8 => "currencyName",
                9 => "telCode",
                10 => "letterCode2",
                11 => "letterCode3",
                12 => "isoNumber",
                13 => "domain"
            );
            
            $importedRecs = csv_Lib::import($this, $dataCsvFile, $fields);
            
            if($importedRecs) {
                $res .= "<li style='color:green'> Импортирана е информация за {$importedRecs} държави.";
            }
        }
    }
}
