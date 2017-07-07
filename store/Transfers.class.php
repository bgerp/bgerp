<?php



/**
 * Клас 'store_Transfers' - Документ за междускладови трансфери
 *
 * 
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_Transfers extends core_Master
{
	
	
    /**
     * Заглавие
     */
    public $title = 'Междускладови трансфери';


    /**
     * Име на документа в бързия бутон за добавяне в папката
     */
    public $buttonInFolderTitle = 'Трансфер';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'St';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, store_iface_DocumentIntf, acc_TransactionSourceIntf=store_transaction_Transfer, acc_AllowArticlesCostCorrectionDocsIntf,trans_LogisticDataIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_plg_StoreFilter, store_Wrapper, plg_Sorting, plg_Printing, acc_plg_Contable, acc_plg_DocumentSummary,
                    doc_DocumentPlg, trans_plg_LinesPlugin, doc_plg_BusinessDoc, plg_Search,plg_Clone,deals_plg_SetTermDate';

    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     * 
     * @see plg_Clone
     */
    public $cloneDetails = 'store_TransfersDetails';
    
    
    /**
     * Кой може да сторнира
     */
    public $canRevert = 'storeMaster, ceo';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'fromStore, toStore, folderId, id';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,store';


	/**
	 * Кой има право да променя?
	 */
	public $canChangeline = 'ceo,store';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,store';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,store';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,store';
    
    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'ceo,store';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo,store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'deliveryTime,valior, title=Документ, fromStore, toStore, volume, weight, folderId, createdOn, createdBy';


    /**
     * Детайла, на модела
     */
    public $details = 'store_TransfersDetails';
    

    /**
     * Кой е главния детайл
     *
     * @var string - име на клас
     */
    public $mainDetail = 'store_TransfersDetails';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Междускладов трансфер';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'store/tpl/SingleLayoutTransfers.shtml';

   
    /**
     * Файл за единичния изглед в мобилен
     */
    public $singleLayoutFileNarrow = 'store/tpl/SingleLayoutTransfersNarrow.shtml';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.5|Логистика";
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Как се казва полето в което е избран склада
     */
    public $storeFieldName = 'fromStore';


    /**
     * Дата на очакване
     */
    public $termDateFld = 'deliveryTime';
    
    
	/**
	 * Икона на единичния изглед
	 */
	public $singleIcon = 'img/16/transfers.png';


	/**
	 * Полета за филтър по склад
	 */
	public $filterStoreFields = 'fromStore,toStore';
	
	
	/**
	 * Кои полета от листовия изглед да се скриват ако няма записи в тях
	 */
	public $hideListFieldsIfEmpty = 'deliveryTime';
	
	
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('valior', 'date', 'caption=Дата, mandatory,oldFieldName=date');
        $this->FLD('fromStore', 'key(mvc=store_Stores,select=name)', 'caption=От склад,mandatory');
 		$this->FLD('toStore', 'key(mvc=store_Stores,select=name)', 'caption=До склад,mandatory');
 		$this->FLD('weight', 'cat_type_Weight', 'input=none,caption=Тегло');
        $this->FLD('volume', 'cat_type_Volume', 'input=none,caption=Обем');
        
        // Доставка
        $this->FLD('deliveryTime', 'datetime', 'caption=Натоварване');
        $this->FLD('lineId', 'key(mvc=trans_Lines,select=title,allowEmpty)', 'caption=Транспорт');
        
        // Допълнително
        $this->FLD('note', 'richtext(bucket=Notes,rows=3)', 'caption=Допълнително->Бележки');
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторниран,stopped=Спряно, pending=Заявка)', 
            'caption=Статус, input=none'
        );
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($requiredRoles == 'no_one') return;
    	
    	if(!deals_Helper::canSelectObjectInDocument($action, $rec, 'store_Stores', 'toStore')){
    		$requiredRoles = 'no_one';
    	}
    	
    	if($action == 'pending' && isset($rec) && $rec->id){
    		$Detail = cls::get($mvc->mainDetail);
    		if(!$Detail->fetchField("#{$Detail->masterKey} = {$rec->id}")){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
	/**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
    	$rec = $this->fetch($id);
    	$dQuery = store_TransfersDetails::getQuery();
    	$dQuery->where("#transferId = {$id}");
    	$measures = $this->getMeasures($dQuery->fetchAll());
    	
    	$rec->weight = $measures->weight;
    	$rec->volume = $measures->volume;
    	
    	return $this->save($rec);
    }
    
    
    /**
     * След рендиране на сингъла
     */
    protected static function on_AfterRenderSingle($mvc, $tpl, $data)
    {
    	if(Mode::is('printing') || Mode::is('text', 'xhtml')){
    		$tpl->removeBlock('header');
    	}
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(!$rec->weight) {
    		$row->weight = "<span class='quiet'>0</span>";
    	}
    		
    	if(!$rec->volume) {
    		$row->volume = "<span class='quiet'>0</span>";
    	}
    	
    	if($fields['-single']){
	    	
    		$row->fromStore = store_Stores::getHyperlink($rec->fromStore);
    		$row->toStore = store_Stores::getHyperlink($rec->toStore);
    		if(isset($rec->lineId)){
    			$row->lineId = trans_Lines::getHyperlink($rec->lineId);
    		}
    		
    		if ($rec->fromStore) {
    		    $fromStoreLocation = store_Stores::fetchField($rec->fromStore, 'locationId');
    		    if($fromStoreLocation){
    		        $row->fromAdress = crm_Locations::getAddress($fromStoreLocation);
    		    }
    		}
	    	
    		if ($rec->toStore) {
    		    $toStoreLocation = store_Stores::fetchField($rec->toStore, 'locationId');
    		    if($toStoreLocation){
    		        $row->toAdress = crm_Locations::getAddress($toStoreLocation);
    		    }
    		}
	    	
	    	$row->weight = ($row->weightInput) ? $row->weightInput : $row->weight;
	    	$row->volume = ($row->volumeInput) ? $row->volumeInput : $row->volume;
    	}
    	
    	if($fields['-list']){
    		$row->title = $mvc->getLink($rec->id, 0);
    		
    		$attr = array();
    		foreach (array('fromStore', 'toStore') as $storeFld){
	    		if(store_Stores::haveRightFor('single', $rec->{$storeFld})){
	    			$attr['ef_icon'] = 'img/16/home-icon.png';
	    			$row->{$storeFld} = ht::createLink($row->{$storeFld}, array('store_Stores', 'single', $rec->{$storeFld}), NULL, $attr);
	    		}
    		}
    	}
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param store_Stores $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $data->form->setDefault('valior', dt::now());
        $data->form->setDefault('fromStore', store_Stores::getCurrent('id', FALSE));
        $folderCoverId = doc_Folders::fetchCoverId($data->form->rec->folderId);
        $data->form->setDefault('toStore', $folderCoverId);
    	
        if(!trans_Lines::count("#state = 'active'")){
        	$data->form->setField('lineId', 'input=none');
        }
    }
    
    
	/**
     * След изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        if ($form->isSubmitted()) {
        	$rec = &$form->rec;
        	
        	if($rec->fromStore == $rec->toStore){
        		$form->setError('toStore', 'Складовете трябва да са различни');
        	}
        	
        	$rec->folderId = store_Stores::forceCoverAndFolder($rec->toStore);
        }
    }


    /**
     * СT не може да бъде начало на нишка; може да се създава само в съществуващи нишки
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
        $folderClass = doc_Folders::fetchCoverClassName($folderId);
    	
        return cls::haveInterface('store_iface_TransferFolderCoverIntf', $folderClass);
    }
        
    
    /**
     * @param int $id key(mvc=store_Receipts)
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
        expect($rec = $this->fetch($id));
        $title = $this->getRecTitle($rec);
        
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
     * Връща масив от използваните нестандартни артикули в СР-то
     * 
     * @param int $id - ид на СР
     * @return param $res - масив с използваните документи
     * 					['class'] - инстанция на документа
     * 					['id'] - ид на документа
     */
    public function getUsedDocs_($id)
    {
    	$res = array();
    	$dQuery = store_TransfersDetails::getQuery();
    	$dQuery->EXT('state', 'store_Transfers', 'externalKey=transferId');
    	$dQuery->where("#transferId = '{$id}'");
    	while($dRec = $dQuery->fetch()){
    		$cid = cat_Products::fetchField($dRec->newProductId, 'containerId');
    		$res[$cid] = $cid;
    	}
    	
    	return $res;
    }
    
    
	/**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getCoversAndInterfacesForNewDoc()
    {
    	return array('store_iface_TransferFolderCoverIntf');
    }
    
    
    /**
     * Помощен метод за показване на документа в транспортните линии
     * 
     * @param stdClass $rec - запис на документа
     * @return stdClass $row - вербалния запис
     */
    private function prepareLineRows($rec)
    {
    	$rec->weight = ($rec->weightInput) ? $rec->weightInput : $rec->weight;
    	$rec->volume = ($rec->volumeInput) ? $rec->volumeInput : $rec->volume;
    	
    	$row = $this->recToVerbal($rec, 'toAdress,fromStore,toStore,weight,volume,palletCountInput,-single');
    	
    	$row->rowNumb = $rec->rowNumb;
    	$row->address = $row->toAdress;
    	$row->ROW_ATTR['class'] = "state-{$rec->state}";
    	$row->docId = $this->getLink($rec->id, 0);
    	
    	return $row;
    }
    
    
    /**
     * Подготовка на показване като детайл в транспортните линии
     */
    public function prepareTransfers($data)
    {
    	$masterRec = $data->masterData->rec;
    	$query = $this->getQuery();
    	$query->where("#lineId = {$masterRec->id}");
    	$query->where("#state != 'rejected'");
    	$query->orderBy("#createdOn", 'DESC');
    	
    	$i = 1;
    	while($dRec = $query->fetch()){
    		$dRec->rowNumb = $i;
    		$data->transfers[$dRec->id] = $this->prepareLineRows($dRec);
    		$i++;
    		
    		$data->masterData->weight += $dRec->weight;
    		$data->masterData->volume += $dRec->volume;
    		$data->masterData->palletCount += $dRec->palletCountInput;
    	}
    }
    
    
    /**
     * Подготовка на показване като детайл в транспортните линии
     */
    public function renderTransfers($data)
    {
    	if(count($data->transfers)){
    		$table = cls::get('core_TableView');
    		$fields = "rowNumb=№,docId=Документ,fromStore=Склад->Изходящ,toStore=Склад->Входящ,weight=Тегло,volume=Обем,palletCountInput=Палети,address=@Адрес";
    		 
    		return $table->get($data->transfers, $fields);
    	}
    }
    
    
	/**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
        return tr("|Междускладов трансфер|* №") . $rec->id;
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
    	// Споделяме текущия потребител със нишката на заданието
    	$cu = core_Users::getCurrent();
    	doc_ThreadUsers::addShared($rec->threadId, $rec->containerId, $cu);
    }
    
    
    /**
     * Списък с артикули върху, на които може да им се коригират стойностите
     * @see acc_AllowArticlesCostCorrectionDocsIntf
     *
     * @param mixed $id               - ид или запис
     * @return array $products        - масив с информация за артикули
     * 			    o productId       - ид на артикул
     * 				o name            - име на артикула
     *  			o quantity        - к-во
     *   			o amount          - сума на артикула
     *   			o inStores        - масив с ид-то и к-то във всеки склад в който се намира
     *    			o transportWeight - транспортно тегло на артикула
     *     			o transportVolume - транспортен обем на артикула
     */
    function getCorrectableProducts($id)
    {
    	$products = array();
    	$rec = $this->fetchRec($id);
    	$query = store_TransfersDetails::getQuery();
    	$query->where("#transferId = {$rec->id}");
    	while($dRec = $query->fetch()){
    		if(!array_key_exists($dRec->newProductId, $products)){
    			$products[$dRec->newProductId] = (object)array('productId'    => $dRec->newProductId,
    					'quantity'        => 0,
    					'name'            => cat_Products::getTitleById($dRec->newProductId, FALSE),
    					'amount'          => NULL,
    					'transportWeight' => $dRec->weight,
    					'transportVolume' => $dRec->volume,
    					'inStores'        => array($rec->toStore => 0),
    			);
    		}
    		
    		$products[$dRec->newProductId]->quantity += $dRec->quantity;
    		$products[$dRec->newProductId]->inStores[$rec->toStore] += $dRec->quantity;
    	}
    
    	return $products;
    }
    
    
    /**
     * Информация за логистичните данни
     *
     * @param mixed $rec   - ид или запис на документ
     * @return array $data - логистичните данни
     *
     *		string(2)     ['fromCountry']  - международното име на английски на държавата за натоварване
     * 		string|NULL   ['fromPCode']    - пощенски код на мястото за натоварване
     * 		string|NULL   ['fromPlace']    - град за натоварване
     * 		string|NULL   ['fromAddress']  - адрес за натоварване
     *  	string|NULL   ['fromCompany']  - фирма
     *   	string|NULL   ['fromPerson']   - лице
     * 		datetime|NULL ['loadingTime']  - дата на натоварване
     * 		string(2)     ['toCountry']    - международното име на английски на държавата за разтоварване
     * 		string|NULL   ['toPCode']      - пощенски код на мястото за разтоварване
     * 		string|NULL   ['toPlace']      - град за разтоварване
     *  	string|NULL   ['toAddress']    - адрес за разтоварване
     *   	string|NULL   ['toCompany']    - фирма
     *   	string|NULL   ['toPerson']     - лице
     * 		datetime|NULL ['deliveryTime'] - дата на разтоварване
     * 		text|NULL 	  ['conditions']   - други условия
     * 		varchar|NULL  ['ourReff']      - наш реф
     */
    function getLogisticData($rec)
    {
    	$rec = $this->fetchRec($rec);
    	$res = array();
    	$res['ourReff'] = "#" . $this->getHandle($rec);
    	$res['loadingTime'] = (!empty($rec->deliveryTime)) ? $rec->deliveryTime : $rec->valior . " " . bgerp_Setup::get('START_OF_WORKING_DAY');
    	
    	foreach (array('from', 'to')  as $part){
    		if($locationId = store_Stores::fetchField($rec->{"{$part}Store"}, 'locationId')){
    			$location = crm_Locations::fetch($locationId);
    			
    			$res["{$part}Country"]    = drdata_Countries::fetchField($location->countryId, 'commonName');
    			$res["{$part}PCode"]    = !empty($location->pCode) ? $location->pCode : NULL;
    			$res["{$part}Place"]    = !empty($location->place) ? $location->place : NULL;
    			$res["{$part}Address"]  = !empty($location->address) ? $location->address : NULL;
    			$res["{$part}Person"]   = !empty($location->mol) ? $location->mol : NULL;
    		}
    	}
    	
    	return $res;
    }
}