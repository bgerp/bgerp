<?php


/**
 * Документ за Платежно Нареждане
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
class bank_PaymentOrders extends bank_DocumentBlank
{
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Платежни нареждания';
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools2, bank_Wrapper, acc_plg_DocumentSummary, plg_Search, plg_Sorting,doc_DocumentPlg, plg_Printing, doc_ActivatePlg, doc_EmailCreatePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'number=Номер, reason, valior, amount, currencyId, beneficiaryName, beneficiaryIban, createdOn, createdBy';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Платежно нареждане';
    
    
    /**
     * Икона на документа
     */
    public $singleIcon = 'img/16/pln.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Bpo';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'bank/tpl/SinglePaymentOrder.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'valior, reason, beneficiaryName, ordererIban, beneficiaryIban';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'valior,createdOn,modifiedOn';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('documentType', 'enum(transfer=Кредитен превод,budget=Плащане от/към бюджета)', 'caption=Вид,removeAndRefreshForm = paymentType|documentNumber|periodStart|periodEnd|liablePerson|vatId|EGN|LNC,silent,notNull,value=transfer');
        $this->FLD('amount', 'double(decimals=2,min=0)', 'caption=Сума,mandatory,summary=amount');
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
        
        $this->FLD('paymentType', 'varchar(6)', 'caption=Допълнителни данни->Вид плащане');
        $this->FLD('documentNumber', 'varchar(40)', 'caption=Допълнителни данни->Номер на документа, по който се плаща');
        $this->FLD('periodStart', 'date(format=d.m.Y)', array('caption' => 'Период, за който се плаща->От дата'));
        $this->FLD('periodEnd', 'date(format=d.m.Y)', array('caption' => 'Период, за който се плаща->До дата'));
        $this->FLD('liablePerson', 'varchar(255)', 'caption=Допълнителни данни->Задължено лице,remember');
        
        $this->FLD('vatId', 'drdata_VatType', 'caption=Допълнителни данни->ЕИК');
        $this->FLD('EGN', 'varchar(10)', 'caption=Допълнителни данни->ЕГН');
        $this->FLD('LNC', 'varchar(10)', 'caption=Допълнителни данни->ЛНЧ');
        
        $this->setDbIndex('valior');
    }
    
    
    /**
     * Обработка на формата за редакция и добавяне
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $form = &$data->form;
        $originId = $form->rec->originId;
        
        if ($originId) {
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
            
            if ($doc->className == 'bank_IncomeDocuments') {
                
                // Ако оригиналния документ е приходен, наредителя е контрагента
                // а получателя е моята фирма
                $form->setDefault('beneficiaryName', $myCompany->company);
                $ownAcc = bank_OwnAccounts::getOwnAccountInfo($docRec->ownAccount);
                $form->setDefault('beneficiaryIban', $ownAcc->iban);
                $form->setDefault('orderer', $docRec->contragentName);
                $form->setSuggestions('ordererIban', $contragentIbans);
                
                if ($docRec->contragentIban) {
                    $form->setDefault('ordererIban', $docRec->contragentIban);
                }
            } elseif ($doc->className == 'bank_SpendingDocuments') {
                
                // Ако оригиналния документ е приходен, наредителя е моята фирма
                // а получателя е контрагента
                $form->setDefault('orderer', $myCompany->company);
                $ownAcc = bank_OwnAccounts::getOwnAccountInfo($docRec->ownAccount);
                $form->setDefault('ordererIban', $ownAcc->iban);
                $form->setSuggestions('beneficiaryIban', $contragentIbans);
                
                if ($docRec->contragentIban) {
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
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;
        
        if ($rec->documentType != 'budget') {
            $form->setField('paymentType', 'input=none');
            $form->setField('documentNumber', 'input=none');
            $form->setField('periodStart', 'input=none');
            $form->setField('periodEnd', 'input=none');
            $form->setField('liablePerson', 'input=none');
            $form->setField('vatId', 'input=none');
            $form->setField('EGN', 'input=none');
            $form->setField('LNC', 'input=none');
        }
        
        if ($form->isSubmitted()) {
            if (!$rec->execBank) {
                $rec->execBank = bglocal_Banks::getBankName($rec->ordererIban);
            }
            
            if (!$rec->execBankBic) {
                $rec->execBankBic = bglocal_Banks::getBankBic($rec->ordererIban);
            }
            
            if ((int) !empty($rec->LNC) + (int) !empty($rec->EGN) + (int) !empty($rec->vatId) > 1) {
                $form->setError('vatId,EGN,LNC', 'Трябва само едно от полетата за ЕИК, ЕГН и ЛНЧ да е попълнено');
            }
            
            if (!empty($rec->LNC)) {
                $lnc = cls::get('bglocal_BulgarianLNC');
                if ($lnc->isLnc($rec->LNC) !== true) {
                    $form->setError('LNC', 'Грешен ЛНЧ номер');
                }
            }
            
            if (!empty($rec->EGN)) {
                try {
                    $Egn = new bglocal_BulgarianEGN($rec->EGN);
                } catch (bglocal_exception_EGN $e) {
                    $form->setError('EGN', $e->getMessage());
                }
            }
        }
    }
    
    
    /**
     * Обработки по вербалното представяне на данните
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->number = static::getHandle($rec->id);
        
        
        if ($fields['-single']) {
            
            // Извличаме името на банката и BIC-а на получателя от IBAN-а му
            $row->contragentBank = bglocal_Banks::getBankName($rec->beneficiaryIban);
            $row->contragentBankBic = bglocal_Banks::getBankBic($rec->beneficiaryIban);
            $row->sayWords = self::spellAmount($rec);
        }
    }
    
    
    /**
     * Спелуване на сумата
     * 
     * @param stdClass $rec
     * @return mixed
     */
    private static function spellAmount($rec)
    {
        $lg = core_Lg::getCurrent();
        $SpellNumber = cls::get('core_SpellNumber');
        $sayWords = $SpellNumber->asCurrency($rec->amount, $lg, true);
        $sayWords = str_replace('0.0', '', $sayWords);
        $sayWords = str_replace('0.', '', $sayWords);
        
        return $sayWords;
    }
    
    
    /**
     * Функция която скрива бланката с логото на моята фирма
     * при принтиране ако документа е базиран на
     * "приходен банков документ"
     */
    public function renderSingleLayout_(&$data)
    {
        $tpl = parent::renderSingleLayout_($data);
        
        if (Mode::is('printing')) {
            if ($data->row->originClassId == 'bank_IncomeDocuments') {
                
                // скриваме логото на моята фирма
                $tpl->replace('', 'blank');
            }
        }
        
        return $tpl;
    }
    
    
    /**
     * Връща тялото на имейла генериран от документа
     *
     * @see email_DocumentIntf
     *
     * @param int  $id      - ид на документа
     * @param bool $forward
     *
     * @return string - тялото на имейла
     */
    public function getDefaultEmailBody($id, $forward = false)
    {
        $handle = $this->getHandle($id);
        $tpl = new ET(tr('Моля запознайте се с нашето платежно нареждане') . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
    
    
    /**
     * След рендиране на единичния изглед
     */
    protected static function on_AfterRenderSingleLayout($mvc, $tpl, $data)
    {
        if ($data->rec->documentType != 'budget') {
            $tpl->removeBlock('budgetBlock');
            $tpl->removeBlock('paymentType');
            $tpl->removeBlock('sayWords');
        }
    }
    
    
    /**
     * Генерира шаблон с попълнени данни за платежно нареждане към 'Моята фирма'
     * 
     * @param string|null $reason
     * @param array $fields
     * 
     *          ['currencyCode'] - код на валута, ако няма тази от нашия ибан
     *          ['amount']       - сума във валутата
     *          ['valior']       - вальор
     *          ['documentType'] - кредитно плащане или плащане от/към бюджета
     *          ['ownAccount']   - ид на наша сметка, информацията я извлича от нея
     * 
     * @return core_ET
     */
    public static function getFilledBlankTpl($reason = null, $fields = array())
    {
       $fields = (object)$fields;
       
       // Проверка на входните параметри
       $row = new stdClass();
       $row->reason = $reason;
       $row->currencyId = $fields->currencyCode;
       $documentType = isset($fields->documentType) ? $fields->documentType : 'transfer';
       expect(in_array($documentType, array('transfer', 'budget')));
       $row->documentType = cls::get(get_called_class())->getFieldType('documentType')->toVerbal($documentType);
       $row->valior = (isset($fields->valior)) ? dt::mysql2verbal($fields->valior, 'd.m.Y') : null;
       
       // Информация за моята сметка
       $ownCompany = crm_Companies::fetchOwnCompany();
       $row->beneficiaryName = $ownCompany->company;
       if(isset($fields->ownAccount)){
           $ownAccountInfo = bank_OwnAccounts::getOwnAccountInfo($fields->ownAccount); 
           $row->beneficiaryIban = $ownAccountInfo->iban;
           $row->contragentBankBic = (isset($fields->bic)) ? $fields->bic : $ownAccountInfo->bic;
           $row->contragentBank = (isset($fields->bank)) ? $fields->bank : $ownAccountInfo->bank;
           $row->currencyId = isset($fields->currencyId) ? $fields->currencyId : currency_Currencies::getCodeById($ownAccountInfo->currencyId);
       }
       
       // Сума 
       if(isset($fields->amount)){
           expect($amount = core_Type::getByName('double')->fromVerbal($fields->amount));
           $row->amount = core_Type::getByName('double(decimals=2)')->toVerbal($amount);
           $row->amount = str_replace('&nbsp;', '', $row->amount);
           $row->amount = str::removeWhiteSpace($row->amount);
           
           if($documentType == 'budget'){
               $row->sayWords = self::spellAmount($fields);
           }
       }
       
       // Заместване на данните в шаблона
       $tpl = getTplFromFile('bank/tpl/SinglePaymentOrder.shtml');
       $tpl->push('css/Application.css', 'CSS');
       $tpl->push('bank/tpl/css/belejka.css', 'CSS');
       $tpl->placeObject($row);

       return $tpl;
    }
    
    
    /**
     * Генерира файл хендлър към платежно нареждане с данни за 'Моята фирма'
     *
     * @param string|null $reason
     * @param array $fields
     *
     *          ['currencyCode'] - код на валута, ако няма тази от нашия ибан
     *          ['amount']       - сума във валутата
     *          ['valior']       - вальор
     *          ['documentType'] - кредитно плащане или плащане от/към бюджета
     *          ['ownAccount']   - ид на наша сметка, информацията я извлича от нея
     *
     * @return core_ET
     */
    public static function getBlankAsPdf($fileName = null, $reason = null, $fields = array())
    {
        $tpl = self::getFilledBlankTpl($reason, $fields);
        if(empty($fileName)){
            $fileName = "po_" . substr(md5($reason), 0, 4);
        }
        
        $fileHandler = doc_PdfCreator::convert($tpl, $fileName);
        
        return $fileHandler;
    }
}
