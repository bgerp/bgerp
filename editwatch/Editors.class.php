<?php


/**
 * Клас 'editwatch_Editors' -
 *
 *
 * @category  vendors
 * @package   editwatch
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class editwatch_Editors extends core_Manager
{
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('userId', 'key(mvc=core_Users)', 'caption=Потребител');
        $this->FLD('mvcName', 'varchar(64)', 'caption=Мениджър');
        $this->FLD('recId', 'int', 'caption=Запис');
        $this->FLD('lastEdit', 'datetime', 'caption=Последно');
        
        $this->dbEngine = 'InnoDB';
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public static function getAndSetCurrentEditors($mvcName, $recId, $userId = null)
    {
        return static::getCurrentEditors($mvcName, $recId, $userId, true);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public static function getCurrentEditors($mvcName, $recId, $userId = null, $setEditor = false)
    {
        $res = array();
        
        // Подготовка на данните
        if (is_object($mvcName)) {
            $mvcName = cls::getClassName($mvcName);
        }
        
        if (null === $userId) {
            $userId = Users::getCurrent();
        }
        
        if ($setEditor) {
            $rec = new stdClass();
            $rec->id = static::fetchField("#userId = {$userId} AND #mvcName = '{$mvcName}' AND #recId = {$recId}", 'id');
            $rec->lastEdit = DT::verbal2mysql();
            $rec->userId = $userId;
            $rec->recId = $recId;
            $rec->mvcName = $mvcName;
            static::save($rec);
        }
        
        $query = static::getQuery();
        
        $before1min = dt::timestamp2Mysql(time() - 7);
        
        $sql = "#userId != {$userId} AND " .
        "#mvcName = '{$mvcName}' AND #recId = {$recId} AND #lastEdit >= '{$before1min}'";
        
        while ($rec = $query->fetch($sql)) {
            $res[$rec->userId] = $rec->lastEdit;
        }
        
        return $res;
    }
    
    
    /**
     * Изпълнява се след начално установяване
     */
    public static function on_AfterSetupMvc($mvc, &$res)
    {
        $conf = core_Packs::getConfig('editwatch');
        
        $rec = new stdClass();
        $rec->systemId = 'delete_old_editwatch_records';
        $rec->description = 'Изтриване на старите editwatch записа';
        $rec->controller = 'editwatch_Editors';
        $rec->action = 'DeleteOldRecs';
        $rec->period = max(1, round($conf->EDITWATCH_REC_LIFETIME / 60));
        $rec->offset = 0;
        $res .= core_Cron::addOnce($rec);
    }
    
    
    /**
     * Изтриване на старите записи по часовник
     */
    public function cron_DeleteOldRecs()
    {
        $conf = core_Packs::getConfig('editwatch');
        
        $expireTime = dt::timestamp2Mysql(time() - $conf->EDITWATCH_REC_LIFETIME);
        
        $cnt = $this->delete("#lastEdit <= '{$expireTime}'");
        
        return "Бяха изтрити {$cnt} EditWatch записа";
    }
}
