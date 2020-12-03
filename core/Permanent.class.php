<?php


/**
 * Максимален размер за полето на манипулатора
 */
defIfNot('CORE_PERMANENT_HANDLER_SIZE', 32);


/**
 * Сол за префика на ключовете
 */
defIfNot('CORE_PERMANENT_PREFIX_SALT', md5(EF_SALT . '_CORE_PERM'));


/**
 * Клас 'core_Permanent' - Кеширане на обекти за по-дълго време, променливи или масиви за определено време
 *
 *
 * @category  bgerp
 * @package   core
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class core_Permanent extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Постоянни данни';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Постоянен обект';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Кой може да добавя
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да редактира
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой може да изтрива
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
    public $fetchFieldsBeforeDelete = 'id,key';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created,plg_SystemWrapper,plg_Sorting';
    
    
    /**
     * Стойност, при която кеша няма да бъде автоматично изтрит
     */
    const IMMORTAL_VALUE = 0;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('key', 'identifier(' . (CORE_PERMANENT_HANDLER_SIZE + 3) . ')', 'caption=Ключ,notNull');
        $this->FLD('data', 'blob(16777215,serialize,compress)', 'caption=Данни');
        $this->FLD('lifetime', 'int', 'caption=Живот,notNull');
        
        $this->setDbUnique('key');
    }
    
    
    /**
     * Записва обект в постоянния кеш
     */
    public static function set($key, $data, $lifetime = 1)
    {
        // Ключа
        $key = self::getKey($key);
        expect(is_numeric($lifetime));
        expect(!is_null($data));
        
        // Колко е живота на кеша (освен ако не е завинаги)
        if($lifetime != self::IMMORTAL_VALUE){
            $lifetime = time() + ($lifetime * 60);
        }
        
        // Подготовка на записа
        $rec = (object) array('key' => $key, 'data' => (object) array('value' => $data), 'lifetime' => $lifetime);
        
        // Запис, ако има стар го замества
        $me = cls::get(get_called_class());
        $id = $me->save($rec, null, 'REPLACE');
        Debug::log("PERMANENT_CACHE::set {$key}");
        
        return $id;
    }
    
    
    /**
     * Извлича запис от кеша
     *
     * @param string   $key          - ключ на кеша
     * @param datetime $minCreatedOn - от коя дата насетне да се търси
     *
     * @return mixed - кешираната стойност или NULL, ако не е намерена
     */
    public static function get($key, $minCreatedOn = null)
    {
        $key = self::getKey($key);
        
        // Подготовка на условието
        $where = "#key = '[#1#]'";
        if (isset($minCreatedOn)) {
            $where .= " AND #createdOn >= '{$minCreatedOn}'";
        }
        
        // Опит за извличане на данни
        $rec = self::fetch(array($where, $key), 'data,lifetime', false);
        
        if (empty($rec) || !is_object($rec->data)) {
            Debug::log("PERMANENT_CACHE::get {$key} - no exists");
            
            return;
        }
        
        // Ако живота е изтекъл се изтрива записа, вместо да се връща-
        if ($rec->lifetime != self::IMMORTAL_VALUE && $rec->lifetime < time()) {
            self::delete($rec->id);
            Debug::log("PERMANENT_CACHE::delete {$key} - expired");
            
            return;
        }
        
        // Връщане на кешираните данни
        $value = $rec->data->value;
        Debug::log("PERMANENT_CACHE::get {$key} - success");
        
        return $value;
    }
    
    
    /**
     * Изтриване на постоянния кеш
     *
     * @param string $key     - ключа
     * @param bool   $likeKey - дали да е точно подадения ключ или подобен на него
     *
     * @return int
     */
    public static function remove($key, $likeKey = false)
    {
        $key = self::getKey($key);
        
        if ($likeKey) {
            self::delete(array("#key LIKE '[#1#]%'", "{$key}"));
        } else {
            self::delete(array("#key = '[#1#]'", $key));
        }
    }
    
    
    /**
     * Връща ключ за запазване
     *
     * @param string $key - оригиналния ключ
     *
     * @return string $newKey - ключа за запис
     */
    private static function getKey($key)
    {
        $handler = str::convertToFixedKey($key, CORE_PERMANENT_HANDLER_SIZE - 4, 12);
        $prefix = md5(EF_DB_NAME . '|' . CORE_PERMANENT_PREFIX_SALT);
        $prefix = substr($prefix, 0, 6);
        
        $newKey = "{$prefix}|{$handler}";
        
        return $newKey;
    }
    
    
    /**
     * Изтриване на изтеклите записи, по разписание
     */
    public function cron_DeleteExpiredPermData()
    {
        $deletedRecs = $this->delete("#lifetime != '" . self::IMMORTAL_VALUE . "' AND #lifetime < " . time());
        $msg = "Лог: <b style='color:blue;'>{$deletedRecs}</b> постоянни записа с изтекъл срок бяха изтрити";
        
        return $msg;
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    protected static function on_AfterRecToVerbal(&$mvc, &$row, &$rec, $fields = array())
    {
        if($rec->lifetime == self::IMMORTAL_VALUE){
            $row->lifetime = tr("Без лимит");
        }
    }
}
