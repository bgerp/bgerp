<?php
class acc_journal_Account
{
    
    /**
     * Запис на модела acc_Accounts
     *
     * @var stdClass
     */
    public $rec;
    
    
    /**
     * Конструктор
     *
     * @param stdClass|string $rec systemId на сметка или запис на модела acc_Accounts
     */
    public function __construct($rec)
    {
        if (is_scalar($rec)) {
            $systemId = $rec;
            
            acc_journal_Exception::expect(
                $rec = acc_Accounts::fetch(array("#systemId = '[#1#]'", $systemId)),
                "Липсва сметка със `systemId`={$systemId}",
                array('redirect' => array('acc_Accounts', 'list'))
            );
        }
        
        $this->rec = $rec;
    }
    
    
    /**
     * Фабрика за създаване на acc_journal_Account (този клас) по първичен ключ
     *
     * @param int $id key(mvc=acc_Accounts)
     */
    public static function byId($id)
    {
        acc_journal_Exception::expect(
            $rec = acc_Accounts::fetch($id),
            "Липсва сметка с `id`={$id}",
            array('redirect' => array('acc_Accounts', 'list'))
        );
        
        return new static($rec);
    }
    
    /**
     * @todo Чака за документация...
     */
    public function __get($name)
    {
        return $this->rec->{$name};
    }
    
    
    /**
     * Допустмо ли е тази сметка да се дебитира/кредитира със зададените аналитичности?
     *
     * Допустимостта се определя от:
     *
     * o броят на аналитичностите на сметката да съвпада с броя на зададените пера
     * o N-тото перо в $items да поддържа интерфейса на N-тата номенклатура-аналитичност
     * о N-тото перо в $items да има зададено количество точно тогава, когато N-тата
     * номенклатура-аналитичност на сметката e измерима.
     *
     * @param  array   $items
     * @return boolean
     */
    public function accepts($items)
    {
        $countAnalit =
        intval(isset($this->rec->groupId1))
        + intval(isset($this->rec->groupId2))
        + intval(isset($this->rec->groupId3));
        
        // колкото са пера - толкова аналитичности на сметката
        acc_journal_Exception::expect(
 
            true || $countAnalit == count($items),
            sprintf(
                "Броя на аналитичностите на сметка '%s' (%d) не съвпада с броя на подадените пера (%d)",
                $this->rec->systemId,
                $countAnalit,
                count($items)
            )
        );
        
        /* @var $item acc_journal_Item */
        foreach (array_values($items) as $N => $item) {
            $nn = $N + 1;
            
            acc_journal_Exception::expect(
                $listId = $this->rec->{"groupId{$nn}"},
                sprintf(
                    "{$this->rec->systemId}: на перо #%d(%s) не съответства аналитичност на сметката",
                    $nn,
                    $item->className()
                ),
                array('redirect' => array('acc_Accounts', 'list'))
            );
            
            // Съпоставка на интерфейсите
            $listInterfaceId = acc_Lists::fetchField($listId, 'regInterfaceId');
            
            if (!empty($listInterfaceId)) {
                acc_journal_Exception::expect(
                    $item->implementsInterface($listInterfaceId),
                    sprintf(
                        "{$this->rec->systemId}: перо #%d(%s) не поддържа интерфейс %s",
                        $nn,
                        $item->className(),
                        core_Interfaces::fetchField($listInterfaceId, 'name')
                    ),
                    (array) $items,
                    array('redirect' => array('acc_Accounts', 'list'))
                );
            }
        }
        
        return true;
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
    
    
    /**
     * Има ли сметката зададана стратегия за изчисляване на цената при кредитиране
     *
     * @return boolean
     */
    public function hasStrategy()
    {
        return !empty($this->rec->strategy);
    }
}
