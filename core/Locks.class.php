<?php



/**
 * Клас 'core_Lock' - Мениджър за заключване на обекти
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Locks extends core_Manager
{
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = 'Заключвания';
    
    /**
     * Кои полета ще бъдат показани?
     */
    // var $listFields = 'id,createdOn=Кога?,createdBy=Кой?,what=Какво?';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'admin,debug';
    
	
    /**
     * Кой може да листва и разглежда?
     */
    var $canRead = 'admin';
    
    
    /**
     * Кой може да добавя, редактира и изтрива?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой може да добавя, редактира и изтрива?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Плъгини и MVC класове за предварително зареждане
     */
    var $loadList = 'plg_SystemWrapper, plg_RowTools';
    
    
    /**
     * Масив с $objectId на всички заключени обекти от текущия хит
     */
    var $locks = array();
    
    
    /**
     * Описание на полетата на модела
     */
    function description()
    {
        $this->FLD('objectId', 'varchar(64)', 'caption=Обект');
        $this->FLD('lockExpire', 'int', 'caption=Срок');
        $this->FLD('user', 'key(mvc=core_Users)', 'caption=Потребител');
        
        $this->setDbUnique('objectId');
        
        $this->setDbEngine = 'memory';
        
        $this->dbEngine = 'InnoDB';
    }
    
    
    /**
     * Заключва обект с посоченото $objectId за максимално време $maxDuration,
     * като за това прави $maxTrays опити, през интервал от 1 секунда
     */
    static function get($objectId, $maxDuration = 10, $maxTrays = 5, $delOnShutDown = TRUE)
    {
        $Locks = cls::get('core_Locks');
        
        // Санитаризираме данните
        $maxTrays = max($maxTrays, 0);
        $maxDuration = max($maxDuration , 0);
        $objectId = str::convertToFixedKey($objectId, 32, 4);
        
        $lockExpire = time() + $maxDuration;
        
        $rec = $Locks->locks[$objectId];
        
        // Ако този обект е заключен от текущия хит, връщаме TRUE
        if($rec) {
            
            // Ако имаме промяна в крайния срок за заключването
            // отразяваме я в модела
            if($rec->lockExpire < $lockExpire) {
                $rec->lockExpire = $lockExpire;
                $Locks->save($rec);
                $Locks->locks[$objectId] = $rec;
                
                // Дали да се изтрие преди излизане от хита - за асинхронни процеси
                $Locks->locks[$objectId]->_delOnShutDown = $delOnShutDown;
            }
            
            return TRUE;
        }
        
        // Извличаме записа съответстващ на заключването, от модела
        $rec = $Locks->fetch(array("#objectId = '[#1#]'", $objectId), '*', FALSE);
        
        // Създаваме празен запис, ако не съществува такъв за обекта
        if(!$rec) {
            $rec = new stdClass();
        }
        
        // Ако няма запис за този обект или заключването е преминало крайния си срок 
        // - записваме го и излизаме с успех
        if (empty($rec->id) || ($rec->lockExpire <= time())) {
            $rec->lockExpire = $lockExpire;
            $rec->objectId = $objectId;
            $rec->user = core_Users::getCurrent();
            
            // Ако възникне дублиран запис
            if ($Locks->save($rec, NULL, 'IGNORE')) {
                $Locks->locks[$objectId] = $rec;
                
                // Дали да се изтрие преди излизане от хита - за асинхронни процеси
                $Locks->locks[$objectId]->_delOnShutDown = $delOnShutDown;
                
                return TRUE;
            }
        }
        
        // Правим последователно няколко опита да заключим обекта, през интервал 1 сек
        if (static::waitForLock($objectId, $maxDuration, $maxTrays)) return TRUE;    
        
        return FALSE;
    }
    
    
    /**
     * Форматира в по-вербални данни реда от листовата таблица
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->lockExpire = dt::mysql2verbal(dt::timestamp2Mysql($rec->lockExpire), 'd-M-Y G:i:s');
    }
    
    
    /**
     * Отключва обект с посоченото $objectId
     * Извиква се при край на операцията четене или запис започната с add()
     */
    static function release($objectId)
    {
        $objectId = str::convertToFixedKey($objectId, 32, 4);
        
        $Locks = cls::get('core_Locks');
        $Locks->delete(array("#objectId = '[#1#]'", $objectId));
    }
    
    
    /**
     * Преди излизане от хита, изтриваме всички негови локове
     */
    static function on_Shutdown($mvc)
    {
        if (count($mvc->locks)) {
            foreach($mvc->locks as $rec) {
                
                // Дали да се изтрие преди излизане от хита - за асинхронни процеси
                if ($rec->_delOnShutDown) {
                    $mvc->delete($rec->id);    
                }
            }
        }
    }
    
    
    /**
     * Проверява дали обекта е заключен
     * 
     * @param string $objectId - Стринга, за който се проверява дали не е заключен
     * 
     * @return boolean - Връща TRUE, ако обекта е заключен
     */
    static function isLocked($objectId)
    {
        // Сегашното време
        $now = time();
        
        // Проверяваме дали обекта не е заключен
        if (core_Locks::fetch("#objectId = '{$objectId}' AND #lockExpire >= '{$now}'", NULL, FALSE)) {
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * Правим последователно няколко опита да заключим обекта, през интервал 1 сек
     * 
     * @param string $objectId - Стринга, за който се проверява дали не е заключен 
     * @param integer $maxDuration - За колко време да заключим
     * @param integer $maxTrays - Колко опита да се направи за заключване
     * 
     * @return boolean
     */
    static function waitForLock($objectId, $maxDuration = 10, $maxTrays = 5)
    {
        // Правим последователно няколко опита да заключим обекта, през интервал 1 сек
        while($maxTrays > 0) {
            
            sleep(1);
            Debug::log("Sleep 1 sec. in" . __CLASS__);

            if(static::get($objectId, $maxDuration, 0)) {
                
                return TRUE;
            }
            
            $maxTrays--;
        }
    }
}