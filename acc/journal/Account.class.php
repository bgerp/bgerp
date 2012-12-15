<?php
class acc_journal_Account
{
    /**
     *
     * @var stdClass
     */
    protected $rec;
     
    public function __construct($rec)
    {
        $this->rec = $rec;
    }


    public static function system($systemId)
    {
        expect($rec = acc_Accounts::fetch(array("#systemId = '[#1#]'", $systemId)), "Липсва сметка със `systemId`={$systemId}");

        return new static($rec);
    }


    public static function id($id)
    {
        expect($rec = acc_Accounts::fetch($id), "Липсва сметка с `id`={$id}");

        return new static($rec);
    }


    public function __get($name)
    {
        return $this->rec->{$name};
    }
    
    
    /**
     * Допустмо ли е тази сметка да се дебитира/кредитира със зададените аналитичности?
     * 
     * Допустимостта се определя от:
     * 
     *  o броят на аналитичностите на сметката да съвпада с броя на зададените пера
     *  o N-тото перо в $items да поддържа интерфейса на N-тата номенклатура-аналитичност 
     *  о N-тото перо в $items да има зададено количество точно тогава, когато N-тата
     *    номенклатура-аналитичност на сметката e измерима.
     *  
     * @param array $items
     * @return boolean
     */
    public function accepts($items)
    {
        $countAnalit = 
            intval(isset($this->rec->groupId1))
            + intval(isset($this->rec->groupId2))
            + intval(isset($this->rec->groupId3));

        // колкото са пера - толкова аналитичности на сметката 
        expect($countAnalit == count($items), "Броя на аналитичностите на сметката не съвпада с броя на перата");
        
        /* @var $item acc_journal_Entry */
        foreach (array_values($items) as $N=>$item) {
            $nn = $N+1;
            $listId = $this->rec->{"groupId{$nn}"};
            
            // Съпоставка на интерфейсите
            $listInterfaceId = acc_Lists::fetchField($listId, 'regInterfaceId');
            expect($item->implementsInterface($listInterfaceId), "Перото не поддържа нужния интерфейс");
        } 
        
        return TRUE;
    }
    
    
    /**
     * Има ли сметката размерна аналитичност?
     * 
     * По дефиниция, сметката може да има най-много една размерна аналитичност и тя задължително
     * трябва да е последната й аналитичност.
     * 
     * @return boolean
     */
    public function isDimensional()
    {
        return !empty($this->rec->groupId3) && acc_Lists::isDimensional($this->rec->groupId3)
            || !empty($this->rec->groupId2) && acc_Lists::isDimensional($this->rec->groupId2)
            || !empty($this->rec->groupId1) && acc_Lists::isDimensional($this->rec->groupId1);
    }
}
