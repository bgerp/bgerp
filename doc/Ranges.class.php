<?php


/**
 * Клас 'doc_Ranges' - Модел за диапазони на номерата на документите
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.11
 */
class doc_Ranges extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created,doc_Wrapper,plg_RowTools2,plg_State2';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin';
    
    
    /**
     * Кой може да го редактира?
     */
    public $canWrite = 'ceo,admin';
    
    
    /**
     * Кой може да променя състоянието?
     */
    public $canChangestate = 'ceo,admin';
    
    
    /**
     * Заглавие
     */
    public $title = 'Диапазони на документите';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Диапазон на документите';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,class,min,max,current,lastUsedOn,systemId,state,createdOn,createdBy';
    
    
    /**
     * Описание на модела на нишките от контейнери за документи
     */
    public function description()
    {
        // Информация за нишката
        $this->FLD('class', 'class(interface=doc_DocumentIntf,select=title)', 'mandatory,caption=Документ');
        $this->FLD('min', 'int(min=0)', 'mandatory,caption=Долна граница');
        $this->FLD('max', 'int(Min=0)', 'mandatory,caption=Горна граница');
        $this->FLD('current', 'varchar', 'input=none,caption=Текущ');
        $this->FLD('lastUsedOn', 'datetime(format=smartTime)', 'input=none,caption=Последно');
        $this->FLD('systemId', 'varchar', 'input=none,caption=Системно ид');
        
        $this->setDbIndex('class');
        $this->setDbUnique('class,min,max');
        $this->setDbUnique('systemId');
    }
    
    
    /**
     * Добавя нов диапазон за документа
     * 
     * @param mixed $class
     * @param int $min
     * @param int $max
     * @param string|null $systemId
     * 
     * @return int
     */
    public static function add($class, $min, $max, $systemId = null)
    {
        $mvc = cls::get($class);
        
        expect($max > $min && (empty($min) || ($min && ctype_digit($min))) && ctype_digit($max));
        $rec = (object)array('class' => $mvc->getClassId(), 'min' => $min, 'max' => $max);
        if(isset($systemId)){
            $rec->systemId = $systemId;
        }
        
        $exRec = $fields = null;
        if (!cls::get(get_called_class())->isUnique($rec, $fields, $exRec)) {
            $rec->id = $exRec->id;
        } else {
            $rec->state = 'active';
        }
        
        return self::save($rec);
    }
    
    
    /**
     * Кои са допустимите диапазони за документа
     * 
     * @param mixed $class
     * 
     * @return array $res
     */
    public static function getAvailableRanges($class)
    {
        $res = array();
        $mvc = cls::get($class);
        
        $query = self::getQuery();
        $query->where("#class = {$mvc->getClassId()} AND #state = 'active'");
        $query->where("#current IS NULL OR (#current IS NOT NULL AND #current < #max)");
        
        $query->orderBy('min', 'ASC');
        while($rec = $query->fetch()){
            $res[$rec->id] = self::displayRange($rec);
        }
        
        return $res;
    }
    
    
    /**
     * Кой е дефолтния диапазон на класа
     * 
     * @param mixed $class
     * 
     * @return int $defaultRangeId
     */
    public static function getDefaultRangeId($class)
    {
        $ranges = self::getAvailableRanges($class);
        $defaultRangeId = key($ranges);
        
        return $defaultRangeId;
    }
    
    
    /**
     * Ф-я връщаща следващия номер на документа в зададения диапазон
     * 
     * @param int $id
     * @param mixed $class
     * @param string|null $numberField
     * @param string|null $rangeNumField
     * 
     * @throws core_exception_Expect
     * 
     * @return int $next
     */
    public static function getNextNumber($id, $class, $numberField = null, $rangeNumField = null)
    {
        expect($rec = self::fetchRec($id));
        $mvc = cls::get($class);
        setIfNot($numberField, $mvc->numberFld);
        setIfNot($rangeNumField, $mvc->rangeNumFld);
        
        if($rec->state == 'closed'){
            throw new core_exception_Expect('Избраният диапазон е запълнен. Моля изберете друг|*!', 'Несъответствие');
        }
        
        $query = $mvc->getQuery();
        $query->XPR('maxNum', 'int', "MAX(#{$numberField})");
        $query->between('number', $rec->min, $rec->max);
        $query->where("#{$rangeNumField} = {$rec->id}");
        
        if (!$maxNum = $query->fetch()->maxNum) {
            $next = $rec->min;
        } else {
            $next = $maxNum + 1;
        }
        
        if($next > $rec->max){
            throw new core_exception_Expect('Избраният диапазон е запълнен. Моля изберете друг|*!', 'Несъответствие');
        }
        
        return $next;
    }
    
    
    /**
     * Обновява брояча на диапазона
     * 
     * @param int $id
     * @param double $number
     * 
     * @return void
     */
    public static function updateRange($id, $current)
    {
        expect($rec = self::fetchRec($id));
        
        $rec->current = $current;
        if($rec->current >= $rec->max){
            $rec->state = 'closed';
        }
        
        self::save($rec, 'current,state');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'delete' && isset($rec)) {
            if(!empty($rec->lastUsedOn) || !empty($rec->current)){
                $requiredRoles = 'no_one';
            }
        }
        
        if($action == 'edit' && isset($rec)) {
            if($rec->state == 'closed'){
                $requiredRoles = 'no_one';
            }
        }
        
        if($action == 'changestate' && isset($rec)){
            if(!empty($rec->systemId)){
                $requiredRoles = 'no_one';
            }
            
            if($rec->state == 'closed' && $rec->current >= $rec->max){
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        
        // Да се показват само документе със закачения плъгин
        $documentOptions = core_Classes::getOptionsByInterface('doc_DocumentIntf', 'title');
        $documentIds = array_keys($documentOptions);
        foreach ($documentIds as $docClassId) {
            if(!cls::get($docClassId)->hasPlugin('doc_plg_Sequencer2')){
                unset($documentOptions[$docClassId]);
            }
        }
        
        $form->setOptions('class', $documentOptions);
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if($form->isSubmitted()){
            $rec = $form->rec;
            
            if($rec->min >= $rec->max){
                $form->setError('min,max', "Невъзможна стойност");
            }
            
            $query = self::getQuery();
            $query->where("#class = {$rec->class} AND #id != '{$rec->id}'");
            while ($exRange = $query->fetch()){
                if(!($exRange->max <= $rec->min || $exRange->min >= $rec->max)){
                    $form->setError('min,max', "Има препокриване с|* <b>{$exRange->min} - {$exRange->max}</b>");
                }
            }
            
            if(isset($rec->current)){
                if($rec->max < $rec->current){
                    $form->setError('max', "Горната граница не може да е по-малка от текущия номер|*: <b>{$rec->current}</b>");
                }
            }
        }
    }
    
    
    /**
     * Показване на диапазона
     * 
     * @param int $id
     * 
     * @return string $res
     */
    public static function displayRange($id)
    {
        $rec = self::fetchRec($id);
        $res = "{$rec->min} - {$rec->max}";
        
        return $res;
    }
}