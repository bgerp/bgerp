<?php 


/**
 * Документ за Вносни бележки
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_DepositSlips extends bank_DocumentBlank
{
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Вносни бележки";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools2, bank_Wrapper,
         plg_Sorting, plg_Clone, doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary, doc_ActivatePlg,
         plg_Search, doc_plg_MultiPrint, cond_plg_DefaultValues, doc_EmailCreatePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "number=Номер, reason, valior, amount, currencyId, beneficiaryName, beneficiaryIban, state, createdOn, createdBy";
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Вносна бележка';
    
    
    /**
     * Икона на документа
     */
    public $singleIcon = 'img/16/vnb.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Vb";
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'bank/tpl/SingleDepositSlip.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'valior, reason, beneficiaryName, beneficiaryIban, execBank';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'amount,valior';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
        
        'execBank'           => 'lastDocUser|lastDoc',
        'execBankBranch'  => 'lastDocUser|lastDoc',
        'execBankAdress'  => 'lastDocUser|lastDoc',
        'beneficiaryName' => 'lastDocUser|lastDoc',
        'beneficiaryIban' => 'lastDocUser|lastDoc',
        'beneficiaryBank' => 'lastDocUser|lastDoc',
        'depositor'       => 'lastDocUser|lastDoc',
    );
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('amount', 'double(decimals=2,max=2000000000,min=0)', 'caption=Сума,mandatory,summary=amount');
        $this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута');
        $this->FLD('reason', 'varchar(255)', 'caption=Основание,mandatory');
        $this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,mandatory');
        $this->FLD('execBank', 'varchar(255)', 'caption=До->Банка,mandatory');
        $this->FLD('execBankBranch', 'varchar(255)', 'caption=До->Клон');
        $this->FLD('execBankAdress', 'varchar(255)', 'caption=До->Адрес');
        $this->FLD('beneficiaryName', 'varchar(255)', 'caption=Получател->Име,mandatory');
        $this->FLD('beneficiaryIban', 'iban_Type', 'caption=Получател->IBAN,mandatory');
        $this->FLD('beneficiaryBank', 'varchar(255)', 'caption=Получател->Банка');
        $this->FLD('depositor', 'varchar(255)', 'caption=Вносител->Име,mandatory');

        $this->setDbIndex('valior');
    }
    
    
    /**
     * Обработка на формата за редакция и добавяне
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $form = &$data->form;
        $originId = $form->rec->originId;
        
        if($originId) {
            
            // Ако основанието е по банков документ намираме кой е той
            $doc = doc_Containers::getDocument($originId);
            $originRec = $doc->fetch();
            
            // Извличаме каквато информация можем от оригиналния документ
            $form->setDefault('currencyId', $originRec->currencyId);
            $form->setDefault('amount', $originRec->amount);
            $form->setDefault('reason', $originRec->reason);
            $form->setDefault('valior', $originRec->valior);
            
            $myCompany = crm_Companies::fetchOwnCompany();
            $form->setDefault('beneficiaryName', $myCompany->company);
            $ownAccount = bank_OwnAccounts::getOwnAccountInfo($originRec->ownAccount);
            $form->setDefault('beneficiaryIban', $ownAccount->iban);
            $form->setDefault('beneficiaryBank', $ownAccount->bank);
            
            // Ако контрагента е лице, слагаме името му за получател
            if($originRec->contragentClassId != crm_Companies::getClassId()){
                $form->setDefault('depositor', $originRec->contragentName);
            }
            
            if($originRec->contragentId && $originRec->contragentId){
                $options = bank_Accounts::getContragentIbans($originRec->contragentId, $originRec->contragentClassId);
                $form->setSuggestions('beneficiaryIban', $options);
            }
        }
        
        static::getContragentInfo($form);
    }
    
    
    /**
     * Попълва формата с информацията за контрагента извлечена от папката
     */
    protected static function getContragentInfo(core_Form $form)
    {
        if(isset($form->rec->beneficiaryName)) return;
        $folderId = $form->rec->folderId;
        
        // Информацията за контрагента на папката
        expect($contragentData = doc_Folders::getContragentData($folderId), "Проблем с данните за контрагент по подразбиране");
        
        if($contragentData) {
            if($contragentData->company) {
                
                $form->setReadOnly('beneficiaryName', $contragentData->company);
            } elseif ($contragentData->person) {
                
                // Ако папката е на лице, то вносителя по дефолт е лицето
                $form->setReadOnly('beneficiaryName', $contragentData->person);
            }
        }
    }
    
    
    /**
     * Обработки по вербалното представяне на данните
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->number = static::getHandle($rec->id);
        
        if($fields['-single']) {
            $SpellNumber = cls::get('core_SpellNumber');
            $row->sayWords = $SpellNumber->asCurrency($rec->amount, 'bg', TRUE);
            
            $row->sayWords = str_replace('0.0', '', $row->sayWords);
            $row->sayWords = str_replace('0.', '', $row->sayWords);
        }
    }
    
    
    /**
     * Обработка след изпращане на формата
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if($form->isSubmitted()){
            if(!$form->rec->beneficiaryBank){
                $form->rec->beneficiaryBank = bglocal_Banks::getBankName($form->rec->beneficiaryIban);
            }
        }
    }
    
    
    /**
     * Връща тялото на имейла генериран от документа
     * 
     * @see email_DocumentIntf
     * @param int $id - ид на документа
     * @param boolean $forward
     * @return string - тялото на имейла
     */
    public function getDefaultEmailBody($id, $forward = FALSE)
    {
        $handle = $this->getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашата вносна бележка") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
}