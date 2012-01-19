<?php

/**
 * Мениджър на домейни (TLD)
 *
 * Информацията дали един домейн е публичен се използва при рутирането на входящи писма.
 *
 *
 * @category  vendors
 * @package   drdata
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @see       https://github.com/bgerp/bgerp/issues/108
 * @see       https://github.com/bgerp/bgerp/issues/156
 */
class drdata_Domains extends core_Manager
{
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_State, drdata_Wrapper';
    
    
    /**
     * Заглавие
     */
    var $title = "Домейни";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, domain, isPublicMail, state';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin';
    
    
    /**
     * Кой  може да пише?
     */
    var $canWrite = 'admin';
    
    function description()
    {
        $this->FLD('domain', 'varchar(255)', 'caption=Домейн,mandatory');
        $this->FLD('isPublicMail', 'enum(no=Не е публичен, static=Публичен по дефиниция, cron=Публичен по данни)', 'caption=Домейн,mandatory');
    }
    
    
    /**
     *  Извиква се след SetUp-а на таблицата за модела
     */
    function on_AfterSetupMVC($mvc, $res)
    {
        if(!$mvc->fetch("1=1") || Request::get('Full')) {

            // Подготвяме пътя до файла с данните
            $dataCsvFile = dirname (__FILE__) ."/data/publicdomains.csv";
            
            // Кои колонки ще вкарваме
            $fields = array(
                1 => "domain",
                2 => "isPublicMail",
            );
            
            $importedRecs = csv_Lib::import($this, $dataCsvFile, $fields);
            
            if($importedRecs) {
                $res .= "<li style='color:green'>Импортирана е информация за {$importedRecs} публични имейл домейни.";
            }
        }
    }
    
    
    /**
     * Проверка дали един домейн е публичен или не
     *
     * @param string $domain
     * @return boolean TRUE - публичен, FALSE - не е публичен
     */
    static function isPublic($domain)
    {
        return (boolean)static::fetch(
        	"#domain = '{$domain}'"
            . " AND #state = 'active'"
            . " AND #isPublicMail != 'no'"
            . " AND #isPublicMail IS NOT NULL"
        );
    }
}
