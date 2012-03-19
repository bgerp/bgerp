<?php



/**
 * Клас 'core_Lock' - Мениджър за заключване на обекти
 *
 *
 * @category  all
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
     * Кой може да листва и разглежда?
     */
    var $canRead = 'admin';
    
    
    /**
     * Кой може да добавя, редактира и изтрива?
     */
    var $canWrite = 'no_one';
    
    
    /**
     * Плъгини и MVC класове за предварително зареждане
     */
    var $loadList = 'plg_SystemWrapper';
    
    
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
    }
    
    
    /**
     * Заключва обект с посоченото $objectId за максимално време $maxDuration,
     * като за това прави $maxTrays опити, през интервал от 1 секунда
     */
    function add($objectId, $maxDuration = 10, $maxTrays = 5)
    {
        $Locks = cls::get('core_Locks');
        
        // Санитаризираме данните
        $maxTrays = max($maxTrays, 1);
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
            }
            
            return TRUE;
        }
        
        $rec = $Locks->fetch(array("#objectId = '[#1#]'", $objectId));
        
        $rec->user = core_Users::getCurrent();
        
        // Ако няма запис за този обект или заключването е преминало крайния си срок 
        // - записваме го и излизаме с успех
        if (empty($rec->id) || ($rec->lockExpire <= time())) {
            $rec->lockExpire = $lockExpire;
            $rec->objectId = $objectId;
            $Locks->save($rec);
            
            return TRUE;
        }
        
        // Дотук стигаме след като $rec->id съществува и $rec->lockExpire > time()
        // Следователно има запис и той е заключен от друг хит - 
        // правим зададения брой опити да го запишем през 1 секунди
        $lock = TRUE;
        
        do {
            sleep(1);
            
            if ($rec->lockExpire <= time()) {
                // Записът се е отключил => записваме нашия lock
                $Locks->save($rec, NULL, 'IGNORE');
                $lock = FALSE;
            }
            $maxTrays--;
        } while($lock && ($maxTrays>0));
        
        if (!$lock) {
            $this->locks[$objectId] = $rec;
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * Отключва обект с посоченото $objectId
     * Извиква се при край на операцията четене или запис започната с add()
     */
    function remove($objectId)
    {
        $Locks = cls::get('core_Locks');
        $Locks->delete(array("#objectId = '[#1#]'", $objectId));
    }
    
    
    /**
     * Деструктор, който премахва всички локвания от текущия хит
     */
    function __destruct()
    {
        if(count($this->locks)) {
            foreach($this->locks as $rec) {
                $this->delete($rec->id);
            }
        }
    }
}