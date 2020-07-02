<?php


/**
 * Клас 'cond_Ranges' - Модел за диапазони на номерата на документите
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.11
 */
class cond_Ranges extends core_Manager
{
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'doc_Ranges';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created,cond_Wrapper,plg_RowTools2,plg_State2';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, admin';
    
    
    /**
     * Кой може да го редактира?
     */
    public $canWrite = 'ceo, admin';
    
    
    /**
     * Кой може да променя състоянието?
     */
    public $canChangestate = 'ceo, admin';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo, admin';
    
    
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
    public $listFields = 'id,class,min,max,current,lastUsedOn,isDefault,users,roles,createdOn,createdBy,state';
    
    
    /**
     * Описание на модела на нишките от контейнери за документи
     */
    public function description()
    {
        // Информация за нишката
        $this->FLD('class', 'class(interface=doc_DocumentIntf,select=title)', 'mandatory,caption=Документ');
        $this->FLD('min', 'bigint(min=0)', 'mandatory,caption=Долна граница');
        $this->FLD('max', 'bigint(Min=0)', 'mandatory,caption=Горна граница');
        $this->FLD('isDefault', 'enum(no=Не,yes=Да)', 'maxRadio=1,caption=По подразбиране,notNull,value=no');
        $this->FLD('roles', 'keylist(mvc=core_Roles,select=role,groupBy=type,orderBy=orderByRole)', 'caption=Достъп->Роли,autohide');
        $this->FLD('users', 'userList', 'caption=Достъп->Потребители,autohide');
        
        $this->FLD('current', 'bigint', 'input=none,caption=Текущ');
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
    public static function add($class, $min, $max, $users = null, $roles = null, $systemId = null)
    {
        $mvc = cls::get($class);
        
        expect($max > $min && (empty($min) || ($min && type_Int::isInt($min))) && type_Int::isInt($max));
        expect($users || $roles);
        $usersKeylist = isset($users) ? keylist::fromArray(arr::make($users, true)) : null;
        $rolesKeylist = isset($roles) ? core_Roles::getRolesAsKeylist($roles) : null;
        expect(!empty($usersKeylist) || !empty($rolesKeylist));
        
        $rec = (object)array('class' => $mvc->getClassId(), 'min' => $min, 'max' => $max, 'users' => $usersKeylist, 'roles' => $rolesKeylist);
        if(isset($systemId)){
            $rec->systemId = $systemId;
        }
        
        $exRec = $fields = null;
        if (!cls::get(get_called_class())->isUnique($rec, $fields, $exRec)) {
            $rec->id = $exRec->id;
        } else {
            $rec->state = 'active';
            
            if(!self::count("#class = {$mvc->getClassId()}")){
                $rec->isDefault = 'yes';
            }
        }
        
        return self::save($rec);
    }
    
    
    /**
     * Кои диапазони на класа може да избира потребителя
     * 
     * @param mixed $class - клас
     * @param int|null $cu - текущ потребител
     * 
     * @return array $res  - достъпните диапазони
     */
    public static function getAvailableRanges($class, $cu = null)
    {
        $cu = isset($cu) ? $cu : core_Users::getCurrent();
        
        $res = array();
        $mvc = cls::get($class);
        
        $query = self::getQuery();
        $query->where("#class = {$mvc->getClassId()} AND #state = 'active'");
        $query->where("#current IS NULL OR (#current IS NOT NULL AND #current < #max)");
        $query->orderBy('min', 'ASC');
        while($rec = $query->fetch()){
            
            // Диапазона е достъпен ако потребителя е ceo или е изрично посочен или има някоя от посочените роли или диапазона е без ограничение
            if(haveRole('ceo', $cu) || (keylist::isIn($cu, $rec->users) || haveRole($rec->roles, $cu) || (empty($rec->users) && empty($rec->roles)))){
                $res[$rec->id] = self::displayRange($rec);
            }
        }
        
        return $res;
    }
    
    
    /**
     * Кой е дефолтния диапазон, който може да избере потребителя
     * 
     * @param mixed $class - клас
     * @param int|null $cu - текущ потребител
     * 
     * @return int         - дефолтния диапазон
     */
    public static function getDefaultRangeId($class, $cu = null)
    {
        $cu = isset($cu) ? $cu : core_Users::getCurrent();
        
        // Ако има достъп до само един диапазон, той е дефолтния
        $mvc = cls::get($class);
        $ranges = self::getAvailableRanges($class, $cu);
        if(countR($ranges) == 1){
            
            return key($ranges);
        }
        
        // Ако глобалния дефолтен за класа е достъпен за потребителя е той
        $globalDefaultrangeId = self::fetchField("#class={$mvc->getClassId()} AND #state = 'active' AND #isDefault = 'yes'", 'id');
        if(isset($globalDefaultrangeId) && array_key_exists($globalDefaultrangeId, $ranges)){
           
            return $globalDefaultrangeId;
        }
        
        // Ако има повече от 1 диапазон и нито един не е дефолтен, то дефолтен ще е този с по малко ид
        if(countR($ranges) > 1){
            
            return key($ranges);
        }
        
        // Ако няма глобален дефолт и няма налични тогава дефолтен е първия свободен въобще
        if(!isset($globalDefaultrangeId)){
            $query = self::getQuery("#class={$mvc->getClassId()} AND #state = 'active'");
            $query->orderBy('id', 'ASC');
            $query->limit(1);
            
            
            if($foundRec = $query->fetch()){
                
                return $foundRec->id;
            }
        }
        
        // В краен вариант връщаме глобалния дефолт
        return $globalDefaultrangeId;
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
            
            
            $rec->isDefault = 'no';
        }
        
        self::save($rec, 'current,isDefault,state');
    }
    
    
    
    
    public function setNextDefault($class)
    {
        if($rec->isDefault == 'yes'){
            $query = self::getQuery();
            $query->where("#id != {$rec->id} AND #class = {$rec->class}");
            $query->orderBy('id', 'ASC');
            if($nextRec = $query->fetch()){
                $nextRec->isDefault = 'yes';
                self::save($nextRec, 'isDefault');
            }
        }
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
        
        if($form->rec->createdBy == core_Users::SYSTEM_USER){
            foreach (array('class', 'min', 'max', 'roles', 'users') as $field){
                $form->setField($field, 'input=none');
            }
        }
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
                $form->setError('min,max', "Горната граница трябва да е по-голяма от горната");
            }
            
