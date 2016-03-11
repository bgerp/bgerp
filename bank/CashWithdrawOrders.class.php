<?php 


/**
 * Документ за Нареждане разписки
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_CashWithdrawOrders extends core_Master
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Нареждане разписка";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, bank_Wrapper, acc_plg_DocumentSummary, doc_ActivatePlg,
         plg_Sorting, doc_DocumentPlg, plg_Printing,  plg_Search, doc_plg_MultiPrint, cond_plg_DefaultValues, doc_EmailCreatePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "tools=Пулт, number=Номер, reason, valior, amount, currencyId, proxyName=Лице, state, createdOn, createdBy";
    
    
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
    var $singleTitle = 'Нареждане разписка';
    
    
    /**
     * Икона на документа
     */
    var $singleIcon = 'img/16/nrrz.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Nr";
    
    
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
    var $singleLayoutFile = 'bank/tpl/SingleCashWithdrawOrder.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'valior, reason, proxyName, proxyEgn, proxyIdCard, id';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "4.92|Финанси";
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
        
        'ordererIban'      => 'lastDocUser|lastDoc',
        'execBank'          => 'lastDocUser|lastDoc',
        'execBankBranch' => 'lastDocUser|lastDoc',
        'execBankAdress' => 'lastDocUser|lastDoc',
        'proxyName'         => 'lastDocUser|lastDoc',
        'proxyEgn'          => 'lastDocUser|lastDoc',
        'proxyIdCard'     => 'lastDocUser|lastDoc',
    );
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('amount', 'double(decimals=2,max=2000000000,min=0)', 'caption=Сума,mandatory,summary=amount');
        $this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута');
        $this->FLD('reason', 'varchar(255)', 'caption=Основание,mandatory');
        $this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,width=6em,mandatory');
        $this->FLD('ordererIban', 'key(mvc=bank_OwnAccounts,select=bankAccountId)', 'caption=От->Сметка,mandatory');
        $this->FLD('execBank', 'varchar(255)', 'caption=От->Банка,mandatory');
        $this->FLD('execBankBranch', 'varchar(255)', 'caption=От->Клон');
        $this->FLD('execBankAdress', 'varchar(255)', 'caption=От->Адрес');
        $this->FLD('proxyName', 'varchar(255)', 'caption=Упълномощено лице->Име,mandatory');
        $this->FLD('proxyEgn', 'bglocal_EgnType', 'caption=Упълномощено лице->ЕГН,mandatory');
        $this->FLD('proxyIdCard', 'varchar(16)', 'caption=Упълномощено лице->Лк. No,mandatory');
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме към формата за търсене търсене по Каса
        bank_OwnAccounts::prepareBankFilter($data, array('ordererIban'));
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
            $rec = $doc->fetch();
            
            // Извличаме каквато информация можем от оригиналния документ
            $form->setDefault('currencyId', $rec->currencyId);
            $form->setDefault('amount', $rec->amount);
            $form->setDefault('reason', $rec->reason);
            $form->setDefault('valior', $rec->valior);
            $ownAcc = ($rec->ownAccount) ? $rec->ownAccount : $rec->creditBank;
            
            $account = bank_OwnAccounts::getOwnAccountInfo($ownAcc);
            $form->setDefault('execBank', $account->bank);
            $form->setReadOnly('ordererIban', $ownAcc);
            
            // Ако контрагента е лице, слагаме името му за получател
            $coverClass = doc_Folders::fetchCoverClassName($form->rec->folderId);
            
            if($coverClass == 'crm_Persons') {
                $form->setDefault('proxyName', $rec->contragentName);
                
                // EGN на контрагента 
                $proxyEgn = crm_Persons::fetchField($rec->contragentId, 'egn');
                $form->setDefault('proxyEgn', $proxyEgn);
                
                // Номер на Л. картата на лицето ако е записана в системата
                if($idCard = crm_ext_IdCards::fetchField("#personId = {$rec->contragentId}", 'idCardNumber')) {
                    $form->setDefault('proxyIdCard', $idCard);
                }
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
            $spellNumber = cls::get('core_SpellNumber');
            $row->sayWords = $spellNumber->asCurrency($rec->amount, 'bg', FALSE);
            
            $myCompany = crm_Companies::fetchOwnCompany();
            $row->ordererName = $myCompany->company;
        }
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
        
        // Може да се добавя само към Разходен банков ордер
        if(!($origin->isInstanceOf('bank_SpendingDocuments') || $origin->isInstanceOf('bank_InternalMoneyTransfer'))) return FALSE;
        
        return TRUE;
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
        $tpl = new ET(tr("Моля запознайте се с нашето нареждане разписка") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
}