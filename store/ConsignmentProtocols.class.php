<?php



/**
 * Клас 'store_ConsignmentProtocols'
 *
 * Мениджър на протоколи за отговорно пазене
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov<ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_ConsignmentProtocols extends core_Master
{
	
	
	/**
     * Заглавие
     * 
     * @var string
     */
    public $title = 'Протоколи за отговорно пазене';


    /**
     * Абревиатура
     */
    public $abbr = 'Cpt';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf, store_iface_DocumentIntf, bgerp_DealIntf, acc_TransactionSourceIntf=store_transaction_ConsignmentProtocol';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, store_Wrapper, doc_plg_BusinessDoc,plg_Sorting, acc_plg_Contable, cond_plg_DefaultValues,
                    doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary, plg_Search, bgerp_plg_Blank, doc_plg_HidePrices';

    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,store';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,store';


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
     * Кой може да го види?
     */
    public $canView = 'ceo,store';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo,store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, valior, contragentId=Контрагент, folderId, createdOn, createdBy';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/shipment.png';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'store_ConsignmentProtocolDetailsSend,store_ConsignmentProtocolDetailsReceived' ;
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Протокол за отговорно пазене';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'store/tpl/SingleLayoutConsignmentProtocol.shtml';

   
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.7|Логистика";
    
    
    /**
     * Записи за обновяване
     */
    protected $updated = array();
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'valior,folderId,note';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('valior', 'date', 'caption=Дата, mandatory');
    	$this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
    	$this->FLD('contragentId', 'int', 'input=hidden');
    	
    	$this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)', 'mandatory,caption=Плащане->Валута');
    	$this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=От склад, mandatory');
    
    	$this->FLD('note', 'richtext(bucket=Notes,rows=3)', 'caption=Допълнително->Бележки');
    	$this->FLD('state',
    			'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)',
    			'caption=Статус, input=none'
    	);
    	$this->FLD('snapshot', 'blob(serialize, compress)', 'caption=Данни,input=none');
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
     * След изпълнение на скрипта, обновява записите, които са за ъпдейт
     */
    public static function on_Shutdown($mvc)
    {
    	if(count($mvc->updated)){
    		foreach ($mvc->updated as $id) {
    			$rec = $mvc->fetchRec($id);
    			$mvc->save($rec);
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(isset($fields['-list'])){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    		$row->contragentId = cls::get($rec->contragentClassId)->getHyperlink($rec->contragentId, TRUE);
    	}
    	
    	if(isset($fields['-single'])){
    		store_DocumentMaster::prepareHeaderInfo($row, $rec);
    	}
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
    	$rec = $mvc->fetchRec($rec);
    	
    	if(empty($rec->snapshot)){
    		$rec->snapshot = $mvc->prepareSnapshot($rec, dt::now());
    		$mvc->save($rec, 'snapshot');
    		core_Statuses::newStatus('up');
    	} else {
    		core_Statuses::newStatus('ne');
    	}
    }
    
    
    /**
     * След подготовка на сингъла
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	// Ако няма 'снимка' на моментното състояние, генерираме го в момента
    	if(empty($data->rec->snapshot)){
    		$data->rec->snapshot = $mvc->prepareSnapshot($data->rec, dt::now());
    	}
    }
    
    
    /**
     * След рендиране на еденичния изглед
     */
    public static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	$snapshot = $data->rec->snapshot;
    	
    	$mvcTable = new core_Mvc;
    	$mvcTable->FLD('blQuantity', 'int', 'tdClass=accCell');
    	 
    	$table = cls::get('core_TableView', array('mvc' => $mvcTable));
    	$details = $table->get($snapshot->rows, 'productId=Артикул,blQuantity=К-во');
    	
    	$tpl->replace($details, 'SNAPSHOT');
    	$tpl->replace($snapshot->date, 'SNAPSHOT_DATE');
    }
    
    
    /**
     * Подготвя снапшот на моментното представяне на базата
     */
    private function prepareSnapshot($rec, $date)
    {
    	$rows = array();
    	
    	// Кое е перото на контрагента ?
    	$contragentItem = acc_Items::fetchItem($rec->contragentClassId, $rec->contragentId);
    	
    	// За да покажем моментното състояние на сметката на контрагента, взимаме баланса до края на текущия ден
    	$to = dt::addDays(1, $date);
    	$Balance = new acc_ActiveShortBalance(array('from' => $to,
    												'to' => $to,
									    			'accs' => '333',
									    			'item1' => $contragentItem->id,
									    			'strict' => TRUE,
									    			'cacheBalance' => FALSE));
    	
    	// Изчлисляваме в момента, какъв би бил крания баланс по сметката в края на деня
    	$Balance = $Balance->getBalanceBefore('333');
    	$Double = cls::get('type_Double');
    	$Double->params['smartRound'] = TRUE;
    	
    	$accId = acc_Accounts::getRecBySystemId('333')->id;
    	
    	// Подготвяме записите за показване
    	foreach ($Balance as $b){
    		if($b['accountId'] != $accId) continue;
    		
    		$row = new stdClass;
    		$row->productId = acc_Items::getVerbal($b['ent2Id'], 'titleLink');
    		$row->blQuantity = $Double->toVerbal($b['blQuantity']);
    		if($b['baseQuantity'] < 0){
    			$row->blQuantity = "<span class='red'>{$row->blQuantity}</span>";
    		}
    		
    		$rows[] = $row;
    	}
        
    	$Datetime = cls::get('type_DateTime', array('params' => array('format' => 'smartTime')));
    	
    	// Връщаме подготвените записи, и датата към която са подготвени
        return (object)array('rows' => $rows, 'date' => $Datetime->toVerbal($date));
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec  = &$form->rec;
    
    	$form->setDefault('valior', dt::now());
    	$form->setDefault('storeId', store_Stores::getCurrent('id', FALSE));
    	$rec->contragentClassId = doc_Folders::fetchCoverClassId($rec->folderId);
    	$rec->contragentId = doc_Folders::fetchCoverId($rec->folderId);
    	$form->setDefault('currencyId', acc_Periods::getBaseCurrencyCode());
    	
    	if(isset($rec->id)){
    		if(store_ConsignmentProtocolDetailsSend::fetchField("#protocolId = {$rec->id}")){
    			$form->setReadOnly('currencyId');
    		}
    	}
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	return tr("|Протокол за отговорно пазене|* №") . $rec->id;
    }
    
    
    /**
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
     * В кои корици може да се вкарва документа
     *
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('doc_ContragentDataIntf');
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
    	$threadRec = doc_Threads::fetch($threadId);
    	$coverClass = doc_Folders::fetchCoverClassName($threadRec->folderId);
    	 
    	return cls::haveInterface('doc_ContragentDataIntf', $coverClass);
    }
}