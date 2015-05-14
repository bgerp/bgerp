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
 * @copyright 2006 - 2015 Experta OOD
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
     * Абревиатура
     */
    public $abbr = 'St';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, store_iface_DocumentIntf, acc_TransactionSourceIntf=store_transaction_Transfer';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, store_Wrapper, plg_Sorting, plg_Printing, acc_plg_Contable, acc_plg_DocumentSummary,
                    doc_DocumentPlg, trans_plg_LinesPlugin, doc_plg_BusinessDoc, plg_Search';

    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,store';
    
    
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
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo,store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт, valior, title=Документ, fromStore, toStore, volume, weight, folderId, createdOn, createdBy';


    /**
     * Детайла, на модела
     */
    public $details = 'store_TransfersDetails';
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Междускладов трансфер';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'store/tpl/SingleLayoutTransfers.shtml';

   
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.5|Логистика";


    /**
     * Опашка от записи за записване в on_Shutdown
     */
    protected $updated = array();
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
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
        $this->FLD('deliveryTime', 'datetime', 'caption=Срок до');
        $this->FLD('lineId', 'key(mvc=trans_Lines,select=title,allowEmpty)', 'caption=Транспорт');
        
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
     * Обновява информацията на документа
     * @param int $id - ид на документа
     */
    public function updateMaster($id)
    {
    	$rec = $this->fetch($id);
    	$dQuery = $this->store_TransfersDetails->getQuery();
    	$dQuery->where("#transferId = {$id}");
    	$measures = $this->getMeasures($dQuery->fetchAll());
    	
    	$rec->weight = $measures->weight;
    	$rec->volume = $measures->volume;
    	
    	$this->save($rec);
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
    		
	    	$fromStoreLocation = store_Stores::fetchField($rec->fromStore, 'locationId');
	    	if($fromStoreLocation){
	    		$row->fromAdress = crm_Locations::getAddress($fromStoreLocation);
	    	}
	    	
	    	$toStoreLocation = store_Stores::fetchField($rec->toStore, 'locationId');
    		if($toStoreLocation){
	    		$row->toAdress = crm_Locations::getAddress($toStoreLocation);
	    	}
	    	
	    	$row->weight = ($row->weightInput) ? $row->weightInput : $row->weight;
	    	$row->volume = ($row->volumeInput) ? $row->volumeInput : $row->volume;
    	}
    	
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    		$row->title = $mvc->getLink($rec->id, 0);
    		
    		$attr = array();
    		foreach (array('fromStore', 'toStore') as $storeFld){
	    		if(store_Stores::haveRightFor('single', $rec->{$storeFld})){
	    			$attr['class'] = "linkWithIcon";
	    			$attr['style'] = "background-image:url('" . sbf('img/16/home-icon.png', "") . "');";
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
     * @param int $id - ид на СР
     * @return param $res - масив с използваните документи
     * 					['class'] - инстанция на документа
     * 					['id'] - ид на документа
     */
    public function getUsedDocs_($id)
    {
    	$res = array();
    	$dQuery = $this->store_TransfersDetails->getQuery();
    	$dQuery->EXT('state', 'store_Transfers', 'externalKey=transferId');
    	$dQuery->where("#transferId = '{$id}'");
    	while($dRec = $dQuery->fetch()){
    		$sProd = store_Products::fetch($dRec->productId);
    		$ProductMan = cls::get($sProd->classId);
    		if(cls::haveInterface('doc_DocumentIntf', $ProductMan)){
    			$res[] = (object)array('class' => $ProductMan, 'id' => $sProd->productId);
    		}
    	}
    	return $res;
    }
    
    
	/**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('store_iface_TransferFolderCoverIntf');
    }
    
    
    /**
     * Помощен метод за показване на документа в транспортните линии
     * @param stdClass $rec - запис на документа
     * @param stdClass $row - вербалния запис
     */
    private function prepareLineRows($rec)
    {
    	$row = $this->recToVerbal($rec, 'toAdress,fromStore,toStore,weight,volume,weightInput,volumeInput,palletCountInput,-single');
    	$row->weight = ($row->weightInput) ? $row->weightInput : $row->weight;
    	$row->volume = ($row->volumeInput) ? $row->volumeInput : $row->volume;
    	
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
}