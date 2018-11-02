<?php


/**
 * Банкови сметки на фирмата
 *
 *
 * @category  bgerp
 * @package   bank
 *
 * @author    Milen Georgiev <milen@download.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
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
                     plg_Sorting, bgerp_plg_FLB, plg_Current, plg_LastUsedKeys, doc_FolderPlg, plg_Rejected, plg_State, plg_Modified';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'bankAccountId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title, bankAccountId, currency=Валута, type, blAmount=Сума';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, bank';
    
    
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
     * Кой може да пише
     */
    public $canReject = 'ceo, admin';
    
    
    /**
     * Кой може да пише
     */
    public $canRestore = 'ceo, admin';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'ceo, admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'bank,ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, bank';
    
    
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
    public $canReports = 'ceo,bank,acc';
    
    
    /**
     * По кои сметки ще се правят справки
     */
    public $balanceRefAccounts = '503';
    
    
    /**
     * По кой итнерфейс ще се групират сметките
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
        $this->FLD('comment', 'richtext(bucket=Notes,rows=6)', 'caption=Бележки');
        $this->FLD('operators', 'userList(roles=bank|ceo)', 'caption=Контиране на документи->Потребители,mandatory');
        $this->FLD('autoShare', 'enum(yes=Да,no=Не)', 'caption=Споделяне на сделките с другите отговорници->Избор,notNull,default=yes,maxRadio=2');
        
        $this->setDbUnique('title');
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    protected static function on_AfterRecToVerbal(&$mvc, &$row, &$rec, $fields = array())
    {
        $row->STATE_CLASS .= ($rec->state == 'rejected') ? ' state-rejected' : ' state-active';
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
                
                // Обръщаме го във четим за хората вид
                $Double = cls::get('type_Double');
                $Double->params['decimals'] = 2;
                $row->blAmount = "<span style='float:right'>" . $Double->toVerbal($rec->blAmount) . '</span>';
                
                if ($rec->blAmount < 0) {
                    $row->blAmount = "<span style='color:red'>{$row->blAmount}</span>";
                }
            }
        }
        
        if ($rec->bankAccountId) {
            $currencyId = bank_Accounts::fetchField($rec->bankAccountId, 'currencyId');
            $row->currency = currency_Currencies::getCodeById($currencyId);
            $ownAccounts = bank_OwnAccounts::getOwnAccountInfo($rec->id);
            
            $row->bank = bank_Accounts::getVerbal($ownAccounts, 'bank');
            $row->bic = bank_Accounts::getVerbal($ownAccounts, 'bic');
        }
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
        if (!count($data->rows)) {
            
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
        $colspan = count($data->listFields) - 1;
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
        $form->FNC('fromOurCompany', 'int', 'input=hidden');
        if (Request::get('fromOurCompany', 'int')) {
            $form->rec->fromOurCompany = true;
        }
        
        // Номера на сметката не може да се променя ако редактираме, за смяна на
        // сметката да се прави от bank_accounts
        if ($form->rec->id) {
            if (isset($form->rec->bankAccountId)) {
                $ibanRec = bank_Accounts::fetch($form->rec->bankAccountId);
                $form->setDefault('iban', $ibanRec->iban);
                $form->setDefault('bank', $ibanRec->bank);
                $form->setDefault('bic', $ibanRec->bic);
                $form->setDefault('currencyId', $ibanRec->currencyId);
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
     * Изчличане на цялата информация за сметката която е активна
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
                }
            }
        }
        
        if ($form->isSubmitted()) {
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
                        $form->setError('iban', 'Подадения IBAN принадлежи на чужда сметка');
                        
                        return;
                    }
                }
            }
            
            if (isset($rec->iban)) {
                $rec->bankAccountId = $mvc->addNewAccount($rec->iban, $rec->currencyId, $rec->bank, $rec->bic);
            }
            
            if (!$rec->title) {
                $rec->title = bank_Accounts::fetchField($rec->bankAccountId, 'iban');
                if ($accountRec) {
                    $rec->title .= ', ' . currency_Currencies::getCodeById($accountRec->currencyId);
                }
            }
        }
    }
    
    
    /**
     * Добавя нова наша сметка
     *
     * @param string $iban
     * @param int    $currencyId
     * @param string $bank
     * @param string $bic
     *
     * @return int $accId
     */
    private function addNewAccount($iban, $currencyId, $bank, $bic)
    {
        $IbanType = core_Type::getByName('iban_Type(64)');
        expect(currency_Currencies::fetch($currencyId));
        $iban = trim($iban);
        
        $accRec = new stdClass();
        foreach (array('iban', 'bic', 'bank', 'currencyId') as $fld) {
            $accRec->{$fld} = ${$fld};
        }
        
        if ($exRecId = bank_Accounts::fetchField(array("#iban = '[#1#]'", $iban), 'id')) {
            $accRec->id = $exRecId;
        }
        
        $ourCompany = crm_Companies::fetchOurCompany();
        $accRec->contragentId = $ourCompany->id;
        $accRec->contragentCls = $ourCompany->classId;
        
        $accId = bank_Accounts::save($accRec);
        
        return $accId;
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
     * @param boolean $selectIban
     * @param string|null $currencyCode
     * @return array $accounts
     */
    public static function getOwnAccounts($selectIban = true, $currencyCode = null)
    {
        $Varchar = cls::get('type_Varchar');
        $accounts = array();
        $query = static::getQuery();
        $query->where("#state != 'rejected' AND #state != 'closed'");
        $cu = core_Users::getCurrent();
        
        while ($rec = $query->fetch()) {
            if (!bgerp_plg_FLB::canUse(__CLASS__, $rec, $cu, 'select')) {
                continue;
            }
            
            if (isset($rec->bankAccountId)) {
                $account = bank_Accounts::fetch($rec->bankAccountId);
                $cCode = currency_Currencies::getCodeById($account->currencyId);
                if(isset($currencyCode) && strtoupper($currencyCode) != $cCode) continue;
               
                $verbal = ($selectIban === true) ? $Varchar->toVerbal($account->iban) : $rec->title;
                
                $accounts[$rec->id] = "{$cCode} - {$verbal}";
            }
        }
      
        return $accounts;
    }
    
    
    /**
     * Подготвя и осъществява търсене по банка, изпозлва се
     * в банковите документи
     *
     * @param stdClass $data
     * @param array    $fields - масив от полета в полета в които ще се
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
}
