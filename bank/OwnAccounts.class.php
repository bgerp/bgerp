<?php


/**
 * Банкови сметки на фирмата
 *
 *
 * @category  bgerp
 * @package   bank
 *
 * @author    Milen Georgiev <milen@download.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bank_OwnAccounts extends core_Master
{
    /**
     * Да се създаде папка при създаване на нов запис
     */
    public $autoCreateFolder = 'instant';
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'acc_RegisterIntf, bank_OwnAccRegIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, bank_Wrapper, acc_plg_Registry,
                     plg_Sorting, bgerp_plg_FLB, plg_Current, doc_plg_Close, plg_LastUsedKeys, doc_FolderPlg, plg_Rejected, plg_State, plg_Modified';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'bankAccountId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title, bankAccountId, currency=Валута, type, blAmount=Сума';
    
    
    /**
     * Кой може да активира?
     */
    public $canActivate = 'ceo, bank';
    
    
    /**
     * Поле за избор на потребителите, които могат да активират обекта
     *
     * @see bgerp_plg_FLB
     */
    public $canActivateUserFld = 'operators';
    
    
    /**
     * Кой може да оттегля?
     */
    public $canReject = 'ceo, admin';


    /**
     * Кой може да пише
     */
    public $canClose = 'ceo, admin';


    /**
     * Кой може да възстановява?
     */
    public $canRestore = 'ceo, admin';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'ceo, admin';
    
    
    /**
     * Кой може да редактира?
     */
    public $canEdit = 'ceo, admin, bankMaster';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'bank, ceo, bankAll';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, bank, bankAll';
    
    
    /**
     * Заглавие
     */
    public $title = 'Банкови сметки на фирмата';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Банкова сметка';
    
    
    /**
     * Кой  може да вижда счетоводните справки?
     */
    public $canAddacclimits = 'ceo, bankMaster, accMaster,accLimits';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Всички записи на този мениджър автоматично стават пера в номенклатурата със системно име $autoList
     */
    public $autoList = 'bankAcc';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'bank/tpl/SingleLayoutOwnAccount.shtml';
    
    
    /**
     * Икона за единичен изглед
     */
    public $singleIcon = 'img/16/own-bank.png';
    
    
    /**
     * Детайли на този мастър обект
     *
     * @var string|array
     */
    public $details = 'AccReports=acc_ReportDetails';
    
    
    /**
     * Кой  може да вижда счетоводните справки?
     */
    public $canReports = 'ceo,bank,acc,bankAll';
    
    
    /**
     * По кои сметки ще се правят справки
     */
    public $balanceRefAccounts = '503';
    
    
    /**
     * По кой интерфейс ще се групират сметките
     */
    public $balanceRefGroupBy = 'bank_OwnAccRegIntf';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('bankAccountId', 'key(mvc=bank_Accounts,select=iban)', 'caption=Сметка,input=none');
        $this->FLD('type', 'enum(current=Разплащателна,
                                 deposit=Депозитна,
                                 loan=Кредитна,
                                 personal=Персонална,
                                 capital=Набирателна)', 'caption=Тип,mandatory');
        $this->FLD('title', 'varchar(128)', 'caption=Наименование');
        $this->FLD('countries', 'keylist(mvc=drdata_Countries,select=commonNameBg)', 'caption=Държави, title=Използване по подразбиране за фирми от съответните държави');
        $this->FLD('comment', 'richtext(bucket=Notes,rows=6)', 'caption=Бележки');
        $this->FLD('operators', 'userList(roles=bank|ceo,showClosedUsers=no)', 'caption=Контиране на документи->Потребители');
        $this->FLD('autoShare', 'enum(yes=Да,no=Не)', 'caption=Споделяне на сделките с другите отговорници->Избор,notNull,default=yes,maxRadio=2');
        
        $this->setDbUnique('title');
    }
    
    
    /**
     * Връща дефолтната bankAccountId за съответна фирма
     * 
     * @param integer $countryId
     * 
     * @return integer|boolean
     */
    public static function getDefaultIdForCountry($countryId, $checkNull = true)
    {
        $query = self::getQuery();
        $query->limit(1);
        $query->likeKeylist('countries', $countryId);
        $query->where("#state != 'rejected' AND #state != 'closed'");

        if ($checkNull) {
            $query->orWhere('#countries IS NULL');
            $query->orderBy('countries', 'DESC');
        }
        
        $query->show('bankAccountId');
        $query->orderBy('modifiedOn', 'DESC');
        
        if ($rec = $query->fetch()) {
            
            return $rec->bankAccountId;
        }
        
        return false;
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    protected static function on_AfterRecToVerbal(&$mvc, &$row, &$rec, $fields = array())
    {
        $stateClass = ($rec->state == 'rejected') ? ' state-rejected' : (($rec->state == 'closed' ? ' state-closed': ' state-active'));
        $row->STATE_CLASS .= $stateClass;
        if($mvc->getCurrent('id', false) != $rec->id){
            $row->ROW_ATTR['class'] = $stateClass;
        }

        $row->bankAccountId = ht::createLink($row->bankAccountId, array('bank_Accounts', 'single', $rec->bankAccountId));
        
        if (isset($fields['-list'])) {
            if (bgerp_plg_FLB::canUse($mvc, $rec)) {
                $bankItem = acc_Items::fetchItem($mvc->getClassId(), $rec->id);
                $rec->blAmount = 0;
                
                // Намираме всички записи от текущия баланс за това перо
                if ($balRec = acc_Balances::getLastBalance()) {
                    $bQuery = acc_BalanceDetails::getQuery();
                    acc_BalanceDetails::filterQuery($bQuery, $balRec->id, $mvc->balanceRefAccounts, null, $bankItem->id);
                    
                    // Събираме ги да намерим крайното салдо на перото
                    while ($bRec = $bQuery->fetch()) {
                        $rec->blAmount += $bRec->blAmount;
                    }
                }
                
                // Обръщаме го в четим за хората вид
                $row->blAmount = ht::styleNumber(core_Type::getByName('double(decimals=2)')->toVerbal($rec->blAmount), $rec->blAmount);
            }
        }
        
        if ($rec->bankAccountId) {
            $currencyId = bank_Accounts::fetchField($rec->bankAccountId, 'currencyId');
            $row->currency = currency_Currencies::getCodeById($currencyId);
            $ownAccounts = bank_OwnAccounts::getOwnAccountInfo($rec->id);

            $row->bank = bank_Accounts::getVerbal($ownAccounts, 'bank');
            $row->bic = bank_Accounts::getVerbal($ownAccounts, 'bic');
            $row->conditionSaleBg = bank_Accounts::getVerbal($ownAccounts, 'conditionSaleBg');
            $row->conditionSaleEn = bank_Accounts::getVerbal($ownAccounts, 'conditionSaleEn');
        }
    }


    /**
     * Ако няма записи не вади таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        $data->listTableMvc->FLD('blAmount', 'int');
    }


    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    protected static function on_AfterPrepareListFields($mvc, $data)
    {
        $data->listFields['blAmount'] .= ', ' . acc_Periods::getBaseCurrencyCode();
    }
    
    
    /**
     * След рендиране на лист таблицата
     */
    protected static function on_AfterRenderListTable($mvc, &$tpl, &$data)
    {
        if (!countR($data->rows)) {
            
            return;
        }
        
        foreach ($data->recs as $rec) {
            $total += $rec->blAmount;
        }
        
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $total = $Double->toVerbal($total);
        
        if ($total < 0) {
            $total = "<span style='color:red'>{$total}</span>";
        }
        
        $currencyId = acc_Periods::getBaseCurrencyCode();
        $state = (Request::get('Rejected', 'int')) ? 'rejected' : 'closed';
        $colspan = countR($data->listFields) - 1;
        $lastRow = new ET("<tr style='text-align:right' class='state-{$state}'><td colspan='{$colspan}'>[#caption#]:&nbsp;<span class='cCode'>{$currencyId}</span>&nbsp;<b>[#total#]</b></td><td>&nbsp;</td></tr>");
        $lastRow->replace(tr('Общо'), 'caption');
        $lastRow->replace($total, 'total');
        
        $tpl->append($lastRow, 'ROW_AFTER');
    }
    
    
    /**
     * Обработка по формата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $form = &$data->form;
        $form->FNC('iban', 'iban_Type(64)', 'caption=IBAN / №,mandatory,before=type,refreshForm,removeAndRefreshForm=bic|bank,input');
        $form->FNC('currencyId', 'key(mvc=currency_Currencies, select=code,allowEmpty)', 'caption=Валута,mandatory,after=iban,input');
        $form->FNC('bic', 'varchar(12)', 'caption=BIC,after=currencyId,input');
        $form->FNC('bank', 'varchar(64)', 'caption=Банка,after=bic,input');
        $form->FNC('conditionSaleBg', 'richtext(rows=2)', 'caption=Допълнителни условия към Продажба->BG,autohide,input,after=comment');
        $form->FNC('conditionSaleEn', 'richtext(rows=2)', 'caption=Допълнителни условия към Продажба->EN,autohide,input,after=conditionSaleBg');
        $form->FNC('fromOurCompany', 'int', 'input=hidden');
        if (Request::get('fromOurCompany', 'int')) {
            $form->rec->fromOurCompany = true;
        }
        
        // При редакция се допълват полетата с тези от сметката
        if ($form->rec->id) {
            if (isset($form->rec->bankAccountId)) {
                $ibanRec = bank_Accounts::fetch($form->rec->bankAccountId);
                $form->setDefault('iban', $ibanRec->iban);
                $form->setDefault('bank', $ibanRec->bank);
                $form->setDefault('bic', $ibanRec->bic);
                $form->setDefault('currencyId', $ibanRec->currencyId);
                $form->setDefault('conditionSaleBg', $ibanRec->conditionSaleBg);
                $form->setDefault('conditionSaleEn', $ibanRec->conditionSaleEn);
            }
        }
    }
    
    
    /**
     * Пренасочва URL за връщане след запис към сингъл изгледа
     */
    protected static function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
        // Ако има форма, и тя е събмитната и действието е 'запис'
        if ($data->form && $data->form->isSubmitted() && $data->form->cmd == 'save') {
            if (isset($data->form->rec->fromOurCompany)) {
                $ourCompany = crm_Companies::fetchOurCompany();
                $data->retUrl = toUrl(array('crm_Companies', 'single', $ourCompany->id, 'Tab' => 'ContragentBankAccounts'));
            }
        }
    }
    
    
    /**
     * Проверка дали може да се добавя банкова сметка в ownAccounts(Ако броя
     * на собствените сметки отговаря на броя на сметките на Моята компания в
     * bank_Accounts то не можем да добавяме нова сметка от този мениджър
     *
     * @return bool TRUE/FALSE - можем ли да добавяме нова сметка
     */
    public function canAddOwnAccount()
    {
        $ourCompany = crm_Companies::fetchOurCompany();
        
        $accountsQuery = bank_Accounts::getQuery();
        $accountsQuery->where("#contragentId = {$ourCompany->id}");
        $accountsQuery->where("#contragentCls = {$ourCompany->classId}");
        $accountsNumber = $accountsQuery->count();
        $ownAccountsQuery = $this->getQuery();
        $ownAccountsNumber = $ownAccountsQuery->count();
        
        if ($ownAccountsNumber == $accountsNumber) {
            
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Извличане на цялата информация за сметката която е активна
     *
     * @return stdClass $acc - записа отговарящ на текущата ни сметка
     */
    public static function getOwnAccountInfo($id = null)
    {
        if ($id) {
            $ownAcc = static::fetch($id);
        } else {
            $ownAcc = static::fetch(static::getCurrent());
        }
        expect($ownAcc);
        
        if (!$ownAcc) {
            
            return false;
        }
        
        $acc = bank_Accounts::fetch($ownAcc->bankAccountId);
        $acc->currencyCode = currency_Currencies::getCodeById($acc->currencyId);
        expect($acc, $ownAcc);
        
        if (!$acc->bank) {
            $acc->bank = bglocal_Banks::getBankName($acc->iban);
        }
        
        if (!$acc->bic) {
            $acc->bic = bglocal_Banks::getBankBic($acc->iban);
        }
        
        return $acc;
    }
    
    
    /**
     * Изпълнява се след въвеждането на данните от формата
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = &$form->rec;
        
        if (!$form->gotErrors()) {
            if (isset($rec->iban)) {
                $accountRec = bank_Accounts::fetch(array("#iban = '[#1#]'", $rec->iban));

                if (!$accountRec) {
                    $form->setDefault('bank', bglocal_Banks::getBankName($rec->iban));
                    $form->setDefault('bic', bglocal_Banks::getBankBic($rec->iban));
                } else {
                    $form->setDefault('bank', $accountRec->bank);
                    $form->setDefault('bic', $accountRec->bic);
                    $form->setDefault('currencyId', $accountRec->currencyId);
                    $form->setDefault('conditionSaleBg', $accountRec->conditionSaleBg);
                    $form->setDefault('conditionSaleEn', $accountRec->conditionSaleEn);
                }
            }
        }
        
        if ($form->isSubmitted()) {
            $form->rec->_isSubmitted = true;

            if (empty($rec->bankAccountId)) {
                $accountRec = bank_Accounts::fetch(array("#iban = '[#1#]'", $rec->iban));
                
                // Проверка дали вече нямаме наша сметка с този IBAN
                if (self::fetchField("#bankAccountId = '{$accountRec->id}'")) {
                    $form->setError('iban', 'Вече има наша сметка с този|* IBAN');
                    
                    return;
                }
                
                // Проверка дали няма чужда сметка с този IBAN
                $ourCompany = crm_Companies::fetchOurCompany();
                if (!empty($accountRec)) {
                    if ($accountRec->contragentId != $ourCompany->id || $accountRec->contragentCls != $ourCompany->classId) {
                        $form->setError('iban', 'Подаденият IBAN принадлежи на сметка на друга фирма');
                        return;
                    }
                }
            } else {

                // Ако е редактиран IBAN проверява се дали новия не е вече добавен
                if(bank_Accounts::fetch(array("#iban = '[#1#]' AND #id != {$rec->bankAccountId}", $rec->iban))){
                    $form->setError('iban', 'Вече има наша сметка с този|* IBAN');
                    return;
                }
            }
            
            if (empty($rec->title)) {
                $rec->title = "{$rec->iban}, " . currency_Currencies::getCodeById($rec->currencyId);
            }
        }
    }
    
    
    /**
     * Извиква се преди запис в модела
     */
    protected static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        if ($rec->_isSubmitted === true) {
            $rec->bankAccountId = self::syncWithAccount($rec->bankAccountId, $rec->iban, $rec->currencyId, $rec->bank, $rec->bic, $rec->conditionSaleBg, $rec->conditionSaleEn);
        }
    }


    /**
     * Синхронизиране на банкова сметка с наша сметка
     *
     * @param $id
     * @param $iban
     * @param $currencyId
     * @param $bank
     * @param $bic
     * @param $commonConditionSaleBg
     * @param $commonConditionSaleEn
     * @return int
     */
    private static function syncWithAccount($id, $iban, $currencyId, $bank, $bic, $commonConditionSaleBg, $commonConditionSaleEn)
    {
        $save = false;
        $bank = ($bank) ? $bank : null;
        $bic = ($bic) ? $bic : null;
        $ourCompany = crm_Companies::fetchOurCompany();
        $newRec = (object) array('id' => $id, 'iban' => $iban, 'currencyId' => $currencyId, 'bank' => $bank, 'bic' => $bic, 'contragentId' => $ourCompany->id, 'contragentCls' => $ourCompany->classId, 'conditionSaleBg' => $commonConditionSaleBg, 'conditionSaleEn' => $commonConditionSaleEn);
        if (isset($id)) {
            $exRec = bank_Accounts::fetch($id);
            if (!is_null($newRec->iban) || !is_null($newRec->currencyId) || !is_null($newRec->bank) || !is_null($newRec->bic)) {
                foreach (array('iban', 'currencyId', 'bank', 'bic', 'conditionSaleBg', 'conditionSaleEn') as $fld) {
                    if ($exRec->{$fld} != $newRec->{$fld}) {
                        $save = true;
                    }
                }
            }
        } else {
            $save = true;
        }
        
        $res = $id;
        if ($save) {
            $res = bank_Accounts::save($newRec);
        }
        
        return $res;
    }
    
    
    /*******************************************************************************************
     *
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see crm_ContragentAccRegIntf
     *
     ******************************************************************************************/
    
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     *
     * @param int $objectId
     */
    public static function getItemRec($objectId)
    {
        $result = null;
        
        if ($rec = static::fetch($objectId)) {
            $account = bank_Accounts::fetch($rec->bankAccountId);
            
            $cCode = currency_Currencies::getCodeById($account->currencyId);
            $result = (object) array(
                'num' => $rec->id  . ' b',
                'title' => $cCode . ' - ' . $rec->title,
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
     * Връща Валутата и IBAN-a на всички наши сметки разделени с "-"
     *
     * @param bool        $selectIban
     * @param string|null $currencyCode
     * @param mixed|null  $onlyIds
     *
     * @return array $accounts
     */
    public static function getOwnAccounts($selectIban = true, $currencyCode = null, $onlyIds = null)
    {
        $Varchar = cls::get('type_Varchar');
        $accounts = array();
        $query = static::getQuery();
        $query->where("#state != 'rejected' AND #state != 'closed'");
        if (isset($onlyIds)) {
            $onlyIds = arr::make($onlyIds, true);
            $query->in('id', $onlyIds);
        }
        
        $cu = core_Users::getCurrent();
        
        while ($rec = $query->fetch()) {
            if (!bgerp_plg_FLB::canUse(__CLASS__, $rec, $cu, 'select')) {
                continue;
            }
            
            if (isset($rec->bankAccountId)) {
                $account = bank_Accounts::fetch($rec->bankAccountId);
                $cCode = currency_Currencies::getCodeById($account->currencyId);
                if (isset($currencyCode) && strtoupper($currencyCode) != $cCode) {
                    continue;
                }
                
                $verbal = ($selectIban === true) ? $Varchar->toVerbal($account->iban) : $rec->title;
                
                $accounts[$rec->id] = "{$cCode} - {$verbal}";
            }
        }
        
        return $accounts;
    }
    
    
    /**
     * Подготвя и осъществява търсене по банка, използва се
     * в банковите документи
     *
     * @param stdClass $data
     * @param array    $fields - масив от полета, в които ще се
     *                         търси по bankId
     */
    public static function prepareBankFilter(&$data, $fields = array())
    {
        $data->listFilter->FNC('own', 'key(mvc=bank_OwnAccounts,select=bankAccountId,allowEmpty)', 'caption=Сметка,silent');
        $data->listFilter->showFields .= ',own';
        $data->listFilter->setDefault('own', static::getCurrent('id', false));
        $data->listFilter->input();
        
        if ($filter = $data->listFilter->rec) {
            if ($filter->own) {
                foreach ($fields as $i => $fld) {
                    $or = ($i === 0) ? false : true;
                    $data->query->where("#{$fld} = {$filter->own}", $or);
                }
            }
        }
    }


    /**
     * Преди затваряне/отваряне на записа
     */
    protected static function on_AfterChangeState(core_Mvc $mvc, &$rec, &$newState)
    {
        $bRec = bank_Accounts::fetch($rec->bankAccountId);
        if($newState == 'closed'){
            $bRec->exState = $bRec->state;
            $bRec->state = 'closed';
        } else {
            $bRec->state = $bRec->exState;
            $bRec->exState = 'closed';
        }
        bank_Accounts::save($bRec, 'state,exState');
    }
}
