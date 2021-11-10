<?php 

/**
 * Документ за Вътрешно Паричен Трансфер
 *
 *
 * @category  bgerp
 * @package   bank
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bank_InternalMoneyTransfer extends core_Master
{
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=bank_transaction_InternalMoneyTransfer';
    
    
    /**
     * Дали сумата е във валута (различна от основната)
     *
     * @see acc_plg_DocumentSummary
     */
    public $amountIsInNotInBaseCurrency = true;
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Вътрешни банкови трансфери';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = true;
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools2, bank_Wrapper,acc_plg_Contable, acc_plg_DocumentSummary,
     	plg_Clone,doc_DocumentPlg, plg_Printing, deals_plg_SaveValiorOnActivation, plg_Search, bgerp_plg_Blank, doc_SharablePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior, title=Документ, reason, folderId, currencyId, amount, state, createdOn, createdBy, modifiedOn, modifiedBy';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Вътрешен банков трансфер';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/money.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Bvt';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'ceo, acc, cash, bank';
    
    
    /**
     * Кой може да го прави заявка?
     */
    public $canPending = 'ceo, acc, cash, bank';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'bank, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'bank,ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'bank,ceo';
    
    
    /**
     * Кой може да сторнира
     */
    public $canRevert = 'bank, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'bank/tpl/SingleInternalMoneyTransfer.shtml';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '4.5|Финанси';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'valior, reason, creditBank, debitBank, operationSysId';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'valior,createdOn,modifiedOn';
    
    
    /**
     * Позволени операции
     */
    public $allowedOperations = array('bank2case' => array('debit' => '501', 'credit' => '503'), 'bank2bank' => array('debit' => '503', 'credit' => '503'));
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('operationSysId', 'enum(bank2bank=Вътрешен банков трансфер,bank2case=Захранване на каса)', 'caption=Операция,mandatory,silent');
        $this->FLD('amount', 'double(decimals=2,maxAllowedDecimals=2)', 'caption=Сума,mandatory,summary=amount');
        $this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута');
        $this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,mandatory');
        $this->FLD('reason', 'richtext(rows=3)', 'caption=Основание,input,mandatory');
        $this->FLD('creditAccId', 'acc_type_Account()', 'caption=Кредит,input=none');
        $this->FLD('creditBank', 'key(mvc=bank_OwnAccounts, select=bankAccountId)', 'caption=От->Банк. сметка');
        $this->FLD('debitAccId', 'acc_type_Account()', 'caption=Дебит,input=none');
        $this->FLD('debitCase', 'key(mvc=cash_Cases, select=name)', 'caption=Към->Каса,input=none');
        $this->FLD('debitBank', 'key(mvc=bank_OwnAccounts, select=bankAccountId)', 'caption=Към->Банк. сметка,input=none');
        $this->FLD(
            'state',
            'enum(draft=Чернова, active=Активиран, rejected=Оттеглен, closed=Контиран,stopped=Спряно, pending=Заявка)',
            'caption=Статус, input=none'
        );
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($requiredRoles == 'no_one') {
            
            return;
        }
        
        if (isset($rec)) {
            if ($rec->operationSysId == 'bank2bank') {
                if (!deals_Helper::canSelectObjectInDocument($action, $rec, 'bank_OwnAccounts', 'debitBank')) {
                    $requiredRoles = 'no_one';
                }
            } elseif ($rec->operationSysId == 'bank2case') {
                if (!deals_Helper::canSelectObjectInDocument($action, $rec, 'cash_Cases', 'debitCase')) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме към формата за търсене търсене по Каса
        bank_OwnAccounts::prepareBankFilter($data, array('creditBank', 'debitBank'));
    }
    
    
    /**
     * Добавяме помощник за избиране на сч. операция
     */
    public static function on_BeforeAction($mvc, &$tpl, $action)
    {
        if ($action != 'add') {
            
            return;
        }
        
        if (!$mvc->haveRightFor($action)) {
            
            return;
        }
        
        // Има ли вече зададено основание?
        if (Request::get('operationSysId', 'varchar')) {
            
            // Има основание - не правим нищо
            return;
        }
        
        $form = static::prepareReasonForm();
        $form->input();
        $form = $form->renderHtml();
        $tpl = $mvc->renderWrapping($form);
        
        return false;
    }
    
    
    /**
     * Подготвяме формата от която ще избираме посоката на движението
     */
    public static function prepareReasonForm()
    {
        $form = cls::get('core_Form');
        $form->method = 'GET';
        $form->FNC('operationSysId', 'enum(bank2bank=Вътрешен банков трансфер,bank2case=Захранване на каса)', 'input,caption=Операция');
        $form->FNC('folderId', 'key(mvc=doc_Folders,select=title)', 'input=hidden,caption=Папка');
        $form->title = 'Нов вътрешен банков трансфер';
        $form->toolbar->addSbBtn('Напред', '', array('class' => 'fright'), 'ef_icon = img/16/move.png');
        $form->toolbar->addBtn('Отказ', toUrl(array('bank_InternalMoneyTransfer', 'list')), 'ef_icon = img/16/close-red.png');
        
        $folderId = bank_OwnAccounts::forceCoverAndFolder(bank_OwnAccounts::getCurrent());
        $folderRec = doc_Folders::fetch($folderId);
        if (!doc_Folders::haveRightToObject($folderRec)) {
            $folderId = static::getDefaultFolder(null, false);
        }
        $form->setDefault('folderId', $folderId);
        
        return $form;
    }
    
    
    /**
     * Подготовка на формата за добавяне
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $form = &$data->form;
        
        // Очакваме и намираме коя е извършената операция
        if (!$form->rec->id) {
            expect($operationSysId = Request::get('operationSysId'));
        } else {
            $operationSysId = $form->rec->operationSysId;
        }
        
        switch ($operationSysId) {
            case 'bank2bank':
                $form->setField('debitBank', 'input');
                $form->setOptions('debitBank', bank_OwnAccounts::getOwnAccounts());
                break;
            case 'bank2case':
                $form->setField('debitCase', 'input');
                break;
        }
        
        $form->setReadOnly('operationSysId');
        $today = dt::verbal2mysql();
        $form->setDefault('currencyId', acc_Periods::getBaseCurrencyId($today));
        $form->setReadOnly('creditBank', bank_OwnAccounts::getCurrent());
    }
    
    
    /**
     * Ако в документа няма код, който да рутира документа до папка/тред,
     * долния код, рутира документа до "Несортирани - [заглавие на класа]"
     */
    protected static function on_BeforeRoute($mvc, &$res, $rec)
    {
        if ($rec->operationSysId == 'bank2bank') {
            $rec->folderId = bank_OwnAccounts::forceCoverAndFolder($rec->debitBank);
        } elseif ($rec->operationSysId == 'bank2case') {
            $rec->folderId = cash_Cases::forceCoverAndFolder($rec->debitCase, 'folderId');
        }
    }
    
    
    /**
     * Проверка след изпращането на формата
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;
            
            $rec->debitAccId = $mvc->allowedOperations[$rec->operationSysId]['debit'];
            $rec->creditAccId = $mvc->allowedOperations[$rec->operationSysId]['credit'];
            
            // Проверяваме дали валутите на дебитната сметка съвпадат
            // с тези на кредитната
            $mvc->validateForm($form);
        }
    }
    
    
    /**
     * При Банка -> Каса
     * Валутата на касата към която прехвърляме приема стойноста на
     * валутата на сметката от която местим
     * При Банка -> Банка
     * Проверява дали банковата сметка към която прехвърляме да е
     * същата като тази на банката от която местим, ако не - сетва
     * грешка
     *
     * @param core_Form $form
     */
    public function validateForm($form)
    {
        $rec = &$form->rec;
        $creditInfo = bank_OwnAccounts::getOwnAccountInfo($rec->creditBank);
        
        if ($rec->operationSysId == 'bank2bank') {
            $bankRec = bank_OwnAccounts::fetch($rec->debitBank);
            if ($bankRec->autoShare == 'yes') {
                $rec->sharedUsers = keylist::removeKey($bankRec->operators, core_Users::getCurrent());
            }
            
            // Двете банкови сметки трябва да са различни
            if ($rec->creditBank == $rec->debitBank) {
                $form->setError('debitBank', 'Дестинацията е една и съща !!!');
                
                return;
            }
            
            $debitInfo = bank_OwnAccounts::getOwnAccountInfo($rec->debitBank);
            
            if ($creditInfo->currencyId != $debitInfo->currencyId) {
                $form->setError('debitBank, creditBank', 'Банковите сметки не са в една валута! Документът за обмяна е "Банкова обмяна на валута".');
                
                return;
            }
            
            if ($creditInfo->currencyId != $rec->currencyId) {
                $form->setError('debitBank, creditBank', 'Банковите сметки не са в посочената валута !!!');
                
                return;
            }
        } elseif ($rec->operationSysId == 'bank2case') {
            $caseRec = cash_Cases::fetch($rec->debitCase);
            if ($caseRec->autoShare == 'yes') {
                $rec->sharedUsers = keylist::merge($rec->sharedUsers, $caseRec->cashiers);
                $rec->sharedUsers = keylist::removeKey($rec->sharedUsers, core_Users::getCurrent());
            }
            
            if ($creditInfo->currencyId != $rec->currencyId) {
                $form->setError('debitEnt1,creditEnt1', 'Банковата сметка не е в посочената валута !!!');
                
                return;
            }
        }
    }
    
    
    /**
     * Обработки по вербалното представяне на данните
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->title = $mvc->getLink($rec->id, 0);
        
        if ($fields['-single']) {
            $row->currency = currency_Currencies::getCodeById($rec->currencyId);
            
            // Изчисляваме равностойността на сумата в основната валута
            if ($rec->rate != '1' && isset($rec->rate)) {
                $double = cls::get('type_Double');
                $double->params['decimals'] = 2;
                $equals = currency_CurrencyRates::convertAmount($rec->amount, $rec->valior, $row->currency);
                $row->equals = $mvc->getFieldType('amount')->toVerbal($equals);
                $row->baseCurrency = acc_Periods::getBaseCurrencyCode($rec->valior);
            }
            
            $row->creditBank = bank_OwnAccounts::getHyperLink($rec->creditBank, true);
            
            if ($rec->debitCase) {
                $row->creditBank .= " » " . cash_Cases::getHyperLink($rec->debitCase, true);
            }
            
            if ($rec->debitBank) {
                $row->creditBank .= " » " . bank_OwnAccounts::getHyperLink($rec->debitBank, true);
            }
        }
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        // Може да създаваме документ-а само в дефолт папката му
        if ($folderId == static::getDefaultFolder(null, false) || doc_Folders::fetchCoverClassName($folderId) == 'bank_OwnAccounts') {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow_($id)
    {
        $rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = $this->singleTitle . " №{$id}";
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $rec->reason;
        
        return $row;
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
        // Споделяме текущия потребител със нишката на заданието
        $cu = core_Users::getCurrent();
        doc_ThreadUsers::addShared($rec->threadId, $rec->containerId, $cu);
    }
}
