<?php


/**
 * 
 */
defIfNot('EF_STATUSE_SALT', '');


/**
 * Клас 'core_SpellNumber' - Вербално представяне на числа
 *
 *
 * @category  ef
 * @package   core
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class core_Statuses extends core_Manager
{
    
    
    /**
     * 
     */
    var $_fetchedRecords;
    
    
    /**
     * Заглавие
     */
    var $title = "Статусни съобщения";
    
    
    /**
     * Кой има право да го променя?
     */
    var $canEdit = 'admin';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin';
    
    
    /**
     * Кой може да изпраща имейли?
     */
    var $canSend = 'admin';
    
    
    /**
     * Кой има право да изтрива?
     */
    var $canDelete = 'admin';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_SystemWrapper, plg_Created';
    
    
        /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'userId, type, message, lifetime, createdOn,createdBy';
    
	
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('sid', 'varchar(32)', 'caption=Идентификатор');
        $this->FLD('userId', 'key(mvc=core_Users, select=names)', 'caption=Потребител');
        $this->FLD('message', 'text', 'caption=Съобщение');
        $this->FLD('type', 'enum(info=Информация, warning=Предупреждение, error=Грешка)', 'caption=Тип');
        $this->FLD('lifeTime', 'int', 'caption=Активен до');
    }
    
    
    /**
     * Добавя съобщение на избрания потребител
     * 
     * @param string  $message  - Съобщение, което ще добавим
     * @param enum    $type     - Типа на съобщението - info, warning, error
     * @param integer $userId   - Потребителя, към когото ще се добавя. Ако не подаден потребител, тогава взема текущия потребител.
     * @param integer $lifeTime - След колко време да се изтрие
     * 
     * @return integer $id - При успешен запис връща id' то на записа
     */
    static function add($mesage, $type='info', $userId=NULL, $lifeTime=60)
    {
        //Очакваме съобщението да не е празен стринг
        expect(str::trim($mesage), 'Няма въведено съобщение.');
        
        //Ако не подаден потребител, тогава взема текущия потребител
        $userId = ($userId) ? ($userId) : (core_Users::getCurrent());
        
        //До кога може да се покаже
        $lifeTime = time() + $lifeTime;
        
        //Вземаме уникалния sid'а на потребителя
        $sid = static::getSid();
        
        $rec = new stdClass();
        $rec->sid = $sid;
        $rec->userId = $userId;
        $rec->message = $mesage;
        $rec->lifeTime = $lifeTime;
        $rec->type = $type;
        
        //Записваме данните
        $id = static::save($rec);
        
        return $id;
    }
    
    
    /**
     * Връща всички статуси на текущия потребител, на които не им е изтекъл lifeTime' а
     * 
     * @return array $resArr - Масив със съобщението и типа на статуса
     */
    static function fetch()
    {
        $resArr = array();
        
        //Правим инстанция на класа
        $Statuses = cls::get('core_Statuses');
        
        //Текущото време
        $now = time();
        
        //Заявка към класа
        $query = $Statuses->getQuery();

        //Данните да са подредени по дата на създаване. Най - новите да са най отпред.
        $query->orderBy('createdOn', 'DESC');
        
        //Да се вземат тези, на които не им е изтекъл срока
        $query->where("#lifeTime >= '{$now}'");
        
        //За кой потребител
        if ($userId = core_Users::getCurrent()) {
            
            //Ако сме логнат потребител
            $query->where("#userId = '{$userId}'");        
        } else {
            
            //Ако не сме тогава вземаме cid'а на текущия потребител
            $sid = $Statuses->getSid();
            $query->where("#sid = '{$sid}'");        
        }

        //Обикаляме в откритите резултати
        while ($rec = $query->fetch()) {
            
            //Добавяме id' то на записа към променливата, от където ще се трият
            $Statuses->_fetchedRecords .= ($Statuses->_fetchedRecords) ? (',' . $rec->id) : ($rec->id);
            
            //Добавяме към масива id' то на записа и съобщението
            $resArr[$rec->id]['message'] = $Statuses->getVerbal($rec, 'message');
            
            //Добавяме към масива id' то на записа и типа
            $resArr[$rec->id]['type'] = $rec->type;
        }

        //Връщаме масива
        return $resArr;
    }
    
    
    /**
     * Извлича статусите за текущия потребител и ги добавя в div таг
     * 
     * @return string $res - Всички активни статуси за текущия потребител, групирани в div таг
     */
    static function show()
    {
        //Всички активни статуси за текущия потребител
        $notifArr = core_Statuses::fetch();
        
        //Обикаляме всички статуси
        foreach ($notifArr as $key => $value) {
            
            //Записваме всеки статус в отделен div и класа се взема от типа на статуса
            $res .= "<div class='notification-{$value['type']}'> {$value['message']} </div>";
        }

        //Инстанция към класа
        $Statuses = cls::get('core_Statuses');
        
        //Извикваме метода shutdown
        $Statuses->invoke('shutdown');
echo $res;
        
        return $res;
    }
    
    
    /**
     * 
     */
    function on_Shutdown($mvc)
    {
        if (!$mvc->_fetchedRecords) {

            return ;
        }
        //Масив с всички id' на съобщения, които са показани
        $recsArr = explode(',', $mvc->_fetchedRecords);
        
        foreach ($recsArr as $id) {
            
            //Изтриваме записити
//            static::delete($id);
        }
    }
    
    
    /**
     * Генерира и връща sid на текущия потребител
     */
    static function getSid()
    {
        //Перманентния ключ на текущия потребител
        $permanentKey = Mode::getPermanentKey();
        
        //Вземаме md5'а на sid
        $sid = md5(EF_STATUSE_SALT . $permanentKey);
        
        return $sid;
    }
    
    
    /**
     * 
     */
    function act_AjaxGetStatuses()
    {
        
        //
        $json = json_encode($this->fetch());
        
        //Извикваме метода shutdown
        $this->invoke('shutdown');
        
        return $json;
    }
}