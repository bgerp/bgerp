<?php 


/**
 * Документ за Нареждане Разписки
 *
 *
 * @category  bgerp
 * @package   cash
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
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
    var $loadList = 'plg_RowTools, bank_Wrapper, bank_TemplateWrapper, plg_Printing, acc_plg_DocumentSummary,
     	plg_Sorting, doc_DocumentPlg,  plg_Search, doc_plg_MultiPrint, bgerp_plg_Blank';
    
    
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
     * Кой може да пише?
     */
    var $canWrite = 'bank, ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'bank, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'bank/tpl/SingleCashWithdrawOrder.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'valior, reason, proxyName, proxyEgn, proxyIdCard';
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "4.92|Финанси";
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('amount', 'double(decimals=2,max=2000000000,min=0)', 'caption=Сума,mandatory,width=6em,summary=amount');
    	$this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута,width=6em');
    	$this->FLD('reason', 'varchar(255)', 'caption=Основание,width=100%,mandatory');
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,width=6em,mandatory');
    	$this->FLD('ordererIban', 'key(mvc=bank_OwnAccounts,select=bankAccountId)', 'caption=От->Сметка,mandatory,width=16em');
    	$this->FLD('execBank', 'varchar(255)', 'caption=От->Банка,width=16em,mandatory');
    	$this->FLD('execBankBranch', 'varchar(255)', 'caption=От->Клон,width=16em');
        $this->FLD('execBankAdress', 'varchar(255)', 'caption=От->Адрес,width=16em');
    	$this->FLD('proxyName', 'varchar(255)', 'caption=Упълномощено лице->Име,mandatory');
    	$this->FLD('proxyEgn', 'drdata_EgnType', 'caption=Упълномощено лице->ЕГН,mandatory');
    	$this->FLD('proxyIdCard', 'varchar(16)', 'caption=Упълномощено лице->Лк. No,mandatory');
    }
    
    
	/**
	 *  Подготовка на филтър формата
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
	{
		// Добавяме към формата за търсене търсене по Каса
		bank_OwnAccounts::prepareBankFilter($data, array('ordererIban'));
	}
	
	
    /**
     *  Обработка на формата за редакция и добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	$form = &$data->form;
    	$originId = $form->rec->originId;
    	
    	// Намираме кой е последния запис от този клас в същия тред
    	$query = static::getQuery();
    	$query->where("#folderId = {$form->rec->folderId}");
    	$query->orderBy('createdOn', 'DESC');
    	$query->limit(1);
    	if($lastRec = $query->fetch()) {
    		$form->setDefault('ordererIban', $lastRec->ordererIban);
    		$form->setDefault('execBank', $lastRec->execBank);
    		$form->setDefault('execBankBranch', $lastRec->execBankBranch);
    		$form->setDefault('execBankAdress', $lastRec->execBankAdress);
    		$form->setDefault('proxyEgn', $lastRec->proxyEgn);
    		$form->setDefault('proxyIdCard', $lastRec->proxyIdCard);
    	} 
    	
    	if($originId) {
    		$doc = doc_Containers::getDocument($originId);
    		$class = $doc->className;
    		$dId = $doc->that;
    		$rec = $class::fetch($dId);
    		
    		// Извличаме каквато информация можем от оригиналния документ
    		$form->setDefault('currencyId', $rec->currencyId);
    		$form->setDefault('ordererIban',$rec->ordererIban);
    		$form->setDefault('amount', $rec->amount);
    		$form->setDefault('reason', $rec->reason);
    		$form->setDefault('valior', $rec->valior);
    		$account = bank_OwnAccounts::getOwnAccountInfo($rec->ordererIban);
    		$form->setDefault('execBank', $account->bank);
    		
    		// Ако контрагента е лице, слагаме името му за получател
    		$coverClass = doc_Folders::fetchCoverClassName($form->rec->folderId);
    		if( $coverClass == 'crm_Persons') {
    			$form->setDefault('proxyName', $rec->contragentName);
    			
    			// EGN на контрагента 
    			$proxyEgn = crm_Persons::fetchField($rec->contragentId, 'egn');
				$form->setDefault('proxyEgn', $proxyEgn);
    			
				// Номер на Л. картата на лицето ако е записана в системата
				if($idCard = crm_ext_IdCards::fetch("#personId = {$rec->contragentId}")) {
					$form->setDefault('proxyIdCard', $idCard);
				}
			}
    	} else {
    		$today = dt::verbal2mysql();
    		$form->setDefault('currencyId', acc_Periods::getBaseCurrencyId($today));
    		$form->setDefault('ordererIban', bank_OwnAccounts::getCurrent());
    		$account = bank_OwnAccounts::getOwnAccountInfo();
    		$form->setDefault('execBank', $account->bank);
    		$form->setDefault('valior', $today);
    	}
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->number = static::getHandle($rec->id);
    	
    	if($fields['-single']) {
	    	$spellNumber = cls::get('core_SpellNumber');
			$row->sayWords = $spellNumber->asCurrency($rec->amount, 'bg', FALSE);
			
			$myCompany = crm_Companies::fetchOwnCompany();
			$row->ordererName = $myCompany->company;
	    	
			// При принтирането на 'Чернова' скриваме заглавието
	    	if(!Mode::is('printing')){
	    		$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
	    	}
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
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    	
    	return $firstDoc->className == 'bank_IncomeDocument' || $firstDoc->className == 'bank_CostDocument';
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
}