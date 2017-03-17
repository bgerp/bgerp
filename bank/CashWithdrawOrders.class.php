<?php 


/**
 * Документ за Нареждане разписки
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_CashWithdrawOrders extends bank_DocumentBlank
{
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Нареждане разписка";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools2, bank_Wrapper, acc_plg_DocumentSummary, doc_ActivatePlg,
         plg_Sorting, doc_DocumentPlg, plg_Printing,  plg_Search, doc_plg_MultiPrint, cond_plg_DefaultValues, doc_EmailCreatePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "number=Номер, reason, valior, amount, currencyId, proxyName=Лице, state, createdOn, createdBy";
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Нареждане разписка';
    
    
    /**
     * Икона на документа
     */
    public $singleIcon = 'img/16/nrrz.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Nr";
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'bank/tpl/SingleCashWithdrawOrder.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'valior, reason, proxyName, proxyEgn, proxyIdCard';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
        'ordererIban'    => 'lastDocUser|lastDoc',
        'execBank'       => 'lastDocUser|lastDoc',
        'execBankBranch' => 'lastDocUser|lastDoc',
        'execBankAdress' => 'lastDocUser|lastDoc',
        'proxyName'      => 'lastDocUser|lastDoc',
        'proxyEgn'       => 'lastDocUser|lastDoc',
        'proxyIdCard'    => 'lastDocUser|lastDoc',
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
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
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
        $tpl = new ET(tr("Моля запознайте се с нашето нареждане разписка") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'add' && isset($rec)){
    		
    		// Ако контрагента е лице само тогава може да се добавя платежно нареждане
    		if(isset($rec->originId)){
    			$origin = doc_Containers::getDocument($rec->originId);
    			if(!$origin->isInstanceOf('bank_SpendingDocuments')){
    				$requiredRoles = 'no_one';
    			} else {
    				$originRec = $origin->fetch();
    				$Cover = doc_Folders::getCover($originRec->folderId);
    				if(!$Cover->haveInterface('crm_PersonAccRegIntf')){
    					$requiredRoles = 'no_one';
    				}
    			}
    		}
    	}
    }
}