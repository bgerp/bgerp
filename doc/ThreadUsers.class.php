<?php



/**
 * Регистър за отношенията на потребители към тредове
 *
 * @category  bgerp
 * @package   doc
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_ThreadUsers extends core_Manager
{
    
    
    /**
     * Необходими мениджъри
     */
    public $loadList = 'plg_Modified, doc_Wrapper, plg_RowTools2';
    
    
    /**
     * Заглавие
     */
    public $title = 'Отношения на потребители, към тредове';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Отношение на потребител, към тредове';
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'admin';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin';
    
    
    /**
     * Кой има право да добавя ?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Описание на модела
     */
    public function description()
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
    public static function addShared($threadId, $containerId, $users, $relation = 'shared')
    {
        if (!$users) {
            return;
        }

        if (is_int($users)) {
            $usersArr = array($users => $users);
        } else {
            $usersArr = keylist::toArray($users);
        }

        if (count($usersArr)) {
            foreach ($usersArr as $userId) {
                if ($userId > 0) {
                    $rec = (object) array(
                            'threadId' => $threadId,
                            'containerId' => $containerId,
                            'userId' => $userId,
                            'relation' => $relation,
                        );
                    static::save($rec, null, 'IGNORE');
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
    public static function addSubscribed($threadId, $containerId, $users)
    {
        return static::addShared($threadId, $containerId, $users, 'subscribed');
    }


    /**
     * Връща всички потребители, за които посочената нишка е споделена
     */
    public static function getShared($threadId, $relation = 'shared')
    {
        $res = array();
        
        $query = self::getQuery();
        $query->show('userId');
        while ($rec = $query->fetch(array("#threadId = [#1#] AND #relation = '[#2#]'", $threadId, $relation))) {
            $res[$rec->userId] = $rec->userId;
        }

        return $res;
    }


    /**
     * Връща всички потребители, които са абонирани за посочената нишка
     */
    public static function getSubscribed($threadId)
    {
        return static::getShared($threadId, 'subscribed');
    }
    

    /**
     * Проверява дали посочения потребител е в посоченото отношение към посочената нишка
     */
    public static function is($threadId, $userId, $relation)
    {
        if (static::fetch("#threadId = {$threadId} AND #userId = {$userId} AND #relation = {$relation}")) {
            
            return true;
        }

        return false;
    }


    /**
     * Премахва цялата информация за даден контейнер
     */
    public static function removeContainer($containerId)
    {
        return static::delete("#containerId = {$containerId}");
    }
}
