<?php 


/**
 * Клас 'status_Messages'
 *
 * @category  vendors
 * @package   status
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class status_Messages extends core_Manager
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Статус съобщения';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'status_Wrapper, plg_Created';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('text', 'html', 'caption=Текст');
        $this->FLD('type', 'enum(success=Успех, notice=Известие, warning=Предупреждение, error=Грешка)', 'caption=Тип');
        $this->FLD('userId', 'user', 'caption=Потребител');
        $this->FLD('sid', 'varchar(32)', 'caption=Идентификатор');
        $this->FLD('lifeTime', 'time', 'caption=Живот');
    }
    
    
    /**
     * Добавя статус съобщение към избрания потребител
     * 
     * @param string $text - Съобщение, което ще добавим
     * @param enum $type - Типа на съобщението - success, notice, warning, error
     * @param integer $userId - Потребителя, към когото ще се добавя. Ако не е подаден потребител, тогава взема текущия потребител.
     * @param integer $lifeTime - След колко време да е неактивно
     * 
     * @return integer - При успешен запис връща id' то на записа
     */
    static function newStatus($text, $type='notice', $userId=NULL, $lifeTime=60)
    {
        // Ако не подаден потребител, тогава използваме текущия
        $userId = ($userId) ? ($userId) : (core_Users::getCurrent());
        
        // Стойности за записа
        $rec = new stdClass();
        $rec->sid = static::getSid();
        $rec->text = $text;
        $rec->type = $type;
        $rec->userId = $userId;
        $rec->lifeTime = $lifeTime;
        
        $id = static::save($rec);
        
        return $id;
    }
    
    
    /**
     * Генерира sid на текущия потребител
     * 
     * @return string - md5 стойността на sid
     */
    static function getSid()
    {
        //Перманентния ключ на текущия потребител
        $permanentKey = Mode::getPermanentKey();
        
        // Стойността на солта на константата
        $conf = core_Packs::getConfig('status');
        $salt = $conf->STATUS_SALT;
        
        //Вземаме md5'а на sid
        $sid = md5($salt . $permanentKey);
        
        return $sid;
    }
    
    
    /**
     * Връща всички статуси на текущия потребител, на които не им е изтекъл lifeTime' а
     * 
     * @param integer $hitTime - timestamp на изискване на страницата
     * 
     * @return array $resArr - Масив със съобщението и типа на статуса
     * @access protected
     */
    static function getStatuses($hitTime)
    {
        $resArr = array();
        
        // id на текущия потребител
        $userId = core_Users::getCurrent();
        
        // Време на извикване на страницата
        $hitTime = dt::timestamp2Mysql($hitTime);
        
        // Вземаме всички записи за текущия потребител
        // Създадени преди съответното време
        $query = static::getQuery();
        $query->where(array("#createdOn >= '[#1#]'", $hitTime));
        
        // Ако потребителя е логнат
        if ($userId > 0) {
            
            // Статусите за него
            $query->where(array("#userId = '[#1#]'", $userId));
        } else {
            
            // Статусите за съответния SID
            $sid = static::getSid();
            $query->where(array("#sid = '[#1#]'", $sid));
        }
        
        $query->orderBy('createdOn', 'ASC');
        
        while ($rec = $query->fetch()) {
            
            // Двумерен масив с типа и текста
            $resArr[$rec->id]['text'] = $rec->text;
            $resArr[$rec->id]['type'] = $rec->type;
        }
        
        return $resArr;
        
    }
    
    
    /**
     * Извлича статусите за текущия потребител и ги добавя в div таг
     * 
     * @return string - Всички активни статуси за текущия потребител, групирани в div таг
     */
    static function show_($hitTime)
    {
        // Всички статуси за текущия потребител преди времето на извикване на страницата
        $statusArr = static::getStatuses($hitTime);
        
        $res = '';
        
        foreach ($statusArr as $value) {

            // Записваме всеки статус в отделен div и класа се взема от типа на статуса
            $res .= "<div class='statuses-{$value['type']}'> {$value['text']} </div>";
        }
        
        return $res;
    }
}
