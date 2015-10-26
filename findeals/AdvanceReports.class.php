<?php
/**
 * Клас 'findeals_AdvanceReports'
 *
 * Мениджър за Авансови отчети
 *
 *
 * @category  bgerp
 * @package   findeals
 * @author    Ivelin Dimov<ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class findeals_AdvanceReports extends core_Master
{
    
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'deals_AdvanceReports';
	
    
    /**
     * Заглавие
     */
    public $title = 'Авансови отчети';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Adr';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=findeals_transaction_AdvanceReport, bgerp_DealIntf, email_DocumentIntf, doc_ContragentDataIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, findeals_Wrapper, plg_Sorting, plg_Printing, acc_plg_Contable, 
                    doc_DocumentPlg, acc_plg_DocumentSummary, plg_Search,
					doc_EmailCreatePlg, bgerp_plg_Blank, doc_plg_HidePrices';

    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,findeals';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,findeals';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,findeals';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,findeals';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,findeals';


    /**
     * Кой може да го види?
     */
    public $canViewprices = 'ceo,findeals';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo,findeals';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт,valior,title=Документ,number,currencyId=Валута, total,folderId,createdOn,createdBy';

    
   /**
    * Основна сч. сметка
    */
    public static $baseAccountSysId = '422';
    
    
    /**
     * Икона на единичния обект
     */
    var $singleIcon = 'img/16/legend.png';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'findeals_AdvanceReportDetails' ;
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Авансов отчет';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'findeals/tpl/SingleAdvanceReportLayout.shtml';

   
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.7|Финанси";
   
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'total';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'valior,number,folderId, id';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('operationSysId', 'varchar', 'caption=Операция,input=hidden');
    	$this->FLD("valior", 'date()', 'caption=Дата, mandatory');
    	$this->FLD("number", 'int', 'caption=Номер');
    	$this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута->Код');
    	$this->FLD('rate', 'double(smartRound,decimals=2)', 'caption=Валута->Курс');
    	$this->FLD('total', 'double(decimals=2)', 'input=none,caption=Общо,notNull');
    	$this->FLD('creditAccount', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'input=none');
    	$this->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Сторниран)', 'caption=Статус, input=none');
    
    	$this->setDbUnique('number');
    }
    
    
    /**
     *  Обработка на формата за редакция и добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	$data->form->setDefault('valior', dt::now());
    	
    	expect($origin = $mvc->getOrigin($data->form->rec));
    	expect($origin->haveInterface('bgerp_DealAggregatorIntf'));
    	$dealInfo = $origin->getAggregateDealInfo();
    	$options = self::getOperations($dealInfo->get('allowedPaymentOperations'));
    	expect(count($options));
    	
    	$data->form->dealInfo = $dealInfo;
    	$data->form->setDefault('operationSysId', 'debitDeals');
    	
    	$data->form->setDefault('currencyId', currency_Currencies::getIdByCode($dealInfo->get('currency')));
    	$data->form->setDefault('rate', $dealInfo->get('rate'));
    	
    	$data->form->addAttr('currencyId', array('onchange' => "document.forms['{$data->form->formAttr['id']}'].elements['rate'].value ='';"));
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
    	$rec = &$form->rec;
    	 
    	if ($form->isSubmitted()){
    		$operations = $form->dealInfo->get('allowedPaymentOperations');
    		$operation = $form->dealInfo->allowedPaymentOperations[$rec->operationSysId];
    		$rec->creditAccount = $operation['credit'];
    		
    		$currencyCode = currency_Currencies::getCodeById($rec->currencyId);
    		if(!$rec->rate){
    			$rec->rate = round(currency_CurrencyRates::getRate($rec->valior, $currencyCode, NULL), 4);
    			if(!$rec->rate){
    				$form->setError('rate', "Не може да се изчисли курс");
    			}
    		} else {
    			if($msg = currency_CurrencyRates::hasDeviation($rec->rate, $rec->valior, $currencyCode, NULL)){
    				$form->setWarning('rate', $msg);
    			}
    		}
    	}
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	// Ако след запис, няма номер, тогава номера му става ид-то на документа
    	if(!$rec->number){
    		$rec->number = $rec->id;
    		$mvc->save($rec);
    	}
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$rec->total /= $rec->rate;
    	$row->total = $mvc->getFieldType('total')->toVerbal($rec->total);
    	
    	if($fields['-single']){
    		
    		if($rec->currencyId == acc_Periods::getBaseCurrencyId($rec->valior)){
    			unset($row->rate);
    		}
    	}
    	
    	$row->title = $mvc->getLink($rec->id, 0);
    }
    
    
    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
    	$rec = $this->fetchRec($id);
    	$rec->total = 0;
    	
    	$query = $this->findeals_AdvanceReportDetails->getQuery();
    	$query->where("#reportId = '{$id}'");
    	while($dRec = $query->fetch()){
    		$rec->total += $dRec->amount * (1 + $dRec->vat);
    	}
    
    	return $this->save($rec);
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public function canAddToFolder($folderId)
    {
    	return FALSE;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(__CLASS__);
    	 
    	return "{$self->singleTitle} №{$rec->id}";
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
    	$docState = $firstDoc->fetchField('state');
    
    	if(($firstDoc->haveInterface('bgerp_DealAggregatorIntf') && $docState == 'active')){
    		
    		if($firstDoc->className != 'findeals_AdvanceDeals') return FALSE;
    		
    		$options = self::getOperations($firstDoc->getPaymentOperations());
    			
    		return count($options) ? TRUE : FALSE;
    	}
    
    	return FALSE;
    }
    
    
    /**
     * Връща платежните операции
     */
    protected static function getOperations($operations)
    {
    	$options = array();
    	
    	// Оставяме само тези операции в коитос е дебитира основната сметка на документа
    	foreach ($operations as $sysId => $op){
    		if($op['credit'] == static::$baseAccountSysId){
    			$options[$sysId] = $op['title'];
    		}
    	}
    	 
    	return $options;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(!empty($data->toolbar->buttons['btnAdd'])){
    		$data->toolbar->removeBtn('btnAdd');
    	}
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    	$row = new stdClass();
    	$row->title = $this->singleTitle . " №{$id}";
    	$row->authorId = $rec->createdBy;
    	$row->author = $this->getVerbal($rec, 'createdBy');
    	$row->state = $rec->state;
    	$row->recTitle = $row->title;
    
    	return $row;
    }
    
    
    /**
     * Документа не може да се активира ако има детайл с количество 0
     */
    public static function on_AfterCanActivate($mvc, &$res, $rec)
    {
    	if(!$rec->total) $res = FALSE;
    }
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
    	$ownCompanyData = crm_Companies::fetchOwnCompany();
    	$Companies = cls::get('crm_Companies');
    	$data->row->MyCompany = cls::get('type_Varchar')->toVerbal($ownCompanyData->company);
    	$data->row->MyAddress = $Companies->getFullAdress($ownCompanyData->companyId);
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
    	$handle = static::getHandle($id);
    	$tpl = new ET(tr("Моля запознайте се с нашия авансов отчет") . ': #[#handle#]');
    	$tpl->append($handle, 'handle');
    
    	return $tpl->getContent();
    }
    
    
    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	// Ако резултата е 'no_one' пропускане
    	if($res == 'no_one') return;
    }
    
    
    /**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     *
     * @param int|object $id
     * @return bgerp_iface_DealAggregator
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function pushDealInfo($id, &$aggregator)
    {
    	 
    }
}
