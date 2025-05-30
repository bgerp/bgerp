<?php

/**
 * Детайл за инкасирани безналични плащания
 *
 *
 * @category  bgerp
 * @package   cash
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cash_InternalMoneyTransferDetails extends core_Detail
{
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';


    /**
     * Кой може да създава?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да редактира?
     */
    public $canEdit = 'no_one';


    /**
     * Кой може да изтрива?
     */
    public $canDelete = 'ceo, acc, cash, bank';


    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'cash_Wrapper,plg_Sorting,plg_Created,plg_RowTools2';


    /**
     * Заглавие
     */
    public $title = 'Детайли на ВКТ за инкасиране';


    /**
     * Полета в листовия изглед
     */
    public $listFields = 'objectId=За инкасиране,paymentId=Плащане,amount=Сума';


    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'transferId';


    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
    public $fetchFieldsBeforeDelete = 'id,transferId,recId';



    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('transferId', 'key(mvc=cash_InternalMoneyTransfer)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('recId', 'key(mvc=cash_NonCashPaymentDetails)', 'input=hidden,mandatory,silent,tdClass=leftCol,caption=Запис');

        $this->setDbIndex('transferId,recId');
    }


    /**
     * Ако няма записи не вади таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        $data->listTableMvc->FLD('amount', 'double');
        $data->listTableMvc->FLD('paymentId', 'int', 'smartCenter');
    }


    /**
     * Рендиране на детайла
     *
     * @param stdClass $data
     *
     * @return core_ET $tpl
     */
    public function renderDetail_($data)
    {
        $tpl = new core_ET('');
        if($data->masterData->rec->operationSysId == 'nonecash2bank' && countR($data->recs)) {
            $tpl = parent::renderDetail_($data);
        }

        return $tpl;
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $nonCashRec = cash_NonCashPaymentDetails::fetch($rec->recId);
        $nonCashRow = cash_NonCashPaymentDetails::recToVerbal($nonCashRec);
        foreach (array('objectId', 'paymentId', 'amount') as $field) {
            $row->{$field} = $nonCashRow->{$field};
        }

        $currencyCode = cond_Payments::fetchField($nonCashRec->paymentId, 'currencyCode');
        $row->amount = currency_Currencies::decorate($row->amount, $currencyCode);
    }


    /**
     * Преди изтриване, се запомнят ид-та на перата
     */
    public static function on_AfterDelete($mvc, &$numRows, $query, $cond)
    {
        foreach ($query->getDeletedRecs() as $rec) {
            $nonCashRec = cash_NonCashPaymentDetails::fetch($rec->recId);
            $nonCashRec->transferredContainerId = null;
            cash_NonCashPaymentDetails::save($nonCashRec, 'transferredContainerId');
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'delete' && isset($rec)) {
            $masterState = cash_InternalMoneyTransfer::fetchField($rec->transferId, 'state');
            if(!in_array($masterState, array('pending', 'draft'))) {
                $requiredRoles = 'no_one';
            }
        }
    }
}