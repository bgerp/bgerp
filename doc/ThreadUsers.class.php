<?php



/**
 * Регистър за отношенията на потребители към тредове
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_ThreadUsers extends core_Manager
{
    
    
    /**
     * Необходими мениджъри
     */
    var $loadList = 'plg_Modified, doc_Wrapper, plg_RowTools';
    
    
    /**
     * Заглавие
     */
    var $title = 'Отношения на потребители, към тредове';
    
    
    /**
     * Права за писане
     */
    var $canWrite = 'admin';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin';
    
    
    /**
     * Кой има право да добавя ?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        // Полета на таблицата
        $this->FLD('threadId', 'key(mvc=doc_Threads,select=id)', 'caption=Нишка, mandatory');
        $this->FLD('containerId', 'key(mvc=doc_Containers,select=id)', 'caption=Контейнер, mandatory');
        $this->FLD('userId', 'key(mvc=core_Users,select=nick)', 'caption=Потребител, mandatory');
        $this->FLD('relation', 'enum(shared=Споделен, subscribed=Абониран)', 'caption=Отношение');
        $this->FLD('seenOn', 'datetime', 'caption=Видян на');
        
        // Индекси 
        $this->setDbIndex('threadId');
        $this->setDbIndex('containerId');
        $this->setDbUnique('threadId,containerId,userId,relation');
    }

    
    /**
     * Добавя споделен потребител(и) към дадената нишка
     * Споделения потребител има права за нишката, дори и да няма права за нейната папка
     * Ако $users е int приема се, че това е id на един потребител. 
     * При множество потребители $users е keylist или масив
     */
    static function addShared($threadId, $containerId, $users, $relation = 'shared')
    {
        if(!$users) return;

        if(is_int($users)) {
            $usersArr = array($users => $users);
        } else {
            $usersArr = type_Keylist::toArray($users);
        }

        if(count($usersArr)) {
            foreach($usersArr as $userId) {
                if($userId > 0) {
                    $rec = (object) array(
                            'threadId' => $threadId,
                            'containerId' => $containerId,
                            'userId' => $userId,
                            'relation' => $relation,
                        );
                    static::save($rec, NULL, 'IGNORE');
                }
            }
        }
    }


    /**
     * Добавя 'абониран' потребител(и) към дадената нишка
     * Абонирания потребител, получава нотификации, когато в нишката има нов документ
     * Ако $users е int приемасе, че това е id на един потребител. 
     * При множество потребители $users е keylist или масив
     */
    static function addSubscribed($threadId, $containerId, $users)
    {
        return static::addShared($threadId, $containerId, $users, 'subscribed');
    }


    /**
     * Връща всички потребители, за които посочената нишка е споделена
     */
    static function getShared($threadId, $relation = 'shared')
    {
        $query = self::getQuery();
        $query->show("userId");
        while($rec = $query->fetch("#threadId = {$threadId} AND #relation = '{$relation}'")) {
            $res[$rec->userId] = $rec->userId;
        }

        return $res;
    }


    /**
     * Връща всички потребители, които са абонирани за посочената нишка
     */
    static function getSubscribed($threadId)
    {
        return static::getShared($threadId, 'subscribed');
    }
    

    /**
     * Проверява дали посочения потребител е в посоченото отношение към посочената нишка
     */
    static function is($threadId, $userId, $relation)
    {
        if(static::fetch("#threadId = {$threadId} AND #userId = {$userId} AND #relation = {$relation}")) {

            return TRUE;
        } else {

            return FALSE;
        }
    }


    /**
     * Премахва цялата информация за даден контейнер
     */
    static function removeContainer($containerId)
    {
        return static::delete("#containerId = {$containerId}");
    }
    
    
    /**
     * Маркира всичко споделени, с потребител контейнери от нишка като видяни
     * 
     * Маркирането става като се запише текущата дата за всички контейнери, които не са били
     * виждани до сега. За преди-видяните контейнери не се записва нищо.  
     * 
     * @param int $threadId
     * @param int $userId текущия потребител по подразбиране 
     * @return boolean
     */
    public static function markViewed($threadId, $userId = NULL)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent('id');
        }
        
        expect($userId);
        
        $now = dt::now();
        
        /* @var $query core_Query */
        $query = static::getQuery();

        /* @var $db core_Db */ 
        $db  = $query->mvc->db;
        
        $result = $db->query("
            UPDATE `{$query->mvc->dbTableName}`
               SET `seen_on` = IFNULL(`seen_on`, '{$now}')
             WHERE `thread_id` = {$threadId}
               AND `user_id`   = {$userId}
               AND `relation`  = 'shared'
        ");
        
        return $result !== FALSE;
    }
    
    
    public function markContainerViewed($containerId, $userId = NULL)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent('id');
        }
        
        expect($userId);
        
        $now = dt::now();
        
        /* @var $query core_Query */
        $query = static::getQuery();

        /* @var $db core_Db */ 
        $db  = $query->mvc->db;
        
        $result = $db->query("
            UPDATE `{$query->mvc->dbTableName}`
               SET `seen_on` = IFNULL(`seen_on`, '{$now}')
             WHERE `container_id` IN (" . implode(',', (array)$containerId) . ")
               AND `user_id`   = {$userId}
               AND `relation`  = 'shared'
        ");
        
        if ($result !== FALSE) {
            $result = $db->affectedRows();
        }
        
        return $result;
    }
    
    
    protected static function getThreadSharing($threadId)
    {
        static $shared = NULL;
        
        if (!isset($shared[$threadId])) {
            /* @var $query core_Query */
            $query = static::getQuery();
            
            $query->where("#threadId = {$threadId}");
            $query->where("#relation = 'shared'");
            
            $query->show('userId, containerId, seenOn');
            
            $shared[$threadId] = array();
            
            while ($rec = $query->fetch()) {
                $shared[$threadId][$rec->containerId][$rec->userId] = $rec->seenOn; 
            }
        }
        
        return $shared[$threadId];
    }


    /**
     * Споделянията на контейнер
     *
     * @param int $container key(mvc=doc_Containers)
     * @param int $threadId key(mvc=doc_Thread) нишката,в която е контейнера
     * @return array ['userId' => datetime] 
     */
    public static function prepareSharingHistory($containerId, $threadId)
    {
        $sharedWith = self::getThreadSharing($threadId);
        
        $result = !empty($sharedWith[$containerId]) ? $sharedWith[$containerId] : array();
    
        return $result;
    }
}