            $query = self::getQuery();
            $query->where("#class = {$rec->class} AND #id != '{$rec->id}'");
            $query->show("min,max");
            $ranges = $query->fetchAll();
            
            foreach ($ranges as $exRange){
                if(!($exRange->max <= $rec->min || $exRange->min >= $rec->max)){
                    $form->setError('min,max', "Има припокриване с|* <b>{$exRange->min} - {$exRange->max}</b>");
                } elseif($rec->max == $exRange->min){
                    $form->setError('max', "Горната граница се припокрива с долната на друг диапазон|* <b>{$exRange->min} - {$exRange->max}</b>");
                } elseif($rec->min == $exRange->max){
                    $form->setError('min', "Долната граница се припокрива с горната на друг диапазон|* <b>{$exRange->min} - {$exRange->max}</b>");
                }
            }
            
            if(isset($rec->current)){
                if($rec->max < $rec->current){
                    $form->setError('max', "Горната граница не може да е по-малка от текущия номер|*: <b>{$rec->current}</b>");
                } elseif($rec->min > $rec->current){
                    $form->setError('min', "Долната граница не може да е по-голяма от текущия номер|*: <b>{$rec->current}</b>");
                }
            }
            
            // Ако диапазона ще е дефолтен, всички останали няма да са дефолтни
            if($rec->isDefault == 'yes'){
                $id = $rec->id;
                array_walk($ranges, function($a) use ($id){
                    if($id != $a->id) {
                        $a->isDefault = 'no';
                    }
                });
                
                if(countR($ranges)){
                    $mvc->saveArray($ranges, 'id,isDefault');
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