<?php 


/**
 * Документ за Платежно Нареждане
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_PaymentOrders extends core_Master
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf, email_DocumentIntf';
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Платежни нареждания";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, bank_Wrapper, acc_plg_DocumentSummary, plg_Search,
         plg_Sorting,doc_DocumentPlg, plg_Printing,doc_plg_MultiPrint, doc_ActivatePlg, doc_EmailCreatePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "tools=Пулт, number=Номер, reason, valior, amount, currencyId, beneficiaryName, beneficiaryIban, createdOn, createdBy";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'reason';
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Платежно нареждане';
    
    
    /**
     * Икона на документа
     */
    var $singleIcon = 'img/16/pln.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Bpо";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'bank, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'bank,ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'bank,ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'bank, ceo';
    
    
    /**
     * Кой може да създава
     */
    var $canAdd = 'bank, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'bank/tpl/SinglePaymentOrder.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'valior, reason, beneficiaryName, ordererIban, beneficiaryIban, id';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "4.9|Финанси";
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('amount', 'double(decimals=2,max=2000000000,min=0)', 'caption=Сума,mandatory,summary=amount');
        $this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута');
        $this->FLD('reason', 'varchar(255)', 'caption=Основание,mandatory');
        $this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,mandatory');
        $this->FLD('moreReason', 'text(rows=2)', 'caption=Допълнително');
        $this->FLD('paymentSystem', 'enum(bisera=БИСЕРА,rings=РИНГС)', 'caption=Пл. система,default=bisera');
        $this->FLD('orderer', 'varchar(255)', 'caption=Наредител->Име,mandatory');
        $this->FLD('ordererIban', 'iban_Type', 'caption=Наредител->Банк. сметка,mandatory');
        $this->FLD('execBank', 'varchar(255)', 'caption=Наредител->Банка');
        $this->FLD('execBankBic', 'varchar(12)', 'caption=Наредител->BIC');
        $this->FLD('execBranch', 'varchar(255)', 'caption=Наредител->Клон');
        $this->FLD('execBranchAddress', 'varchar(255)', 'caption=Наредител->Адрес');
        $this->FLD('beneficiaryName', 'varchar(255)', 'caption=Получател->Име,mandatory');
        $this->FLD('beneficiaryIban', 'iban_Type', 'caption=Получател->IBAN,mandatory');
        $this->FLD('originClassId', 'key(mvc=core_Classes,select=name)', 'input=none');
    }
    
    
    /**
     * Обработка на формата за редакция и добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $form = &$data->form;
        $originId = $form->rec->originId;
        
        if($originId) {
            $doc = doc_Containers::getDocument($originId);
            $docRec = $doc->fetch();
            $cClass = $doc->className;
            
            $form->setDefault('originClassId', $cClass::getClassId());
            $form->setDefault('currencyId', $docRec->currencyId);
            $form->setDefault('amount', $docRec->amount);
            $form->setDefault('reason', $docRec->reason);
            $form->setDefault('valior', $docRec->valior);
            $myCompany = crm_Companies::fetchOwnCompany();
            $contragentIbans = bank_Accounts::getContragentIbans($docRec->contragentId, $docRec->contragentClassId);
            
            if($doc->className == 'bank_IncomeDocuments') {
                
                // Ако оригиналния документ е приходен, наредителя е контрагента
                // а получателя е моята фирма
                $form->setDefault('beneficiaryName', $myCompany->company);
                $ownAcc = bank_OwnAccounts::getOwnAccountInfo($docRec->ownAccount);
                $form->setDefault('beneficiaryIban', $ownAcc->iban);
                $form->setDefault('orderer', $docRec->contragentName);
                $form->setSuggestions('ordererIban', $contragentIbans);
                
                if($docRec->contragentIban){
                    $form->setDefault('ordererIban', $docRec->contragentIban);
                }
            } elseif($doc->className == 'bank_SpendingDocuments') {
                
                // Ако оригиналния документ е приходен, наредителя е моята фирма
                // а получателя е контрагента
                $form->setDefault('orderer', $myCompany->company);
                $ownAcc = bank_OwnAccounts::getOwnAccountInfo($docRec->ownAccount);
                $form->setDefault('ordererIban', $ownAcc->iban);
                $form->setSuggestions('beneficiaryIban', $contragentIbans);
                
                if($docRec->contragentIban){
                    $form->setDefault('beneficiaryIban', $docRec->contragentIban);
                }
                $form->setDefault('beneficiaryName', $docRec->contragentName);
            }
        }
        
        // Поставяме стойности по подразбиране
        $today = dt::verbal2mysql();
        $form->setDefault('valior', $today);
        $form->setDefault('currencyId', acc_Periods::getBaseCurrencyId($today));
        
        // Използваме помощната функция за намиране името на контрагента
        $form->setReadOnly('beneficiaryName');
    }
    
    
    /**
     * След изпращане на формата попълваме банката и бика ако неса
     * попълнени
     */
    static function on_AfterInputEditForm($mvc, &$form)
    {
        if($form->isSubmitted()){
            if(!$form->rec->execBank){
                $form->rec->execBank = bglocal_Banks::getBankName($form->rec->ordererIban);
            }
            
            if(!$form->rec->execBankBic){
                $form->rec->execBankBic = bglocal_Banks::getBankBic($form->rec->ordererIban);
            }
        }
    }
    
    
    /**
     * Обработки по вербалното представяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->number = static::getHandle($rec->id);
        
        if($fields['-single']) {
            
            // Извличаме името на банката и BIC-а на получателя от IBAN-а му
            $row->contragentBank = bglocal_Banks::getBankName($rec->beneficiaryIban);
            $row->contragentBankBic = bglocal_Banks::getBankBic($rec->beneficiaryIban);
        }
    }
    
    
    /**
     * Функция която скрива бланката с логото на моята фирма
     * при принтиране ако документа е базиран на
     * "приходен банков документ"
     */
    function renderSingleLayout_(&$data)
    {
        $tpl = parent::renderSingleLayout_($data);
        
        if(Mode::is('printing')){
            
            if($data->row->originClassId == 'bank_IncomeDocuments') {
                
                // скриваме логото на моята фирма
                $tpl->replace('', 'blank');
            }
        }
        
        return $tpl;
    }
    
    
    /**
     * Вкарваме css файл за единичния изглед
     */
    static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        $tpl->push('bank/tpl/css/belejka.css', 'CSS');
    }
    
    /*
     * Реализация на интерфейса doc_DocumentIntf
     */
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = $rec->reason;
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $rec->reason;
        
        return $row;
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        return FALSE;
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
        // Ако няма ориджин в урл-то, документа не може да се добави към нишката
        $originId = Request::get('originId', 'int');
        
        if(empty($originId)) return FALSE;
        
        // Към кой документ се създава бланката
        $origin = doc_Containers::getDocument($originId);
        
        // Може да се поражда само от приходен или разходен банков документ
        return $origin->isInstanceOf('bank_IncomeDocuments') || $origin->isInstanceOf('bank_SpendingDocuments');
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    static function getHandle($id)
    {
        $rec = static::fetch($id);
        $self = cls::get(get_called_class());
        
        return $self->abbr . $rec->id;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        $data->toolbar->removeBtn('btnAdd');
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашето платежно нареждане") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
    
    
    /**
     * След рендиране на единичния изглед
     */
    static function on_AfterRenderSingleLayout($mvc, $tpl, $data)
    {
        if(Mode::is('printing') || Mode::is('text', 'xhtml')){
            $tpl->removeBlock('header');
        }
    }
}