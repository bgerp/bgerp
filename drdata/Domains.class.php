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
    public $loadList = 'plg_Created, plg_State, drdata_Wrapper, plg_Sorting';
    
    
    /**
     * Заглавие
     */
    public $title = 'Домейни на публични имейл услуги';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, domain, isPublicMail, state';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin';
    
    
    /**
     * Кой  може да пише?
     */
    public $canWrite = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin, debug';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('domain', 'varchar(255)', 'caption=Домейн,mandatory');
        $this->FLD('isPublicMail', 'enum(no=Не, static=По дефиниция, cron=По данни)', 'caption=Публичност,mandatory');
        
        $this->setDbUnique('domain');
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public static function on_AfterSetupMVC($mvc, &$res)
    {
        // Подготвяме пътя до файла с данните
        $file = 'drdata/data/publicdomains.csv';
             
        // Кои колонки ще вкарваме
        $fields = array(
            0 => 'domain',
        );
        
        $defaults = array(
            'isPublicMail' => 'static',
            'state' => 'active',
        );
        
        // Импортираме данните от CSV файла.
        // Ако той не е променян - няма да се импортират повторно
        $cntObj = csv_Lib::importOnce($mvc, $file, $fields, $defaults);
        
        // Записваме в лога вербалното представяне на резултата от импортирането
        $res .= $cntObj->html;
    }
    
    
    /**
     * Проверка дали един домейн е публичен имейл доставчик или не
     *
     * @param  string  $domain
     * @return boolean TRUE - публичен, FALSE - не е публичен
     */
    public static function isPublic($domain)
    {
        if (strpos($domain, '@')) {
            list($left, $domain) = explode('@', $domain);
        }

        $domain = strtolower(trim($domain));

        return (boolean) static::fetch(array(
            "#domain = '[#1#]'"
            . " AND #state = 'active'"
            . " AND #isPublicMail != 'no'"
            . ' AND #isPublicMail IS NOT NULL', $domain));
    }
    
    
    /**
     * Рее-инициализира БД-списъка с публични домейни от тип `cron`.
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
    public static function resetPublicDomains($domains)
    {
        $stats = array(
            'added' => 0,
            'addErrors' => 0,
            'removed' => 0,
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
                $stats[$success ? 'removed' : 'removeErrors']++;
            }
        }
        
        // Тъй като от масива $domains махнахме домейните, които вече са в БД, в него сега
        // останаха само публични домейни, които все още не са в БД. Добавяме ги.
        $domaninKeys = array_keys($domains);
        foreach ($domaninKeys as $domain) {
            $success = static::save(
                (object) array(
                    'domain' => $domain,
                    'isPublicMail' => 'cron'
                ),
                null,
                'IGNORE'
            );
            
            if (!$success) {
                
                // Дублираните да не се броят за грешка
                if (self::fetch(array("#domain = '[#1#]'", $domain))) {
                    continue;
                }
            }
            
            $stats[$success ? 'added' : 'addErrors']++;
        }
        
        return $stats;
    }
}
