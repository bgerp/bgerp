<?php

/**
 * Мениджър на баланси
 */
class acc_Balances extends core_Master
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Баланси";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_State, acc_Wrapper,Accounts=acc_Accounts,
                    plg_Sorting';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $details = 'acc_BalanceDetails';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $singleTitle = 'Счетоводен баланс';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canList = 'admin,acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canRead = 'admin,acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,acc';
    
    
    /**
     * @var acc_Accounts
     */
    var $Accounts;
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('periodId', 'key(mvc=acc_Periods,select=title)', 'caption=Период,column=none,mandatory');
        $this->FLD('fromDate', 'date', 'input=none,caption=Период->от');
        $this->FLD('toDate', 'date', 'input=none,caption=Период->до');
        $this->FLD('state', 'enum(draft=Горещ,active=Активен,rejected=Изтрит)', 'caption=Тип,input=none');
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function act_Single()
    {
        if ($accountId = Request::get('accId', 'int')) {
            $this->accountRec = $this->Accounts->fetch($accountId);
        }
        
        return parent::act_Single();
    }
    
    
    /**
     *  Извиква се след изчисляването на необходимите роли за това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action)
    {
        if ($this->accountRec) {
            if ($action == 'edit') {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_AfterPrepareSingleFields($mvc, $data)
    {
        if ($mvc->accountRec) {
            $data->singleFields = array();
        }
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_AfterPrepareSingleTitle($mvc, $data)
    {
        $row = $mvc->recToVerbal($data->rec, 'fromDate, toDate');
        
        if ($mvc->accountRec) {
            $data->title = $mvc->accountRec->num . '. ' . $mvc->accountRec->title . ': ';
        } else {
            $data->title = 'Обобщен';
        }
        $data->title .= ' баланс за периода от ' .
        $row->fromDate . ' до ' . $row->toDate;
    }
    
    
    /**
     *  Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_BeforeSave($mvc, &$id, $rec)
    {
        $Periods = &cls::get('acc_Periods');
        $periodRec = $Periods->fetch($rec->periodId);
        
        $rec->baseBalanceId = $mvc->getBaseBalanceId($periodRec);
        $rec->fromDate = $periodRec->start;
        $rec->toDate = $periodRec->end;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_AfterSave($mvc, &$id, $rec)
    { 
        $mvc->acc_BalanceDetails->calculateBalance($rec);
    }
    
    private function getBaseBalanceId($periodRec)
    {
        $balanceId = NULL;
        
        $Periods = &cls::get('acc_Periods');
        
        if ($prevPeriodRec = $Periods->fetchPreviousPeriod($periodRec)) {
            $balanceId = $this->fetchField("#periodId = {$prevPeriodRec->id}", 'id');
        }
        
        return $balanceId;
    }
}
