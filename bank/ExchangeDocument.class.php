<?php 


/**
 * Документ за Смяна на валута
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
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
    public $amountIsInNotInBaseCurrency = TRUE;
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Банкови обмени на валути";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools2, bank_Wrapper, acc_plg_Contable,
         plg_Sorting, doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary, plg_Search, doc_plg_MultiPrint, bgerp_plg_Blank, doc_SharablePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "valior, title=Документ, reason, creditCurrency=Обменени->Валута, creditQuantity=Обменени->Сума, debitCurrency=Получени->Валута, debitQuantity=Получени->Сума, state, createdOn, createdBy";
    
    
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
    public $abbr = "Sv";
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'bank, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'bank,ceo';
    
    
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
    public $newBtnGroup = "4.7|Финанси";
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'reason, peroFrom, peroTo, id';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,mandatory');
        $this->FLD('reason', 'varchar(255)', 'caption=Основание,input,mandatory');
        $this->FLD('peroFrom', 'key(mvc=bank_OwnAccounts, select=bankAccountId)', 'input,caption=От->Банк. сметка');
        $this->FLD('creditPrice', 'double(smartRound,decimals=2)', 'input=none');
        $this->FLD('creditQuantity', 'double(smartRound,decimals=2)', 'caption=От->Сума');
        $this->FLD('peroTo', 'key(mvc=bank_OwnAccounts, select=bankAccountId)', 'input,caption=Към->Банк. сметка');
        $this->FLD('debitQuantity', 'double(smartRound,decimals=2)', 'caption=Към->Сума');
        $this->FLD('debitPrice', 'double(smartRound,decimals=2)', 'input=none');
        $this->FLD('equals', 'double(smartRound,decimals=2)', 'input=none,caption=Общо,summary=amount');
        $this->FLD('rate', 'double(smartRound,decimals=5)', 'input=none');
        $this->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Оттеглен,stopped=Спряно, pending=Заявка)', 'caption=Статус, input=none'
        );
        $this->FLD('sharedUsers', 'userList', 'input=none,caption=Споделяне->Потребители');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($requiredRoles == 'no_one') return;
    	if(!deals_Helper::canSelectObjectInDocument($action, $rec, 'bank_OwnAccounts', 'peroFrom')){
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
        
        if($folderId = Request::get('folderId', 'int')){
            if($folderId != bank_OwnAccounts::fetchField(bank_OwnAccounts::getCurrent(), 'folderId')){
                redirect(array('bank_OwnAccounts', 'list'), FALSE, "|Документът не може да се създаде в папката на неактивна сметка");
            }
        }
    }
    
    
    /**
     * Подготовка на формата за добавяне
     */
    public static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $form = &$data->form;
        $today = dt::verbal2mysql();
        $cBank = bank_OwnAccounts::getCurrent();
        $form->rec->folderId = bank_OwnAccounts::forceCoverAndFolder($cBank);
        $form->setDefault('peroFrom', $cBank);
        $form->setDefault('valior', $today);
        $form->setReadOnly('peroFrom');
        $form->setOptions('peroTo', bank_OwnAccounts::getOwnAccounts());
    }
    
    
    /**
     * Проверка след изпращането на формата
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
        if ($form->isSubmitted()){
            
            $rec = &$form->rec;
            
            if(!$rec->creditQuantity || !$rec->debitQuantity) {
                $form->setError("creditQuantity, debitQuantity", "Трябва да са въведени и двете суми !!!");
                
                return;
            }
            
            $creditAccInfo = bank_OwnAccounts::getOwnAccountInfo($rec->peroFrom);
            $debitAccInfo = bank_OwnAccounts::getOwnAccountInfo($rec->peroTo);
            
            if($creditAccInfo->currencyId == $debitAccInfo->currencyId) {
                $form->setWarning('peroFrom, peroTo', 'Валутите са едни и същи, няма смяна на валута !!!');
            }
            
            // Изчисляваме курса на превалутирането спрямо входните данни
            $cCode = currency_Currencies::getCodeById($creditAccInfo->currencyId);
            $dCode = currency_Currencies::getCodeById($debitAccInfo->currencyId);
            $cRate = currency_CurrencyRates::getRate($rec->valior, $cCode, acc_Periods::getBaseCurrencyCode($rec->valior));
            currency_CurrencyRates::checkRateAndRedirect($cRate);
            $rec->creditPrice = $cRate;
            $rec->debitPrice = ($rec->creditQuantity * $rec->creditPrice) / $rec->debitQuantity;
            $rec->rate = round($rec->creditPrice / $rec->debitPrice, 4);
            
            if($msg = currency_CurrencyRates::checkAmounts($rec->creditQuantity, $rec->debitQuantity, $rec->valior, $cCode, $dCode)){
            	$form->setError('debitQuantity', $msg);
            }
            
            // Каква е равностойноста на обменената сума в основната валута за периода
            if($dCode == acc_Periods::getBaseCurrencyCode($rec->valior)){
                $rec->equals = $rec->creditQuantity * $rec->rate;
            } else {
                $rec->equals = currency_CurrencyRates::convertAmount($rec->debitQuantity, $rec->valior, $dCode, NULL);
            }
            
            $bankRec = bank_OwnAccounts::fetch($rec->peroTo);
            if($bankRec->autoShare == 'yes'){
            	$rec->sharedUsers = keylist::removeKey($bankRec->operators, core_Users::getCurrent());
            }
        }
    }
    
    
    /**
     * Обработки по вербалното представяне на данните
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->title = $mvc->getLink($rec->id, 0);
        
        $creditAccInfo = bank_OwnAccounts::getOwnAccountInfo($rec->peroFrom);
        $debitAccInfo = bank_OwnAccounts::getOwnAccountInfo($rec->peroTo);
        $row->creditCurrency = currency_Currencies::getCodeById($creditAccInfo->currencyId);
        $row->debitCurrency = currency_Currencies::getCodeById($debitAccInfo->currencyId);
        
        if($fields['-single']) {
            
            $row->peroTo = bank_OwnAccounts::getHyperLink($rec->peroTo, TRUE);
            $row->peroFrom = bank_OwnAccounts::getHyperLink($rec->peroFrom, TRUE);
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
        return core_Cache::getOrCalc('BankExchDocCanAddToFolder', $folderId, function($folderId)
        {
            if($folderId == static::getDefaultFolder(NULL, FALSE) || doc_Folders::fetchCoverClassName($folderId) == 'bank_OwnAccounts') {
                
                return TRUE;
            }

            return FALSE;
        });
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
        $threadRec = doc_Threads::fetch($threadId);
        
        return self::canAddToFolder($threadRec->folderId);
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow($id)
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
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
        $self = cls::get(__CLASS__);
        
        return $self->singleTitle . " №$rec->id";
    }
}