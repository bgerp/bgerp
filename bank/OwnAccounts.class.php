<?php



/**
 * Банкови сметки на фирмата
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_OwnAccounts extends core_Master {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf, bank_OwnAccRegIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, bank_Wrapper, acc_plg_Registry,
                     plg_Sorting, plg_Current, plg_LastUsedKeys, doc_FolderPlg, plg_Rejected, plg_State';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    var $lastUsedKeys = 'bankAccountId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, title, bankAccountId, currency=Валута, type, blAmount=Сума';
    
    
    /**
     * Кое поле отговаря на кой работи с дадена сметка
     */
    var $inChargeField = 'operators';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'bank, ceo';
    
    
    /**
     * Кой може да селектира?
     */
    var $canSelect = 'ceo,bank';
    
    
    /**
     * Кои мастър роли имат достъп до корицата, дори да нямат достъп до папката
     */
    var $coverMasterRoles = 'ceo, bankMaster';
    
    
    /**
     * Кой може да пише
     */
    var $canReject = 'ceo, bankMaster';
    
    
    /**
     * Кой може да пише
     */
    var $canRestore = 'ceo, bankMaster';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'bankMaster, ceo';
    
    
    /**
     * Кой може да селектира всички записи
     */
    var $canSelectAll = 'ceo, bankMaster';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'bank,ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'bank,ceo';
    
    
    /**
     * Заглавие
     */
    var $title = 'Банкови сметки на фирмата';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Банкова сметка';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'title';
    
    
    /**
     * Всички записи на този мениджър автоматично стават пера в номенклатурата със системно име
     * $autoList
     */
    var $autoList = 'bankAcc';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'bank/tpl/SingleLayoutOwnAccount.shtml';
    
    
    /**
     * Икона за единичен изглед
     */
    var $singleIcon = 'img/16/own-bank.png';
    
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
    function description()
    {
        $this->FLD('bankAccountId', 'key(mvc=bank_Accounts,select=iban)', 'caption=Сметка,mandatory');
        $this->FLD('type', 'enum(current=Разплащателна,
                                 deposit=Депозитна,
                                 loan=Кредитна,
                                 personal=Персонална,
                                 capital=Набирателна)', 'caption=Тип,mandatory');
        $this->FLD('title', 'varchar(128)', 'caption=Наименование');
        $this->FLD('titulars', 'keylist(mvc=crm_Persons, select=name, makeLinks)', 'caption=Титуляри->Име,mandatory');
        $this->FLD('together',  'enum(together=Заедно,separate=Поотделно)', 'caption=Титуляри->Представляват');
        $this->FLD('operators', 'userList(roles=bank|ceo)', 'caption=Оператори,mandatory');
        $this->FLD('autoShare', 'enum(yes=Да,no=Не)', 'caption=Споделяне на сделките с другите отговорници->Избор,notNull,default=yes,maxRadio=2');
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    protected static function on_AfterRecToVerbal(&$mvc, &$row, &$rec, $fields = array())
    {
        $row->STATE_CLASS .= ($rec->state == 'rejected') ? " state-rejected" : " state-active";
        $row->bankAccountId = ht::createLink($row->bankAccountId, array('bank_Accounts', 'single', $rec->bankAccountId));
        
        if(isset($fields['-list'])){
            $bankItem = acc_Items::fetchItem($mvc->getClassId(), $rec->id);
            
            // Намираме всички записи от текущия баланс за това перо
            $balRec = acc_Balances::getLastBalance();
            $bQuery = acc_BalanceDetails::getQuery();
            acc_BalanceDetails::filterQuery($bQuery, $balRec->id, $mvc->balanceRefAccounts, NULL, $bankItem->id);
             
            // Събираме ги да намерим крайното салдо на перото
            $rec->blAmount = 0;
            while($bRec = $bQuery->fetch()){
            	$rec->blAmount += $bRec->blAmount;
            }
            
            // Обръщаме го във четим за хората вид
            $Double = cls::get('type_Double');
            $Double->params['decimals'] = 2;
            $row->blAmount = "<span style='float:right'>" . $Double->toVerbal($rec->blAmount) . "</span>";
            
            if($rec->blAmount < 0){
                $row->blAmount = "<span style='color:red'>{$row->blAmount}</span>";
            }
        }
        
        $currencyId = bank_Accounts::fetchField($rec->bankAccountId, 'currencyId');
        $row->currency = currency_Currencies::getCodeById($currencyId);
    }
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    protected static function on_AfterPrepareListFields($mvc, $data)
    {
        $data->listFields['blAmount'] .= ", " . acc_Periods::getBaseCurrencyCode();
    }
    
    
    /**
     * След рендиране на лист таблицата
     */
    public static function on_AfterRenderListTable($mvc, &$tpl, &$data)
    {
        if(!count($data->rows)) return;
        
        foreach ($data->recs as $rec){
            $total += $rec->blAmount;
        }
        
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $total = $Double->toVerbal($total);
        
        if($total < 0){
            $total = "<span style='color:red'>{$total}</span>";
        }
        
        $state = (Request::get('Rejected', 'int')) ? 'rejected' : 'closed';
        $colspan = count($data->listFields) - 1;
        $lastRow = new ET("<tr style='text-align:right' class='state-{$state}'><td colspan='{$colspan}'>[#caption#]: &nbsp;<b>[#total#]</b></td><td>&nbsp;</td></tr>");
        $lastRow->replace(tr("Общо"), 'caption');
        $lastRow->replace($total, 'total');
        
        $tpl->append($lastRow, 'ROW_AFTER');
    }
    
    
    /**
     * Обработка по формата
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $optionAccounts = $mvc->getPossibleBankAccounts();
        
        $titulars = $mvc->getTitulars();
        
        $data->form->setOptions('bankAccountId', $optionAccounts);
        $data->form->setSuggestions('titulars', $titulars);
        
        // Номера на сметката не може да се променя ако редактираме, за смяна на
        // сметката да се прави от bank_accounts
        if($data->form->rec->id) {
            $data->form->setReadOnly('bankAccountId');
        }
    }
    
    
    /**
     * Връща всички Всички лица, които могат да бъдат титуляри на сметка
     * тези включени в група "Управители"
     */
    function getTitulars()
    {
        $options = array();
        $groupId = crm_Groups::fetchField("#sysId = 'managers'", 'id');
        $personQuery = crm_Persons::getQuery();
        $personQuery->where("#groupList LIKE '%|{$groupId}|%'");
        
        while($personRec = $personQuery->fetch()) {
            $options[$personRec->id] = crm_Persons::getVerbal($personRec, 'name');
        }
        
        if(count($options) == 0) {
            return Redirect(array('crm_Persons', 'list'), NULL, 'Няма лица в група "Управители" за титуляри на "нашите сметки". Моля добавете !');
        }
        
        return $options;
    }
    
    
    /**
     * Подготовка на списъка от банкови сметки, между които можем да избираме
     * @return array $options - масив от потребители
     */
    function getPossibleBankAccounts()
    {
        $bankAccounts = cls::get('bank_Accounts');
        
        // Извличаме само онези сметки, които са на нашата фирма и не са
        // записани в bank_OwnAccounts
        $ourCompany        = crm_Companies::fetchOurCompany();
        $queryBankAccounts = $bankAccounts->getQuery();
        $queryBankAccounts->where("#contragentId = {$ourCompany->id}");
        $queryBankAccounts->where("#contragentCls = {$ourCompany->classId}");
        $options = array();
        
        while($rec = $queryBankAccounts->fetch()) {
            if (!static::fetchField("#bankAccountId = " . $rec->id , 'id')) {
                $options[$rec->id] = $bankAccounts->getVerbal($rec, 'iban');
            }
        }
        
        return $options;
    }
    
    
    /**
     * Проверка дали може да се добавя банкова сметка в ownAccounts(Ако броя
     * на собствените сметки отговаря на броя на сметките на Моята компания в
     * bank_Accounts то не можем да добавяме нова сметка от този мениджър
     * @return boolean TRUE/FALSE - можем ли да добавяме нова сметка
     */
    function canAddOwnAccount()
    {
        $ourCompany = crm_Companies::fetchOurCompany();
        
        $accountsQuery = bank_Accounts::getQuery();
        $accountsQuery->where("#contragentId = {$ourCompany->id}");
        $accountsQuery->where("#contragentCls = {$ourCompany->classId}");
        $accountsNumber = $accountsQuery->count();
        $ownAccountsQuery = $this->getQuery();
        $ownAccountsNumber = $ownAccountsQuery->count();
        
        if($ownAccountsNumber == $accountsNumber) {
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    /**
     * Изчличане на цялата информация за сметката която е активна
     * 
     * @return stdClass $acc - записа отговарящ на текущата ни сметка
     */
    public static function getOwnAccountInfo($id = NULL)
    {
        if($id) {
            $ownAcc = static::fetch($id);
        } else {
            $ownAcc = static::fetch(static::getCurrent());
        }
        
        $acc = bank_Accounts::fetch($ownAcc->bankAccountId);
        
        if(!$acc->bank) {
            $acc->bank = bglocal_Banks::getBankName($acc->iban);
        }
        
        if(!$acc->bic) {
            $acc->bic = bglocal_Banks::getBankBic($acc->iban);
        }
        
        return $acc;
    }
    
    
    /**
     * Изпълнява се след въвеждането на данните от формата
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;
        
        if($form->isSubmitted()) {
            if(!$rec->title) {
                $rec->title = bank_Accounts::fetchField($rec->bankAccountId, 'iban');
            }
        }
    }
    
    
    /**
     * Обработка на ролите
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$res, $action)
    {
        if($action == 'add') {
            if(!$mvc->canAddOwnAccount()) {
                $res = 'no_one';
            }
        }
    }
    
    /*******************************************************************************************
     * 
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see crm_ContragentAccRegIntf
     * 
     ******************************************************************************************/
    
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     * @param int $objectId
     */
    public static function getItemRec($objectId)
    {
        $result = NULL;
        
        if ($rec = static::fetch($objectId)) {
            $account = bank_Accounts::fetch($rec->bankAccountId);
            $cCode = currency_Currencies::getCodeById($account->currencyId);
            $result = (object)array(
                'num'      => $rec->id  . " b",
                'title'    => $cCode . " - " . $rec->title,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    public static function itemInUse($objectId)
    {
        // @todo!
    }
    
    
    /**
     * КРАЙ НА интерфейса @see acc_RegisterIntf
     */
    
    
    /**
     * Връща Валутата и iban-a на всивки наши сметки разделени с "-"
     */
    public static function getOwnAccounts($selectIban = TRUE)
    {
        $Iban = cls::get('iban_Type');
        $accounts = array();
        $query = static::getQuery();
        
        while($rec = $query->fetch()) {
            $account = bank_Accounts::fetch($rec->bankAccountId);
            $cCode = currency_Currencies::getCodeById($account->currencyId);
            if($selectIban === TRUE){
            	$verbal = $Iban->toVerbal($account->iban);
            } else {
            	$verbal = $rec->title;
            }
            
            $accounts[$rec->id] = "{$cCode} - {$verbal}";
        }
        
        return $accounts;
    }
    
    
    /**
     * Подготвя и осъществява търсене по банка, изпозлва се
     * в банковите документи
     * @param stdClass $data
     * @param array $fields - масив от полета в полета в които ще се
     * търси по bankId
     */
    public static function prepareBankFilter(&$data, $fields = array())
    {
        $data->listFilter->FNC('own', 'key(mvc=bank_OwnAccounts,select=bankAccountId,allowEmpty)', 'caption=Сметка,silent');
        $data->listFilter->showFields .= ',own';
        $data->listFilter->setDefault('own', static::getCurrent('id', FALSE));
        $data->listFilter->input();
        
        if($filter = $data->listFilter->rec) {
            if($filter->own) {
                foreach($fields as $fld){
                    $data->query->orWhere("#{$fld} = {$filter->own}");
                }
            }
        }
    }
}
