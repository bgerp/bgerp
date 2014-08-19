<?php
/**
 * Клас 'sales_Services'
 *
 * Мениджър на Предавателен протокол
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Services extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Предавателни протоколи';


    /**
     * Абревиатура
     */
    public $abbr = 'Pss';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf, bgerp_DealIntf, acc_TransactionSourceIntf=sales_transaction_Service';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, sales_Wrapper, plg_Sorting, plg_Printing, acc_plg_Contable,
                    doc_DocumentPlg, plg_ExportCsv, acc_plg_DocumentSummary, plg_Search,
					doc_EmailCreatePlg, bgerp_plg_Blank, doc_plg_TplManager, doc_plg_HidePrices, plg_LastUsedKeys';

    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,sales';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,sales';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,sales';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,sales';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo,sales';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, valior, folderId, amountDeliveredVat, createdOn, createdBy';


    /**
     * Детайла, на модела
     */
    public $details = 'sales_ServicesDetails';
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Предавателен протокол';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'sales/tpl/SingleLayoutServices.shtml';

   
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "3.81|Търговия";
   
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'amountDelivered';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/shipment.png';
    
    
    /**
     * Опашка от записи за записване в on_Shutdown
     */
    protected $updated = array();
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'locationId, note';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('valior', 'date', 'caption=Дата, mandatory,oldFieldName=date');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)', 'input=none,caption=Плащане->Валута');
        $this->FLD('currencyRate', 'double(decimals=2)', 'caption=Валута->Курс,input=hidden'); 
        $this->FLD('chargeVat', 'enum(yes=Включено, separate=Отделно, exempt=Oсвободено, no=Без начисляване)', 'caption=ДДС,input=hidden');
        
        $this->FLD('amountDelivered', 'double(decimals=2)', 'caption=Доставено,input=none,summary=amount'); // Сумата на доставената стока
        $this->FLD('amountDeliveredVat', 'double(decimals=2)', 'caption=Доставено,summary=amount,input=none');
        $this->FLD('amountDiscount', 'double(decimals=2)', 'input=none');
        
        // Контрагент
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden');
        
        // Доставка
        $this->FLD('locationId', 'key(mvc=crm_Locations, select=title)', 'caption=Обект до,silent');
        $this->FLD('deliveryTime', 'datetime', 'caption=Срок до');
        
        // Допълнително
        $this->FLD('note', 'richtext(bucket=Notes,rows=3)', 'caption=Допълнително->Бележки');
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
            'caption=Статус, input=none'
        );
    }


    /**
     * След промяна в детайлите на обект от този клас
     */
    public static function on_AfterUpdateDetail(core_Manager $mvc, $id, core_Manager $detailMvc)
    {
         // Запомняне кои документи трябва да се обновят
    	$mvc->updated[$id] = $id;
    }
    
    
    /**
     * Обновява информацията на документа
     * @param int $id - ид на документа
     */
    public function updateMaster($id)
    {
    	$rec = $this->fetchRec($id);
    	
    	$query = $this->sales_ServicesDetails->getQuery();
        $query->where("#shipmentId = '{$id}'");
        
        deals_Helper::fillRecs($this, $query->fetchAll(), $rec);
        
        // ДДС-т е отделно amountDeal  е сумата без ддс + ддс-то, иначе самата сума си е с включено ддс
        $amount = ($rec->chargeVat == 'separate') ? $this->_total->amount + $this->_total->vat : $this->_total->amount;
        $amount -= $this->_total->discount;
        $rec->amountDelivered = $amount * $rec->currencyRate;
        $rec->amountDeliveredVat = $this->_total->vat * $rec->currencyRate;
        $rec->amountDiscount = $this->_total->discount * $rec->currencyRate;
		
        $this->save($rec);
    }
    
    
    /**
     * След изпълнение на скрипта, обновява записите, които са за ъпдейт
     */
    public static function on_Shutdown($mvc)
    {
        if(count($mvc->updated)){
        	foreach ($mvc->updated as $id) {
	        	$mvc->updateMaster($id);
	        }
        }
    }
    
    
    /**
     * След създаване на запис в модела
     */
    public static function on_AfterCreate($mvc, $rec)
    {
        $origin = static::getOrigin($rec);
        
        // Ако новосъздадения документ има origin, който поддържа bgerp_AggregateDealIntf,
        // използваме го за автоматично попълване на детайлите на протокола
        expect($origin->haveInterface('bgerp_DealAggregatorIntf'));
            
        /* @var $aggregatedDealInfo bgerp_iface_DealResponse */
        $aggregatedDealInfo = $origin->getAggregateDealInfo();
        $agreedProducts = $aggregatedDealInfo->get('products');
            
        if(count($agreedProducts)){
        	foreach ($agreedProducts as $product) {
        		$info = cls::get($product->classId)->getProductInfo($product->productId, $product->packagingId);
        	
        		// Колко остава за експедиране от продукта
        		$toShip = $product->quantity - $product->quantityDelivered;
        	
        		// Пропускат се експедираните и складируемите артикули
        		if (isset($info->meta['canStore']) || ($toShip <= 0)) continue;
        	
        		$shipProduct = new stdClass();
        		$shipProduct->shipmentId  = $rec->id;
        		$shipProduct->classId     = $product->classId;
        		$shipProduct->productId   = $product->productId;
        		$shipProduct->packagingId = $product->packagingId;
        		$shipProduct->quantity    = $toShip;
        		$shipProduct->price       = $product->price;
        		$shipProduct->uomId       = $product->uomId;
        		$shipProduct->discount    = $product->discount;
        		$shipProduct->quantityInPack = ($product->packagingId) ? $info->packagingRec->quantity : 1;
        	
        		$mvc->sales_ServicesDetails->save($shipProduct);
        	}
        }
    }
    
    
	/**
     * Подготвя данните на хедъра на документа
     */
    private function prepareHeaderInfo(&$row, $rec)
    {
    	$ownCompanyData = crm_Companies::fetchOwnCompany();
        $Companies = cls::get('crm_Companies');
        $row->MyCompany = cls::get('type_Varchar')->toVerbal($ownCompanyData->company);
        $row->MyAddress = $Companies->getFullAdress($ownCompanyData->companyId);
        
        $uic = drdata_Vats::getUicByVatNo($ownCompanyData->vatNo);
        if($uic != $ownCompanyData->vatNo){
    		$row->MyCompanyVatNo = $ownCompanyData->vatNo;
    	}
    	$row->uicId = $uic;
    	
    	// Данните на клиента
        $ContragentClass = cls::get($rec->contragentClassId);
    	$cData = $ContragentClass->getContragentData($rec->contragentId);
    	$row->contragentName = cls::get('type_Varchar')->toVerbal(($cData->person) ? $cData->person : $cData->company);
        $row->contragentAddress = $ContragentClass->getFullAdress($rec->contragentId);
    }
    
    
    /**
     * След рендиране на сингъла
     */
    function on_AfterRenderSingle($mvc, $tpl, $data)
    {
    	if(Mode::is('printing') || Mode::is('text', 'xhtml')){
    		$tpl->removeBlock('header');
    	}
    }
    
    
	/**
     * Подготвя данните (в обекта $data) необходими за единичния изглед
     */
    public function prepareSingle_($data)
    {
    	parent::prepareSingle_($data);
    	
    	$rec = &$data->rec;
    	if(empty($data->noTotal)){
    		$data->summary = deals_Helper::prepareSummary($this->_total, $rec->valior, $rec->currencyRate, $rec->currencyId, $rec->chargeVat);
    		$data->row = (object)((array)$data->row + (array)$data->summary);
    	}
    }
    
    
    /**
     * След подготовка на единичния изглед
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	$rec = &$data->rec;
    	$data->row->header = $mvc->singleTitle . " #<b>{$mvc->abbr}{$data->row->id}</b> ({$data->row->state})";
	}
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Задаване на стойности на полетата на формата по подразбиране
        $form = &$data->form;
        $rec  = &$form->rec;
        $form->setDefault('valior', dt::now());
        
        if (empty($rec->folderId)) {
            expect($rec->folderId = core_Request::get('folderId', 'key(mvc=doc_Folders)'));
        }
        
        // Определяне на контрагента (ако още не е определен)
        if (empty($rec->contragentClassId)) {
            $rec->contragentClassId = doc_Folders::fetchCoverClassId($rec->folderId);
        }
        
        if (empty($rec->contragentId)) {
            $rec->contragentId = doc_Folders::fetchCoverId($rec->folderId);
        }
        
        // Поле за избор на локация - само локациите на контрагента по продажбата
        $form->getField('locationId')->type->options = 
            array('' => '') + crm_Locations::getContragentOptions($rec->contragentClassId, $rec->contragentId);
        
        // Ако създаваме нов запис и то базиран на предхождащ документ ...
        if (empty($form->rec->id)) {
        	
            // ... проверяваме предхождащия за bgerp_DealIntf
            $origin = ($form->rec->originId) ? doc_Containers::getDocument($form->rec->originId) : doc_Threads::getFirstDocument($form->rec->threadId);
            expect($origin->haveInterface('bgerp_DealAggregatorIntf'));
            	
            $dealInfo = $origin->getAggregateDealInfo();
            
            $form->setDefault('currencyId', $dealInfo->get('currency'));
            $form->setDefault('currencyRate', $dealInfo->get('rate'));
            $form->setDefault('locationId', $dealInfo->get('deliveryLocation'));
            $form->setDefault('deliveryTime', $dealInfo->get('deliveryTime'));
            $form->setDefault('chargeVat', $dealInfo->get('vatType'));
        }
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(isset($fields['-list'])){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    		if($rec->amountDeliveredVat){
    			$row->amountDeliveredVat = "<span class='cCode' style='float:left'>{$rec->currencyId}</span> &nbsp;{$row->amountDeliveredVat}";
    		} else {
    			$row->amountDeliveredVat = "<span class='quiet'>0.00</span>";
    		}
    	}
    	
    	if(isset($fields['-single'])){
    		$mvc->prepareHeaderInfo($row, $rec);
    	}
    }


    /**
     * Протокола не може да бъде начало на нишка; може да се създава само в съществуващи нишки
     *
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
        return FALSE;
    }
    
    
    /**
     * Може ли протокол да се добави в посочената нишка?
     * Експедиционните нареждания могат да се добавят само в нишки с начало - документ-продажба
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
    	$docState = $firstDoc->fetchField('state');
    
    	// Ако началото на треда е активирана продажба
    	if(($firstDoc->instance() instanceof sales_Sales) && $docState == 'active'){
    		
    		return TRUE;
    	}
    	
    	return FALSE;
    }
        
    
    /**
     * @param int $id key(mvc=sales_Sales)
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
        expect($rec = $this->fetch($id));
        $title = "Протокол за доставка на услуги №{$rec->id} / " . $this->getVerbal($rec, 'valior');
        $row = (object)array(
            'title'    => $title,
            'authorId' => $rec->createdBy,
            'author'   => $this->getVerbal($rec, 'createdBy'),
            'state'    => $rec->state,
            'recTitle' => $title
        );
        
        return $row;
    }
    
    
	/**
     * Връща масив от използваните нестандартни артикули в протоколa
     * @param int $id - ид на протоколa
     * @return param $res - масив с използваните документи
     * 					['class'] - инстанция на документа
     * 					['id'] - ид на документа
     */
    public function getUsedDocs_($id)
    {
    	$res = array();
    	$dQuery = $this->sales_ServicesDetails->getQuery();
    	$dQuery->EXT('state', 'sales_Services', 'externalKey=shipmentId');
    	$dQuery->where("#shipmentId = '{$id}'");
    	$dQuery->groupBy('productId,classId');
    	while($dRec = $dQuery->fetch()){
    		$productMan = cls::get($dRec->classId);
    		if(cls::haveInterface('doc_DocumentIntf', $productMan)){
    			$res[] = (object)array('class' => $productMan, 'id' => $dRec->productId);
    		}
    	}
    	return $res;
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
        $rec = $this->fetchRec($id);
        
        // Конвертираме данъчната основа към валутата идваща от продажбата
        $aggregator->setIfNot('shippedValior',$rec->valior);
        $aggregator->setIfNot('deliveryLocation', $rec->locationId);
        $aggregator->setIfNot('deliveryTime', $rec->deliveryTime);
        
        $dQuery = sales_ServicesDetails::getQuery();
        $dQuery->where("#shipmentId = {$rec->id}");
        
        while ($dRec = $dQuery->fetch()) {
            $p = new stdClass();
            
            $p->classId     = $dRec->classId;
            $p->productId   = $dRec->productId;
            $p->packagingId = $dRec->packagingId;
	        
            $aggregator->push('shippedPacks', $p);
        }
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
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	$mvc->setTemplates($res);
    }
    
    
	/**
     * Зарежда шаблоните на продажбата в doc_TplManager
     */
    private function setTemplates(&$res)
    {
    	$tplArr[] = array('name' => 'Протокол за извършени услуги', 
    					  'content' => 'sales/tpl/SingleLayoutServices.shtml', 'lang' => 'bg', 
    					  'toggleFields' => array('masterFld' => NULL, 'sales_ServicesDetails' => 'packagingId,packQuantity,weight,volume'));
    	$tplArr[] = array('name' => 'Протокол за извършени услуги с цени', 
    					  'content' => 'sales/tpl/SingleLayoutServicesPrices.shtml', 'lang' => 'bg',
    					  'toggleFields' => array('masterFld' => NULL, 'sales_ServicesDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));
        
        $res .= doc_TplManager::addOnce($this, $tplArr);
    }
    
    
	/**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашия протокол за продажба на услуги") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
    
    
     /**
      * Добавя ключови думи за пълнотекстово търсене, това са името на
      * документа или папката
      */
     function on_AfterGetSearchKeywords($mvc, &$res, $rec)
     {
     	// Тук ще генерираме всички ключови думи
     	$detailsKeywords = '';

     	// заявка към детайлите
     	$query = sales_ServicesDetails::getQuery();
     	// точно на тази фактура детайлите търсим
     	$query->where("#shipmentId  = '{$rec->id}'");
     	
	        while ($recDetails = $query->fetch()){
	        	// взимаме заглавията на продуктите
	        	$productTitle = cls::get($recDetails->classId)->getTitleById($recDetails->productId);
	        	// и ги нормализираме
	        	$detailsKeywords .= " " . plg_Search::normalizeText($productTitle);
	        }
	        
    	// добавяме новите ключови думи към основните
    	$res = " " . $res . " " . $detailsKeywords;
     }
     
     
	/**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        return tr("|Протокол за извършени услуги|* №") . $rec->id;
    }
}