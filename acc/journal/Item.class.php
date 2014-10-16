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
    public $itemRec = NULL;
    
    
    /**
     * Конструктор
     *
     * @param int|array $classId
     * @param int $objectId
     */
    public function __construct($classId, $objectId = NULL)
    {
        if (isset($classId) && is_array($classId)) {
            expect(count($classId) == 2);
            list($classId, $objectId) = $classId;
            $classId = core_Classes::getId($classId);
        }
        
        if (!isset($objectId)) {
            expect(is_null($classId) || is_numeric($classId), (array)$classId, $objectId);
            
            $this->id = $classId;
            
            if ($this->id) {
                expect($this->itemRec = acc_Items::fetch($this->id), func_get_args(), $classId, $objectId);
                $this->classId  = $this->itemRec->classId;
                $this->objectId = $this->itemRec->objectId;
            }
        } else {
            expect(is_numeric($objectId));
            
            $this->classId  = $classId;
            $this->objectId = $objectId;
        }
    }
    
    
    /**
     * Дали перото поддържа зададения интерфейс?
     *
     * @param int|string $iface име или id на интерфейс (@see core_Interfaces)
     * @return boolean
     */
    public function implementsInterface($iface)
    {
        if (empty($iface)) {
            return empty($this->classId);
        }
        
        if (empty($this->classId)) {
            return FALSE;
        }
        
        if (is_numeric($iface)) {
            expect($iface = core_Interfaces::fetchField($iface, 'name'));
        }
        
        return cls::haveInterface($iface, $this->classId);
    }
    
    /**
     * "Засилва" записа
     */
    public function force($listId)
    {
        if($this->classId && $this->objectId){
            $itemId = acc_Items::force($this->classId, $this->objectId, $listId);
        } elseif(isset($this->id)) {
            $itemId = $this->id;
        }
        
        if (isset($this->id)) {
            expect($this->id == $itemId);
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
        if(!$this->id){
            $this->id = acc_Items::fetchItem($this->classId, $this->objectId)->id;
        }
        
        // Ако има такова перо извличаме му състоянието
        if($this->id){
            $state = acc_Items::fetchField($this->id, 'state');
        }
        
        return ($state == 'closed') ? TRUE : FALSE;
    }
}
