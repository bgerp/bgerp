<?php


/**
 * Мениджър на семафори за присвоявания и действия
 *
 *
 * @category  bgerp
 * @package   sens2
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sens2_Semaphores extends core_Master
{
    
    /**
     * Необходими плъгини
     */
    public $loadList = 'sens2_Wrapper, plg_RowTools2';
    
    
    /**
     * Заглавие
     */
    public $title = 'Семафори';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin,sens';
 
    
    /**
     * Кой може да добавя, редактира и изтрива?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да добавя, редактира и изтрива?
     */
    public $canDelete = 'debug';
    
    
    /**
     * Кой може да добавя, редактира и изтрива?
     */
    public $canEdit = 'no_one';

    
    /**
     * Дали за този модел ще се прави репликация на SQL заявките
     */
    public $doReplication = false;
    
    
    /**
     * Описание на полетата на модела
     */
    public function description()
    {
        $this->FLD('objectId', 'varchar(64)', 'caption=Обект');
        $this->FLD('value', 'double', 'caption=Стойност');
        $this->FLD('changedOn', 'datetime', 'caption=Промяна->Последна');
        $this->FLD('attempts', 'int', 'caption=Промяна->Опити');

        $this->setDbUnique('objectId');
        
        $this->dbEngine = 'memory';
    }


    
    /**
     * Дали даденото действие да бъде изпълнено
     * 
     * @param int $objectId - The identifier of the variable or output to assign the value to (e.g., string or integer).
     * @param double $value - The value to be assigned  
     * @param bool $onlyDifferent - Boolean flag (true/false) indicating if only a value different from the last assigned one is allowed.
     * @param $minInterval int - Minimum time (in seconds) required between two assignments.
     * @param int $minAttempts - Minimum number of attempts required to assign a value before permitting the assignment.
     *
     * @return bool (true/false) - Whether the assignment should be honored and executed by the calling code.
     *
     */
    public static function check($objectId, $value, $onlyDifferent, $minInterval, $minAttempts) 
    {
        // Ако нищо не е зададено - продължаваме със записа
        if($onlyDifferent === null && $minInterval === null &&  $minAttempts === null) {

            return true;
        }

        $rec = self::fetch("#objectId = {$objectId}");

        if(!$rec) {
            $rec = (object) array(
                'objectId' => $objectId,
                'value' => $value,
                'changedOn' => dt::now(),
                'attempts' => 0,
                );
            self::save($rec);

            return true;
        }

        $res = true;
        

        // Ако се приемат само различни стойности, резултата е негативен, ако стойността съвпада с последно зададената
        if($onlyDifferent && $rec->value == $value) {
            log_System::add('sens2_Semaphores', "1: $onlyDifferent {$rec->value} == {$value}", $rec, 'warning');
            $res = false;
        }
        
        // Ако е зададено минимално време, между две присвоявания, проверява се дали е изтекло от последното присвояване
        if($minInterval && dt::addSecs($minInterval, $rec->changedOn) > dt::now()) {
            log_System::add('sens2_Semaphores', "2: {$minInterval} {$rec->changedOn}", $rec, 'warning');
            $res = false;
        }

        // Увеличаваме броя на опитите
        if((!$onlyDifferent) || ($rec->value != $value)) {
            $rec->attempts++;
        }

        // Ако е зададен минимален брой опити се проверява дали са направени
        if($minAttempts && ($rec->attempts < $minAttempts)) {
            log_System::add('sens2_Semaphores', "3: {$minAttempts} {$rec->attempts}", $rec, 'warning');
            $res = false; 
        }
 
        // Ако резултата е положителен, то запазваме новата стойност, времето и нулираме опитите
        if($res) {
            $rec->value = $value;
            $rec->changedOn = dt::now();
            $rec->attempts = 0;
        } 
 
        self::save($rec);
 
        return $res;
    }
}
