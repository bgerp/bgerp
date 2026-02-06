<?php 

/**
 * Документ за Смяна на валута
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
class bank_ExchangeDocument extends core_Master
{
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=bank_transaction_ExchangeDocument';
    
    
    /**
     * Дали сумата е във валута (различна от основната)
     *
     * @see acc_plg_DocumentSummary
     */
    public $amountIsInNotInBaseCurrency = true;
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Банкови обмени на валути';
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools2, bank_Wrapper, acc_plg_Contable,
         plg_Sorting, plg_Clone, doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary, plg_Search, bgerp_plg_Blank, doc_SharablePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior, title=Документ, reason, creditCurrency=Обменени->Валута, creditQuantity=Обменени->Сума, debitCurrency=Получени->Валута, debitQuantity=Получени->Сума, state, createdOn, createdBy';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Банкова обмяна на валута';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/money_exchange.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Sv';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'bank,ceo,bankAll';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'bank,ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'bank, ceo';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'acc, bank, ceo';
    
    
    /**
     * Кой може да сторнира
     */
    public $canRevert = 'bank, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'bank/tpl/SingleExchangeDocument.shtml';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '4.7|Финанси';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'reason, peroFrom, peroTo, id';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'valior,creditQuantity,debitQuantity';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'valior,createdOn';
    
    
    /**
     * Дефолтен брой копия при печат
     *
     * @var int
     */
    public $defaultCopiesOnPrint = 2;
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,mandatory');
        $this->FLD('reason', 'varchar(255)', 'caption=Основание,input,mandatory');
        $this->FLD('peroFrom', 'key(mvc=bank_OwnAccounts, select=bankAccountId)', 'input,caption=От->Банк. сметка,silent,removeAndRefreshForm=creditCurrency');
        $this->FLD('creditCurrency', 'key(mvc=currency_Currencies, select=code,maxRadio=1)', 'caption=От->Валута,input=none');
        $this->FLD('creditPrice', 'double(smartRound,decimals=2)', 'input=none');
        $this->FLD('creditQuantity', 'double(smartRound,decimals=2,maxAllowedDecimals=2)', 'caption=От->Сума');
        $this->FLD('peroTo', 'key(mvc=bank_OwnAccounts, select=bankAccountId)', 'input,caption=Към->Банк. сметка,silent,removeAndRefreshForm=debitCurrency');
        $this->FLD('debitCurrency', 'key(mvc=currency_Currencies, select=code,maxRadio=1)', 'caption=Към->Валута,input=none');
        $this->FLD('debitQuantity', 'double(smartRound,decimals=2,maxAllowedDecimals=2)', 'caption=Към->Сума');
        $this->FLD('debitPrice', 'double(smartRound,decimals=2)', 'input=none');
        $this->FLD('equals', 'double(smartRound,decimals=2)', 'input=none,caption=Общо,summary=amount');
        $this->FLD('rate', 'double(smartRound,decimals=5)', 'input=none');
        $this->FLD(
            'state',
            'enum(draft=Чернова, active=Контиран, rejected=Оттеглен,stopped=Спряно, pending=Заявка)',
            'caption=Статус, input=none'
        );
        $this->FLD('sharedUsers', 'userList(showClosedUsers=no)', 'caption=Споделяне->Потребители');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($requiredRoles == 'no_one') {
            
            return;
        }
        if (!deals_Helper::canSelectObjectInDocument($action, $rec, 'bank_OwnAccounts', 'peroFrom')) {
            $requiredRoles = 'no_one';
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме към формата за търсене търсене по Каса
        bank_OwnAccounts::prepareBankFilter($data, array('peroFrom', 'peroTo'));
    }
    
    
    /**
     * Добавяме помощник за избиране на сч. операция
     */
    public static function on_BeforeAction($mvc, &$tpl, $action)
    {
        if ($action != 'add') {
            
            return;
        }
        
        if ($folderId = Request::get('folderId', 'int')) {
            if ($folderId != bank_OwnAccounts::fetchField(bank_OwnAccounts::getCurrent(), 'folderId')) {
                redirect(array('bank_OwnAccounts', 'list'), false, '|Документът не може да се създаде в папката на неактивна сметка');
            }
        }
    }
    
    
    /**
     * Подготовка на формата за добавяне
     */
    public static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        $today = dt::verbal2mysql();
        $cBank = bank_OwnAccounts::getCurrent();
        $form->rec->folderId = bank_OwnAccounts::forceCoverAndFolder($cBank);
        $form->setDefault('peroFrom', $cBank);
        $form->setDefault('valior', $today);
        $form->setReadOnly('peroFrom');
        $form->setOptions('peroTo', array('' => '') + bank_OwnAccounts::getOwnAccounts());

        if(isset($rec->peroFrom)){
            $creditCurrencyCode = bank_OwnAccounts::getDefaultCurrency($rec->peroFrom, $rec->valior, true);
            $form->setDefault('creditCurrency', currency_Currencies::getIdByCode($creditCurrencyCode));
            $form->setField('creditCurrency', "input");
        }

        if(isset($rec->peroTo)){
            $debitCurrencyCode = bank_OwnAccounts::getDefaultCurrency($rec->peroTo, $rec->valior, true);
            $form->setDefault('debitCurrency', currency_Currencies::getIdByCode($debitCurrencyCode));
            $form->setField('debitCurrency', "input");
        } else {
            $form->setField('debitQuantity', 'input=none');
        }
    }
    
    
    /**
     * Проверка след изпращането на формата
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;
            
            if (!$rec->creditQuantity || !$rec->debitQuantity) {
                $form->setError('creditQuantity, debitQuantity', 'Трябва да са въведени и двете суми !!!');
                
                return;
            }

            $valiorVerbal = dt::mysql2verbal($rec->valior, 'd.m.Y');
            $defaultCreditCurrencyCode = bank_OwnAccounts::getDefaultCurrency($rec->peroFrom, $rec->valior, true);
            $defaultDebitCurrencyCode = bank_OwnAccounts::getDefaultCurrency($rec->peroTo, $rec->valior, true);

            $creditCurrencyCode = currency_Currencies::getCodeById($rec->creditCurrency);
            $debitCurrencyCode = currency_Currencies::getCodeById($rec->debitCurrency);

            if($defaultCreditCurrencyCode != $creditCurrencyCode){
                $form->setWarning('peroFrom,creditCurrency', "|Избраната валута е различна от поддържаната от кредитната сметка|* <b>{$defaultCreditCurrencyCode}</b> |към вальор|* <b>{$valiorVerbal}</b>");
            }

            if($defaultDebitCurrencyCode != $debitCurrencyCode){
                $form->setWarning('peroTo,debitCurrency', "|Избраната валута е различна от поддържаната от дебитната сметка|* <b>{$defaultCreditCurrencyCode}</b> |към вальор|* <b>{$valiorVerbal}</b>");
            }

            if ($rec->creditCurrency == $rec->debitCurrency) {
                $form->setError('creditCurrency,peroFrom, debitCurrency,peroTo', "Валутите са едни и същи, няма смяна на валута към вальор|* <b>{$valiorVerbal}</b>");
                return;
            }
            
            // Изчисляваме курса на превалутирането спрямо входните данни
            $cRate = currency_CurrencyRates::getRate($rec->valior, $creditCurrencyCode, acc_Periods::getBaseCurrencyCode($rec->valior));
            currency_CurrencyRates::checkRateAndRedirect($cRate);
            $rec->creditPrice = $cRate;
            $rec->debitPrice = ($rec->creditQuantity * $rec->creditPrice) / $rec->debitQuantity;
            $rec->rate = round($rec->creditPrice / $rec->debitPrice, 4);
            
            if ($msg = currency_CurrencyRates::checkAmounts($rec->creditQuantity, $rec->debitQuantity, $rec->valior, $creditCurrencyCode,  $debitCurrencyCode)) {
                $form->setError('debitQuantity', $msg);
            }
            
            // Каква е равностойноста на обменената сума в основната валута за периода
            if ($debitCurrencyCode == acc_Periods::getBaseCurrencyCode($rec->valior)) {
                $rec->equals = $rec->creditQuantity * $rec->rate;
            } else {
                $rec->equals = currency_CurrencyRates::convertAmount($rec->debitQuantity, $rec->valior, $debitCurrencyCode);
            }

            $bankRec = bank_OwnAccounts::fetch($rec->peroTo);
            if ($bankRec->autoShare == 'yes') {
                $rec->sharedUsers = keylist::merge($rec->sharedUsers, keylist::removeKey($bankRec->operators, core_Users::getCurrent()));
            }
        }
    }
    
    
    /**
     * Обработки по вербалното представяне на данните
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->title = $mvc->getLink($rec->id, 0);

        $row->creditCurrency = currency_Currencies::getCodeById($rec->creditCurrency);
        $row->debitCurrency = currency_Currencies::getCodeById($rec->debitCurrency);

        if (isset($fields['-single'])) {
            $rate = ($rec->creditPrice) ? round($rec->debitPrice / $rec->creditPrice, 5) : 0;
            $row->rate = $mvc->getFieldType('rate')->toVerbal($rate);
            $row->rateUnit = "<span class='cCode'>{$row->creditCurrency}</span> / <span class='cCode'>{$row->debitCurrency}</span>";
            $row->type = tr('банкова сметка');
            $row->peroTo = bank_OwnAccounts::getHyperLink($rec->peroTo);
            $row->peroFrom = bank_OwnAccounts::getHyperLink($rec->peroFrom);
            $row->documentTitle = tr($mvc->singleTitle);
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
        return core_Cache::getOrCalc('BankExchDocCanAddToFolder', $folderId, function ($folderId){
                                        if ($folderId == bank_ExchangeDocument::getDefaultFolder(null, false) || 
                                            doc_Folders::fetchCoverClassName($folderId) == 'bank_OwnAccounts') {
                
                                            return true;
                                        }
            
                                        return false;
        });
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     *
     * @return bool
     */
    public static function canAddToThread($threadId)
    {
        $threadRec = doc_Threads::fetch($threadId);
        
        return self::canAddToFolder($threadRec->folderId);
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

        $cCurrencyCode = currency_Currencies::getCodeById($rec->creditCurrency);
        $dCurrencyCode = currency_Currencies::getCodeById($rec->debitCurrency);
        $row->subTitle = $rec->reason. ": {$cCurrencyCode} » {$dCurrencyCode}";

        return $row;
    }


    /**
     * Форсира създаване на банкова обмяна на валута
     *
     * @param int $bankFromId        - от коя б-сметка
     * @param int $bankToId          - към коя б-сметка
     * @param string|null $valior    - вальор
     * @param int $creditCurrencyId  - от коя валута
     * @param double $creditQuantity - к-во на валутата в кредита
     * @param int $debitCurrencyId   - в коя валута
     * @param double $debitQuantity  - к-во на валутата в дебита
     * @param string|null $reason    - основание
     * @return object $exchangeRec
     */
    public static function create($bankFromId, $bankToId, $valior, $creditCurrencyId, $creditQuantity, $debitCurrencyId, $debitQuantity, $reason = null)
    {
        $valior = $valior ?? dt::today();
        $fromBankRec = bank_OwnAccounts::fetch($bankFromId);

        // Подготовка на данните на записа
        $exchangeRec = (object)array('valior' => $valior,
                                     'peroFrom' => $bankFromId,
                                     'peroTo' => $bankToId,
                                     'reason' => $reason,
                                     'creditCurrency' => $creditCurrencyId,
                                     'creditQuantity' => $creditQuantity,
                                     'debitCurrency' => $debitCurrencyId,
                                     'debitQuantity' => $debitQuantity,
                                     'folderId' => $fromBankRec->folderId);

        $cRate = currency_CurrencyRates::getRate($exchangeRec->valior, currency_Currencies::getCodeById($exchangeRec->creditCurrency), acc_Periods::getBaseCurrencyCode($exchangeRec->valior));
        $exchangeRec->creditPrice = $cRate;
        $exchangeRec->debitPrice = ($exchangeRec->creditQuantity * $exchangeRec->creditPrice) / $exchangeRec->debitQuantity;
        $exchangeRec->rate = round($exchangeRec->creditPrice / $exchangeRec->debitPrice, 4);
        $exchangeRec->equals = $exchangeRec->creditQuantity * $exchangeRec->rate;

        $cu = core_Users::getCurrent();
        if ($fromBankRec->autoShare == 'yes') {
            $exchangeRec->sharedUsers = keylist::removeKey($fromBankRec->operators, $cu);
        }

        // Запис и споделяне на текущия потребител с него (ако не е системния)
        bank_ExchangeDocument::save($exchangeRec);
        if(!core_Users::isSystemUser()){
            doc_ThreadUsers::addShared($exchangeRec->threadId, $exchangeRec->containerId, $cu);
        }

        return $exchangeRec;
    }
}
