<?php



/**
 * Мениджър на документ за приключване на счетоводен период
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_ClosePeriods extends core_Master
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'acc_TransactionSourceIntf=acc_transaction_ClosePeriod';
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Приключване на периоди";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools2, acc_Wrapper, acc_plg_Contable, doc_DocumentPlg, doc_plg_HidePrices, doc_plg_SelectFolder';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "title=Заглавие,periodId,state,createdOn,createdBy";
    
    
    /**
     * Може ли да се контира въпреки, че има приключени пера в транзакцията
     */
    public $canUseClosedItems = TRUE;
    
    
    /**
     * Дали при възстановяване/контиране/оттегляне да се заключва баланса
     *
     * @var boolean TRUE/FALSE
     */
    public $lockBalances = TRUE;
    
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Приключване на период';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Cp";
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'acc,ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'accMaster,ceo';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'accMaster,ceo';
    
    
    /**
     * Кой може да го отхвърли?
     */
    public $canReject = 'accMaster,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,acc';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,acc';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'acc/tpl/SingleLayoutClosePeriods.shtml';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "6.3|Счетоводни";
    
    
    /**
     * Полета с цени, които не трябва да се показват ако потребителя няма права да ги вижда
     */
    public $priceFields = 'amountFromInvoices, amountVatGroup1, amountVatGroup2, amountVatGroup3, amountVatGroup4, amountWithoutInvoice';
    
    
    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'doc_UnsortedFolders';

    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD("periodId", 'key(mvc=acc_Periods, select=title, allowEmpty)', 'caption=Период,mandatory,silent');
    	
    	$this->FLD("amountFromInvoices", 'double(decimals=2)', 'input=none,caption=ДДС от фактури с касови бележки');
    	$this->FLD("amountVatGroup1", 'double(decimals=2,min=0)', 'caption=ДДС от касов апарат->Група A,notNull,default=0');
    	$this->FLD("amountVatGroup2", 'double(decimals=2,min=0)', 'caption=ДДС от касов апарат->Група Б,notNull,default=0');
    	$this->FLD("amountVatGroup3", 'double(decimals=2,min=0)', 'caption=ДДС от касов апарат->Група В,notNull,default=0');
    	$this->FLD("amountVatGroup4", 'double(decimals=2,min=0)', 'caption=ДДС от касов апарат->Група Г,notNull,default=0');
    	
    	$this->FLD("amountKeepBalance", 'double(decimals=2,min=0)', 'caption=Други разходи->Салдо за поддържане,notNull,default=0');
    	
    	$this->FLD('state',
    			'enum(draft=Чернова, active=Активиран, rejected=Оттеглен,stopped=Спряно)',
    			'caption=Статус, input=none'
    	);
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$pQuery = acc_Periods::getQuery();
    	$pQuery->where("#state = 'pending'");
    	
    	$data->form->addAttr('periodId', array('onchange' => "addCmdRefresh(this.form);this.form.submit();"));
    	
    	$options = acc_Periods::makeArray4Select(NULL, array("#state = 'active' OR #state = 'pending'", $root));
    	$data->form->setOptions('periodId', $options);
    	
    	if(empty($data->form->rec->id)){
    		$data->form->setDefault('state', 'draft');
    	}
    	
    	$data->form->setDefault('valior', dt::today());
    	
    	if(isset($data->form->rec->periodId)){
    		$conf = core_Packs::getConfig('sales');
    		if($conf->SALE_INV_HAS_FISC_PRINTERS == 'yes'){
    			$pTo = acc_Periods::fetchField($data->form->rec->periodId, 'end');
    			$baseCurrencyCode = acc_Periods::getBaseCurrencyCode($pTo);
    			foreach (range(1, 4) as $i){
    				$data->form->setField("amountVatGroup{$i}", "unit={$baseCurrencyCode}");
    			}
    		} else {
    			$data->form->setField("amountVatGroup1", 'input=none');
    			$data->form->setField("amountVatGroup2", 'input=none');
    			$data->form->setField("amountVatGroup3", 'input=none');
    			$data->form->setField("amountVatGroup4", 'input=none');
    		}
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		$rec = &$form->rec;
    		if($mvc->fetch("#state != 'rejected' AND #periodId = '{$rec->periodId}' AND #id != '{$rec->id}'")){
    			$form->setError("periodId", "Има вече активиран/чернова документ за избрания период");
    		}
    		
    		$conf = core_Packs::getConfig('sales');
    		if($conf->SALE_INV_HAS_FISC_PRINTERS == 'yes'){
    			
    			// От избрания период извличаме сумата на фактурите с касова бележка
    			$periodRec = acc_Periods::fetch($rec->periodId);
    			$rec->amountFromInvoices = sales_Invoices::getVatAmountInCash($periodRec->start, $periodRec->end);
    			
    			$total = $rec->amountVatGroup1 + $rec->amountVatGroup2 + $rec->amountVatGroup3 + $rec->amountVatGroup4;
    			if($total < $rec->amountFromInvoices){
    				$form->setWarning('amountVatGroup1,amountVatGroup2,amountVatGroup3,amountVatGroup4', "|ДДС по ф-ри в брой|* '{$rec->amountFromInvoices}', |е по-голямо от ДДС по касов апарат|*");
    			}
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($fields['-single']){
    		
    		$row->baseCurrencyId = acc_Periods::getBaseCurrencyCode($rec->valior);
    	
    		foreach (range(1, 4) as $id){
    			if(isset($row->{"amountVatGroup{$id}"})){
    				$row->{"amountVatGroup{$id}"} .= " <span class='cCode'>{$row->baseCurrencyId}</span>";
    			}
    		}
    		
    		if($rec->state == 'active'){
    			$valior = acc_Journal::fetchByDoc($mvc->getClassId(), $rec->id)->valior;
    			$Date = cls::get('type_Date');
    			$row->valior = $Date->toVerbal($valior);
    		}
    		
    		$Double = cls::get('type_Double');
    		$Double->params['decimals'] = 2;
    		
    		$rec->amountWithoutInvoice = ($rec->amountVatGroup1 + $rec->amountVatGroup2 + $rec->amountVatGroup3 + $rec->amountVatGroup4) - $rec->amountFromInvoices;
    		$row->amountWithoutInvoice = $Double->toVerbal($rec->amountWithoutInvoice). " <span class='cCode'>{$row->baseCurrencyId}</span>";
    		if($rec->amountWithoutInvoice < 0){
    			$row->amountWithoutInvoice = "<span class='red'>{$row->amountWithoutInvoice}</span>";
    		}
    		
    		$row->amountFromInvoices .= " <span class='cCode'>{$row->baseCurrencyId}</span>";
    	}
    	
    	$row->title = $mvc->getHyperLink($rec->id, TRUE);
    	$balanceId = acc_Balances::fetchField("#periodId = {$rec->periodId}", 'id');
    	
    	if(acc_Balances::haveRightFor('single', $balanceId)){
    		$row->periodId = ht::createLink($row->periodId, array('acc_Balances', 'single', $balanceId), NULL, "ef_icon=img/16/table_sum.png, title = Оборотна ведомост|* {$row->periodId}");
    	}
    	
    	foreach (array('amountVatGroup1', 'amountVatGroup2', 'amountVatGroup3', 'amountVatGroup4', 'amountWithoutInvoice', 'amountKeepBalance', 'amountFromInvoices') as $fld){
    		if($rec->{$fld} == 0){
    			$row->{$fld} = "<span class='quiet'>{$row->{$fld}}</span>";
    		}
    	}
    	
    	$row->amountKeepBalance .= " <span class='cCode'>{$row->baseCurrencyId}</span>";
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
    	$me = cls::get(get_called_class());
    	
    	// Може да създаваме документ-а само в дефолт папката му
    	if ($folderId == doc_UnsortedFolders::forceCoverAndFolder((object)array('name' => $me->title))) {
    		
    		return TRUE;
    	}
    
    	return FALSE;
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    public function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    
    	$row = new stdClass();
    
    	$row->title = $this->getRecTitle($rec);
    	$row->subTitle = $this->getVerbal($rec, 'periodId');
    	$row->authorId = $rec->createdBy;
    	$row->author = $this->getVerbal($rec, 'createdBy');
    	$row->recTitle = $row->title;
    	$row->state = $rec->state;
    
    	return $row;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(get_called_class());
    	$title = acc_Periods::fetchField($rec->periodId, 'title');
    	
    	return tr("Приключване на|* \"{$title}\"");
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $res
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'restore' || $action == 'conto') && isset($rec)){
    		if($mvc->fetch("#state != 'rejected' AND #periodId = '{$rec->periodId}' AND #id != '{$rec->id}'")){
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * След подготовка на сингъла
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	$rec = &$data->rec;
    	
    	$bRec = acc_Balances::fetch("#periodId = {$rec->periodId}");
    	
    	if($rec->state == 'active' && acc_Balances::haveRightFor('single', $bRec)){
    		$data->info = $mvc->prepareInfo($data->rec);
    	}
    }
    
    
    /**
     * Подготвя информацията за направените транзакции в журнала
     * 
     * @param stdClass $rec - запис на документа
     * @return stdClass $info - подготвената информация
     */
    private function prepareInfo($rec)
    {
    	$info = array();
    	$Double = cls::get('type_Double');
    	$Double->params['decimals'] = 2;
    	
    	// Намираме кои сметки са засегнати от документа
    	$accounts = array();
    	$jRec = acc_Journal::fetchByDoc($this->getClassId(), $rec->id);
    	$jQuery = acc_JournalDetails::getQuery();
    	$jQuery->where("#journalId = '{$jRec->id}'");
    	$jQuery->show('debitAccId,creditAccId');
    	while($dRec = $jQuery->fetch()){
    		$accounts[$dRec->debitAccId] = $dRec->debitAccId;
    		$accounts[$dRec->creditAccId] = $dRec->creditAccId;
    	}
    	
    	if(!count($accounts)) return NULL;
    	
    	// За всяка от тях, намираме състоянието им след контирането на документа
    	$bId = acc_Balances::fetchField("#periodId = {$rec->periodId}", 'id');
    	$dQuery = acc_BalanceDetails::getQuery();
    	$dQuery->where("#balanceId = {$bId}");
    	$dQuery->where("#ent1Id IS NULL && #ent2Id IS NULL && #ent3Id IS NULL");
    	$dQuery->in("accountId", $accounts);
    		 
    	// Подготвяме какво е променено по всяка сметка
    	while ($dRec = $dQuery->fetch()){
    		$nRow = new stdClass();
    		$nRow->accountId = acc_Balances::getAccountLink($dRec->accountId, $bId, TRUE, TRUE);
    		foreach (array('baseQuantity', 'baseAmount', 'debitQuantity', 'debitAmount', 'creditQuantity', 'creditAmount', 'blQuantity', 'blAmount') as $fld){
    			$nRow->{$fld} = $Double->toVerbal($dRec->{$fld});
    			if($dRec->{$fld} < 0){
    				$nRow->{$fld} = "<span class='red'>{$nRow->{$fld}}</span>";
    			}
    		}
    		
    		$info[$dRec->accountId] = $nRow;
    	}
    	
    	ksort($info);
    	
    	// Връщаме историята на направените операции
    	return $info;
    }
    
    
    /**
     * След рендиране на еденичния изглед
     */
    public static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	if($data->info){
    		
    		// Показваме таблица със състоянието на сметките
    		$table = cls::get('core_TableView', array('mvc' => cls::get('acc_BalanceDetails')));
    		$fields = array();
    		$fields['accountId']      = 'Сметка';
    		$fields['baseQuantity']   = 'Начално салдо->К-во';
    		$fields['baseAmount']     = 'Начално салдо->Сума';
    		$fields['debitQuantity']  = 'Дебит->К-во';
    		$fields['debitAmount']    = 'Дебит->Сума';
    		$fields['creditQuantity'] = 'Кредит->К-во';
    		$fields['creditAmount']   = 'Кредит->Сума';
    		$fields['blQuantity']     = 'Крайно салдо->К-во';
    		$fields['blAmount']       = 'Крайно салдо->Сума';
    		
    		$details = $table->get($data->info, $fields);
    		
    		$tpl->append($details, 'INFO');
    	}
    }
}