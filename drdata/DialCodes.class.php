<?php



/**
 * Клас 'drdata_DialCodes' -
 *
 *
 * @category  vendors
 * @package   drdata
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class drdata_DialCodes extends core_Manager {
    
    
    /**
     * Заглавие
     */
    var $title = 'Телефонни кодове';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'drdata_Wrapper,plg_RowTools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Декларираме полетата
        $this->FLD('country', 'varchar', 'caption=Страна->Наименование');
        $this->FLD('countryCode', 'varchar(8)', 'caption=Страна->Код,notNull');
        $this->FLD('area', 'varchar', 'caption=Регион->Наименование');
        $this->FLD('areaCode', 'varchar(16)', 'caption=Регион->Код,notNull');
        
        // Декларираме индексите
        $this->setDbUnique('countryCode,areaCode', 'code');
        $this->setDbIndex('country');
    }
    

    /**
     * Изчистване на данните преди запис
     */
    function on_BeforeSave($mvc, $id, &$rec)
    {
        $rec->areaCode = trim($rec->areaCode);
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMVC(&$mvc, &$res)
    {
        // Вкарваме първия източник на данни
        $file = "drdata/data/DialingCodes.dat";
        
        // Колонките на файла с данни отнесени към полетата на модела
        $fields = array('country', 'countryCode', 'area', 'areaCode');
        
        // Разделителят е специфичен     
        $format = array('delimiter' => '|');
        
        // Импорт на данните
        $cntObj = csv_Lib::importOnceFromZero($mvc, $file, $fields, NULL, $format);        

        $res .= $cntObj->html;
    }
}