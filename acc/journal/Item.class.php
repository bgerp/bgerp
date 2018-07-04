<?php

class acc_journal_Item
{
    /**
     *
     * @var int key(mvc=acc_Items)
     */
    public $id;
    
    /**
     *
     * @var int key(mvc=core_Classes)
     */
    private $classId;
    
    /**
     * @var int key(mvc=$classId)
     */
    private $objectId;
    
    /**
     *
     * @var stdClass
     */
    public $itemRec = null;
    
    
    /**
     * Конструктор
     *
     * @param int|array $classId
     * @param int       $objectId
     */
    public function __construct($classId, $objectId = null)
    {
        if (isset($classId) && is_array($classId)) {
            acc_journal_Exception::expect(count($classId) == 2, 'Масива трябва да е от два елемента');
            list($classId, $objectId) = $classId;
            acc_journal_Exception::expect($classId, 'Не е подаден клас');
            $classId = core_Classes::getId($classId);
        }
        
        if (!isset($objectId)) {
            acc_journal_Exception::expect(is_null($classId) || is_numeric($classId), 'Не е подаден клас');
            
            $this->id = $classId;
            
            if ($this->id) {
                acc_journal_Exception::expect($this->itemRec = $this->fetchItemRecById($this->id), 'Липсва перо');
                $this->classId = $this->itemRec->classId;
                $this->objectId = $this->itemRec->objectId;
            }
        } else {
            acc_journal_Exception::expect(is_numeric($objectId), 'Невалидно ид');
            
            $this->classId = $classId;
            $this->objectId = $objectId;
        }
    }
    
    
    /**
     * Дали перото поддържа зададения интерфейс?
     *
     * @param  int|string $iface име или id на интерфейс (@see core_Interfaces)
     * @return boolean
     */
    public function implementsInterface($iface)
    {
        if (empty($iface)) {
            return empty($this->classId);
        }
        
        if (empty($this->classId)) {
            return false;
        }
        
        if (is_numeric($iface)) {
            acc_journal_Exception::expect($iface = core_Interfaces::fetchField($iface, 'name'), 'Липсващ интерфейс');
        }
       
        // Ако перото е системно (класа му е acc_Items) то винаги отговаря на интерфейса
        if ($this->classId == acc_Items::getClassId()) {
            return true;
        }
        
        return cls::haveInterface($iface, $this->classId);
    }
    
    /**
     * "Засилва" записа
     */
    public function force($listId)
    {
        if ($this->classId && $this->objectId) {
            $itemId = acc_Items::force($this->classId, $this->objectId, $listId);
        } elseif (isset($this->id)) {
            $itemId = $this->id;
        }
        
        if (isset($this->id)) {
            acc_journal_Exception::expect($this->id == $itemId, 'Грешно ид');
        }
        
        $this->id = $itemId;
        
        return $this->id;
    }
    
    /**
     * Връща името на класа на регистъра на перото
     */
    public function className()
    {
        if (empty($this->classId)) {
            return 'Неизвестен клас';
        }
        
        return core_Cls::getClassName($this->classId);
    }
    
    
    /**
     * Дали перото е затворено
     */
    public function isClosed()
    {
        if (!$this->id) {
            $this->id = acc_Items::fetchItem($this->classId, $this->objectId)->id;
        }
        
        // Ако има такова перо извличаме му състоянието
        if ($this->id) {
            $state = acc_Items::fetchField($this->id, 'state');
        }
        
        return ($state == 'closed') ? true : false;
    }
    
    
    /**
     * Връща записа отговарящ на това ид
     */
    public function fetchItemRecById($id)
    {
        $Items = cls::get('acc_Items');
        $cache = $Items->getCachedItems();
        
        return $cache['items'][$id];
    }
}
