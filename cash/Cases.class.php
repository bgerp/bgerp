<?php


/**
 * Мениджър на Каси
 *
 *
 * @category  bgerp
 * @package   cash
 *
 * @author    Milen Georgiev <milen@download.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cash_Cases extends core_Master
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'acc_RegisterIntf, cash_CaseAccRegIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Фирмени каси';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Каса';
    
    
    /**
     * Икона за единичен изглед
     */
    public $singleIcon = 'img/16/safe-icon.png';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name,totalBlAmount,cashiers,activateRoles,selectUsers,selectRoles';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, acc_plg_Registry, cash_Wrapper, bgerp_plg_FLB, plg_Current, plg_StyleNumbers, doc_FolderPlg, plg_Created, plg_Rejected, plg_State, plg_Modified, doc_plg_Close, deals_plg_AdditionalConditions';


    /**
     * Полета за допълнителни условие към документи
     * @see deals_plg_AdditionalConditions
     */
    public $additionalConditionsToDocuments = 'sales_Sales,purchase_Purchases';


    /**
     * Кой може да пише
     */
    public $canWrite = 'ceo, admin';
    
    
    /**
     * Кой може да пише
     */
    public $canReject = 'ceo, admin';
    
    
    /**
     * Кой може да пише
     */
    public $canRestore = 'ceo, admin';
    
    
    /**
     * Кой  може да вижда счетоводните справки?
     */
    public $canReports = 'ceo,cash,acc,cashAll';
    
    
    /**
     * Кой  може да вижда счетоводните справки?
     */
    public $canAddacclimits = 'ceo,cashMaster,accMaster,accLimits';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, cash, cashAll';
    
    
    /**
     * Кой може да активира?
     */
    public $canActivate = 'ceo, cash';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, cash, cashAll';
    
    
    /**
     * Детайли на този мастър обект
     *
     * @var string|array
     */
    public $details = 'AccReports=acc_ReportDetails';
    
    
    /**
     * По кои сметки ще се правят справки
     */
    public $balanceRefAccounts = '501,502';
    
    
    /**
     * По кой итнерфейс ще се групират сметките
     */
    public $balanceRefGroupBy = 'cash_CaseAccRegIntf';
    
    
    /**
     * Всички записи на този мениджър автоматично стават пера в номенклатурата със системно име
     * $autoList.
     *
     * @see acc_plg_Registry
     *
     * @var string
     */
    public $autoList = 'case';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'cash/tpl/SingleLayoutCases.shtml';
    
    
    /**
     * Да се създаде папка при създаване на нов запис
     */
    public $autoCreateFolder = 'instant';
    
    
    /**
     * Поле за избор на потребителите, които могат да активират обекта
     *
     * @see bgerp_plg_FLB
     */
    public $canActivateUserFld = 'cashiers';


    /**
     * Ключ с който да се заключи ъпдейта на таблицата
     */
    const SYNC_LOCK_KEY = 'syncCashBlAmount';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar(255)', 'caption=Наименование,mandatory');
        $this->FLD('cashiers', 'userList(roles=cash|ceo,showClosedUsers=no)', 'caption=Контиране на документи->Потребители');
        $this->FLD('autoShare', 'enum(yes=Да,no=Не)', 'caption=Споделяне на сделките с другите отговорници->Избор,notNull,default=yes,maxRadio=2');
        $this->FLD('defaultPaymentType', 'key(mvc=cond_Payments,select=title,allowEmpty)', 'caption=Безналичен метод на плащане по подразбиране->Избор');
        $this->FLD('blAmount', 'double(decimals=2)', 'caption=Наличност->Сума,input=none,notNull,value=0');
        $this->FLD('blData', 'blob(serialize, compress)', 'caption=Кеширани данни->От баланса,input=none');
        $this->FLD('posData', 'blob(serialize, compress)', 'caption=Кеширани данни->От POS,input=none');
        $this->FLD('blAmountInWaitingReceipts', 'double(decimals=2)', 'caption=Наличност->По чакащи бележки,input=none,notNull,value=0');
        $this->FNC('totalBlAmount', 'double(decimals=2)', 'caption=Наличност->В брой,input=none,notNull,value=0,single=show');

        $this->setDbUnique('name');
    }


    /**
     * Изпълнява се преди преобразуването към вербални стойности на полетата на записа
     */
    protected static function on_BeforeRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (is_object($rec)) {
            if (isset($fields['-list'])) {
                $rec->name = $mvc->singleTitle . " \"{$rec->name}\"";
            }

            $rec->totalBlAmount = $rec->blAmount + $rec->blAmountInWaitingReceipts;
        }
    }


    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    protected static function on_AfterRecToVerbal(&$mvc, &$row, &$rec, $fields = array())
    {
        $stateClass = ($rec->state == 'rejected') ? ' state-rejected' : (($rec->state == 'closed' ? ' state-closed': ' state-active'));
        $row->STATE_CLASS = ($row->STATE_CLASS ?? '') . $stateClass;
        if($mvc->getCurrent('id', false) != $rec->id){
            $row->ROW_ATTR['class'] = $stateClass;
        }

        if (isset($fields['-list'])) {
            if(!empty($rec->blAmountInWaitingReceipts)){
                $blAmountInWaitingReceipts = $mvc->getFieldType('blAmountInWaitingReceipts')->toVerbal($rec->blAmountInWaitingReceipts);
                $row->totalBlAmount = ht::createHint($row->totalBlAmount, "Включено от чакащи ПОС бележки|*: {$blAmountInWaitingReceipts}", 'notice', false);
            }
        }

        if (isset($fields['-single'])) {
            $row->hint = ht::createHint('', 'Общо от всички валути, преизчислени по централен курс', 'notice', false);

            $row->totalBlAmount = currency_Currencies::decorate($row->totalBlAmount, $row->currencyId ?? null, true);
            $row->totalBlAmount = ht::styleNumber($row->totalBlAmount, $rec->totalBlAmount);
        }
    }


    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    protected static function on_AfterPrepareListFields($mvc, $data)
    {
        $data->listFields['totalBlAmount'] .= '|*, ' . acc_Periods::getBaseCurrencyCode();
    }


    /**
     * След рендиране на еденичния изглед
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        $rec = &$data->rec;
        $row = &$data->row;

        $inCashRecs = $inCashRows = array();
        foreach (array('blData' => 'blQuantity', 'posData' => 'posQuantity') as $varName => $varFld){
            $d = $rec->{$varName};
            if(is_array($d)){
                foreach ($d as $cId => $cData){
                    if(!array_key_exists($cId, $inCashRecs)){
                        $inCashRecs[$cId] = (object)array('currencyId' => $cId, 'blQuantity' => 0, 'posQuantity' => 0, 'total' => 0);
                    }
                    $inCashRecs[$cId]->{$varFld} += $cData->quantity;
                    $inCashRecs[$cId]->total += $cData->quantity;
                }
            }
        }

        $Double = core_Type::getByName('double(decimals=2)');
        foreach ($inCashRecs as $cId => $cRec){
            $cRow = new stdClass();
            $cRow->currencyId = currency_Currencies::getCodeById($cId);
            $cRow->blQuantity = ht::styleNumber($Double->toVerbal($cRec->blQuantity), $cRec->blQuantity);
            $cRow->posQuantity = ht::styleNumber($Double->toVerbal($cRec->posQuantity), $cRec->posQuantity);
            $cRow->total =  "<b>" . ht::styleNumber($Double->toVerbal($cRec->total), $cRec->total) . "</b>";
            $inCashRows[$cId] = $cRow;
        }

        $fieldset = new core_FieldSet();
        $fieldset->FLD('currencyId', 'varchar', 'smartCenter');
        $fieldset->FLD('total', 'double');
        $fieldset->FLD('blQuantity', 'double');
        $fieldset->FLD('posQuantity', 'double');

        $table = cls::get('core_TableView', array('mvc' => $fieldset));
        $tableHtml = $table->get($inCashRows, 'currencyId=Валута,total=Наличност,blQuantity=Осчетоводени,posQuantity=От ПОС (чакащи)');
        $row->inCashData = $tableHtml;
    }


    /**
     * Подготвя и осъществява търсене по каса, изпозлва се в касовите документи
     *
     * @param stdClass $data
     * @param array    $fields - масив от полета в полета в които ще се
     *                         търси по caseId
     */
    public static function prepareCaseFilter(&$data, $fields = array(), $operationFieldName = null)
    {
        $data->listFilter->FNC('case', 'key(mvc=cash_Cases,select=name,allowEmpty)', 'caption=Каса,placeholderType=all,width=10em,silent');
        $data->listFilter->showFields .= (!empty($data->listFilter->showFields) ? ',' : '') . 'case';
        $data->listFilter->setDefault('case', static::getCurrent('id', false));

        if($operationFieldName){
            $operationOptions = array('all' => 'Всички');
            $operationOptions += $data->query->mvc->getFieldType($operationFieldName)->options;
            $data->listFilter->FNC('operation', 'varchar', 'caption=Операция');
            $data->listFilter->setOptions('operation', $operationOptions);
            $data->listFilter->showFields .= (!empty($data->listFilter->showFields) ? ',' : '') . 'operation';
            $data->listFilter->setDefault('operation', 'all');
        }

        $data->listFilter->input();
        
        if ($filter = $data->listFilter->rec) {
            if (!empty($filter->case)) {
                foreach ($fields as $i => $fld) {
                    $or = !(($i === 0));
                    $data->query->where("#{$fld} = {$filter->case}", $or);
                }
            }

            if(!empty($filter->operation) && $filter->operation != 'all'){
                $data->query->where("#{$operationFieldName} = '{$filter->operation}'");
            }
        }
    }


    /**
     * След рендиране на лист таблицата
     */
    protected static function on_AfterRenderListTable($mvc, &$tpl, &$data)
    {
        if (!countR($data->recs)) return;

        $total = 0;
        foreach ($data->recs as $rec) {
            $total += $rec->blAmount;
        }

        $Double =  core_Type::getByName('double(decimals=2)');
        $totalVerbal = $Double->toVerbal($total);
        $total = ht::styleNumber($totalVerbal, $total);
        
        $currencyId = acc_Periods::getBaseCurrencyCode();
        $state = (Request::get('Rejected', 'int')) ? 'rejected' : 'closed';

        $remainingCols = 5;
        $colspan = countR($data->listFields) - $remainingCols;
        $lastRow = new ET("<tr style='text-align:right' class='state-{$state}'><td colspan='{$colspan}'>[#caption#]: &nbsp; <b>[#total#]</b> </td><td colspan='{$remainingCols}'>&nbsp;</td></tr>");
        $lastRow->replace(tr('Общо'), 'caption');
        $lastRow->replace($total, 'total');
        $tpl->append($lastRow, 'ROW_AFTER');
    }
    
    
    /*******************************************************************************************
     *
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see cash_CaseAccRegIntf
     *
     ******************************************************************************************/
    
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     *
     * @param int $objectId
     */
    public static function getItemRec($objectId)
    {
        $self = cls::get(__CLASS__);
        $result = null;
        
        if ($rec = $self->fetch($objectId)) {
            $result = (object) array(
                'num' => $rec->id . ' cs',
                'title' => $rec->name,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     *
     * @param int $objectId
     */
    public static function itemInUse($objectId)
    {
        // @todo!
    }

    /**
     * Синхронизиране на запис от счетоводството с модела, Вика се от крон-а
     * (@see acc_Balances::cron_Recalc)
     *
     * @param array $arr
     */
    public static function sync($arr)
    {
        if (!core_Locks::obtain(self::SYNC_LOCK_KEY, 60, 3, 1)) {
            self::logWarning('Синхронизирането на касовите наличности е заключено от друг процес');

            return;
        }

        $query = self::getQuery();
        while($rec = $query->fetch()){
            $rec->blData = null;
            if(array_key_exists($rec->id, $arr)){
                $rec->blAmount = arr::sumValuesArray($arr[$rec->id]->currencies, 'amount');
                $rec->blData = $arr[$rec->id]->currencies;
            }
            cash_Cases::save($rec, 'blAmount,blData');
        }

        core_Locks::release(self::SYNC_LOCK_KEY);
    }


    /**
     * Изчисление на какви суми се очакват по чакащи пос бележки
     *
     * @param stdClass $rec
     * @return void
     */
    public static function updateAmountInWaitingReceipts($rec)
    {
        $rec = self::fetchRec($rec);
        if(empty($rec)) return;

        $pointQuery = pos_Points::getQuery();
        $pointQuery->where("#caseId = {$rec->id}");
        $pointQuery->show('id');
        $points = arr::extractValuesFromArray($pointQuery->fetchAll(), 'id');
        if(!countR($points)) return;

        $rQuery = pos_ReceiptDetails::getQuery();
        $rQuery->EXT('pointId', 'pos_Receipts', 'externalName=pointId,externalKey=receiptId');
        $rQuery->EXT('state', 'pos_Receipts', 'externalName=state,externalKey=receiptId');
        $rQuery->EXT('rCreatedOn', 'pos_Receipts', 'externalName=createdOn,externalKey=receiptId');
        $rQuery->EXT('rChange', 'pos_Receipts', 'externalName=change,externalKey=receiptId');
        $rQuery->where("#state = 'waiting' AND #action = 'payment|-1'");
        $rQuery->in("pointId", $points);

        $today = dt::today();
        $posData = $changeDeductedIn = array();
        while($rRec = $rQuery->fetch()) {
            $currencyId = acc_Periods::getBaseCurrencyId($rRec->rCreatedOn);
            $currencyCode = acc_Periods::getBaseCurrencyCode($rRec->rCreatedOn);
            if(!array_key_exists($currencyId, $posData)){
                $posData[$currencyId] = (object)array('quantity' => 0, 'amount' => 0);
            }

            // В плащането в брой е въведената от клиента сума, затова се приспада рестото - веднъж
            // на бележка, както е и при контирането (@see pos_ReceiptDetails::fetchReportData)
            $amount = $rRec->amount;
            if(!empty($rRec->rChange) && !array_key_exists($rRec->receiptId, $changeDeductedIn)){
                $amount -= $rRec->rChange;
                $changeDeductedIn[$rRec->receiptId] = true;
            }

            $posData[$currencyId]->quantity += $amount;
            $posData[$currencyId]->amount += currency_CurrencyRates::convertAmount($amount, $today, $currencyCode);
        }
        $rec->posData = countR($posData) ? $posData : null;
        $rec->blAmountInWaitingReceipts = countR($posData) ? arr::sumValuesArray($posData, 'amount') : 0;

        self::save($rec, 'blAmountInWaitingReceipts,posData');
    }
}
