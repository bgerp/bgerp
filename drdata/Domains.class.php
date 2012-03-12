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
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('domain', 'varchar(255)', 'caption=Домейн,mandatory');
        $this->FLD('isPublicMail', 'enum(no=Не, static=По дефиниция, cron=По данни)', 'caption=Публичност,mandatory');
        
        $this->setDbUnique('domain');
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    function on_AfterSetupMVC($mvc, $res)
    {
        if(!$mvc->fetch("1=1") || Request::get('Full')) {
            
            // Подготвяме пътя до файла с данните
            $dataCsvFile = dirname (__FILE__) . "/data/publicdomains.csv";
            
            // Кои колонки ще вкарваме
            $fields = array(
                0 => "domain",
            );
            
            $defaults = array(
                'isPublicMail' => 'static',
                'state' => 'active',
            );
            
            $importedRecs = csv_Lib::import($this, $dataCsvFile, $fields, array(), $defaults);
            
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
    
    
    /**
     * Ре-инициализира БД-списъка с публични домейни от тип `cron`.
     *
     * След изпълнението на този метод, списъка с публични домейни от тип `cron` в БД е точно
     * масива от домейни $domains
     *
     * @param array $domains масив от домейни; (обикновено) се генерира в
     * @link email_Incomings::scanForPublicDomains().
     * @return array масив с следните елементи:
     *
     * o [added]        - броя успешно добавени домейни
     * o [addErrors]    - броя домейни, за които е възникнала грешка при добавяне
     * o [removed]      - броя успешно изтрити домейни
     * o [removeErrors] - броя домейни, за които е възникнала грешка при изтриване
     */
    static function resetPublicDomains($domains)
    {
        $stats = array(
            'added'        => 0,
            'addErrors'    => 0,
            'removed'      => 0,
            'removeErrors' => 0,
        );
        
        $query = static::getQuery();
        $query->where("#isPublicMail = 'cron'");
        $query->show('domain');
        
        while ($rec = $query->fetch()) {
            if (isset($domains[$rec->domain])) {
                // $rec->domain е бил и остава публичен
                unset($domains[$rec->domain]);
            } else {
                // $rec->domain е бил, но вече не е публичен - изтриваме го от БД
                $success = static::delete($rec->id);
                $stats[$success ? 'removed' : 'removeErros']++;
            }
        }
        
        // Тъй като от масива $domains махнахме домейните, които вече са в БД, в него сега 
        // останаха само публични домейни, които все още не са в БД. Добавяме ги.
        foreach (array_keys($domains) as $domain) {
            $success = static::save(
                (object)array(
                    'domain'       => $domain,
                    'isPublicMail' => 'cron'
                )
            );
            
            $stats[$success ? 'added' : 'addErros']++;
        }
        
        return $stats;
    }
}