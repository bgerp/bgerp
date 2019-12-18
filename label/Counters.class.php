<?php 

/**
 * Броячи за етикетите
 *
 * @category  bgerp
 * @package   label
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class label_Counters extends core_Master
{
    /**
     * Плейсхолдер за брояча
     */
    public static $counterPlace = '%';
    
    
    /**
     * Заглавие на модела
     */
    public $title = 'Броячи';
    
    
    public $singleTitle = 'Брояч';
    
    
    /**
     * Път към картинка 16x16
     */
    public $singleIcon = 'img/16/barcode-icon.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'label/tpl/SingleLayoutCounters.shtml';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'label, admin, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'labelMaster, admin, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'labelMaster, admin, ceo';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'label, admin, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'label, admin, ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'label, admin, ceo';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    public $canReject = 'labelMaster, admin, ceo';
    
    
    /**
     * Необходими роли за възстановяване на документа
     */
    public $canRestore = 'labelMaster, admin, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'label_Wrapper, plg_RowTools2, plg_Created, plg_State, plg_Rejected';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name, min, max, step, createdOn, createdBy';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'label_CounterItems';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar(128)', 'caption=Име, mandatory, width=100%');
        $this->FLD('min', 'int(min=0)', 'caption=Минимално, mandatory');
        $this->FLD('max', 'int(min=1)', 'caption=Максимално, mandatory');
        $this->FLD('step', 'int', 'caption=Стъпка, mandatory');
    }
    
    
    /**
     * Към максималния брояч в модела добавя стъпката и връща резултата
     *
     * @param int  $counterId     - id на записа
     * @param bool $updateCounter
     *
     * @return int - Нов номер
     */
    public static function getCurrent($counterId, $updateCounter = true)
    {
        if ($updateCounter) {
            static $maxVal = null;
        } else {
            $maxVal = null;
        }
        
        // Вземае записа
        $cRec = self::fetch($counterId);
        
        // Ако брояча е оттеглен
        expect($cRec->state != 'rejected', 'Брояча е оттеглен');
        
        // Ако брояча е затворен
        expect($cRec->state != 'closed', 'Броячът е изчерпан');
        
        // Флага, указващ дали има други стойности за брояча
        $haveCounter = false;
        
        if (!isset($maxVal)) {
            $maxVal = label_CounterItems::getMax($counterId);
        }
        
        // Ако няма запис
        if (isset($maxVal)) {
            
            // Добавяме стъпката
            $maxVal += $cRec->step;
        } else {
            
            // Ако стъпката е отрицателна
            if ($cRec->step < 0) {
                
                // Използваме максималната стойност за начална
                $maxVal = $cRec->max;
            } else {
                
                // Използваме минимална стойност за начална
                $maxVal = $cRec->min;
            }
        }
        
        // Ако стъпката е отрицателна
        if ($cRec->step < 0) {
            
            // Ако не сме достигнали минимума
            if ($maxVal >= $cRec->min) {
                
                // Вдигаме флага
                $haveCounter = true;
            }
        } else {
            
            // Ако не сме достигнали максимума
            if ($maxVal <= $cRec->max) {
                
                // Вдигаме флага
                $haveCounter = true;
            }
        }
        
        // Ако брояча е изчерпан
        if (!$haveCounter) {
            
            // Затваряме брояча
            $cRec->state = 'closed';
            
            // Записваме
            self::save($cRec);
            
            // Сетваме грешка
            expect(false, 'Броячът е изчерпан');
        }
        
        // Връщаме стойността
        return $maxVal;
    }
    
    
    /**
     * Проверява в стринга има плейсхолдер за брояч, който да се замести
     *
     * @param string $str - Стринга, който ще се проверява
     *
     * @return bool
     */
    public static function haveCounterPlace($str)
    {
        // Ако в текста някъде се намира плейсхолдер за брояча
        if (strpos($str, self::$counterPlace) !== false) {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Замества плейсхолдера за брояч със съответната стойност
     *
     * @param string $str           - Стринг, в който ще се замества
     * @param int    $counterId     - id на брояча
     * @param int    $printId       - id на етикета
     * @param bool   $updateCounter - Ако е FALSE не се обновява брояча
     *
     * @return string - Новия стринг
     */
    public static function placeCounter($str, $counterId, $printId, $updateCounter = true)
    {
        // Ако име плейсхолдер за брояч
        if (self::haveCounterPlace($str)) {
            
            // Вземаем текущия брояч
            $counter = self::getCurrent($counterId, $updateCounter);
            
            // Ако е зададено да не се обновява
            if ($updateCounter === false) {
                
                // Вземаме последната стойност
                $updated = $counter;
            } else {
                
                // Упдейтваме последния брояч
                $updated = label_CounterItems::updateCounter($counterId, $printId, $counter);
            }
            
            // Очакваме да няма грешка
            expect(isset($updated));
            
            // Заместваме в стринга
            $str = str_replace(self::$counterPlace, $counter, $str);
        }
        
        return $str;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form     $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        // Ако формата е изпратена успешно
        if ($form->isSubmitted()) {
            
            // Ако максимума не е по - голяма от минимума
            if ($form->rec->max <= $form->rec->min) {
                
                // Сетваме грешка
                $form->setError('max', 'Максимума трябва да е над минимума');
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param label_Counters $mvc
     * @param string         $requiredRoles
     * @param string         $action
     * @param stdClass       $rec
     * @param int            $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        // Ако има запис
        if ($rec) {
            
            // Ако редактираме
            if ($action == 'edit') {
                
                // Ако е чернова
                if ($rec->state != 'draft') {
                    
                    // Само чернова да могат да се редактират
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Активира шаблона
     *
     * @param int $id - id на записа
     *
     * @return int - id на записа
     */
    public static function activateCounter($id)
    {
        // Ако няма
        if (!$id) {
            
            return ;
        }
        
        // Вземаме записа
        $rec = self::fetch($id);
        
        // Очакваме да не е оттеглен
        expect($rec->state != 'rejected');
        
        // Ако състоянието е 'draft'
        if ($rec->state == 'draft') {
            
            // Сменяме състоянито на активно
            $rec->state = 'active';
            
            // Записваме
            $id = self::save($rec);
            
            return $id;
        }
    }
    
    
    /**
     * Действия преди извличането на данните
     *
     * @param label_Counters $mvc
     * @param stdClass       $data
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        // Подреждаме по състояние
        $data->query->orderBy('#state=ASC');
        
        // Подреждаме по дата на създаване
        $data->query->orderBy('#createdOn=DESC');
    }
}
