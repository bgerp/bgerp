<?php



/**
 * Мениджър на баланси
 *
 *
 * @category  all
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_Balances extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Оборотни ведомости";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_State, acc_Wrapper,Accounts=acc_Accounts,
                    plg_Sorting';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'acc_BalanceDetails';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Оборотна ведомост';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin,acc';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,acc';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin,acc';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,acc';
    
    
    /**
     * @var acc_Accounts
     */
    var $Accounts;
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('periodId', 'key(mvc=acc_Periods,select=title)', 'caption=Период,column=none,mandatory');
        $this->FLD('fromDate', 'date', 'input=none,caption=Период->от');
        $this->FLD('toDate', 'date', 'input=none,caption=Период->до');
        $this->FLD('state', 'enum(draft=Горещ,active=Активен,rejected=Изтрит)', 'caption=Тип,input=none');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Single()
    {
        if ($accountId = Request::get('accId', 'int')) {
            $this->accountRec = $this->Accounts->fetch($accountId);
        }
        
        return parent::act_Single();
    }
    
    
    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action)
    {
        if ($mvc->accountRec) {
            if ($action == 'edit' || $action == 'delete') {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function on_AfterPrepareSingleFields($mvc, $data)
    {
        if ($mvc->accountRec) {
            $data->singleFields = array();
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на титлата в единичния изглед
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
     * Извиква се преди вкарване на запис в таблицата на модела
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
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_AfterSave($mvc, &$id, $rec)
    {
        $mvc->acc_BalanceDetails->calculateBalance($rec);
    }
    
    
    /**
     * @todo Чака за документация...
     */
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
