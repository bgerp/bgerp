<?php



/**
 * Клас 'trans_Lines' - Документ за Транспортни линии
 *
 *
 * @category  bgerp
 * @package   trans
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class trans_Lines extends core_Master
{
	
    /**
     * Заглавие
     */
    public $title = 'Транспортни линии';


    /**
     * Абревиатура
     */
    public $abbr = 'Tl';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, trans_Wrapper, plg_Printing, plg_Clone,doc_DocumentPlg, bgerp_plg_Blank, plg_Search, change_Plugin, doc_ActivatePlg, doc_plg_SelectFolder, doc_plg_Close';

    
    
    /**
     * Кой може да променя активирани записи
     */
    public $canChangerec = 'ceo, trans';
    
    
    /**
     * По кои полета ще се търси
     */
    public $searchFields = 'title, vehicle, forwarderId, forwarderPersonId';
    
    
    /**
     * Поле за единичен изглед
     */
    public $rowToolsSingleField = 'handler';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, trans';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, trans';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, trans';


    /**
     * Кой има право да пише?
     */
    public $canWrite = 'ceo, trans';


    /**
     * Детайла, на модела
     */
    public $details = 'trans_LineDetails';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, handler=Документ, title, start, folderId, createdOn, createdBy';
    

    /**
     * Кои полета да могат да се променят след активацията на документа
     */
    public $changableFields = 'title, start, repeat, vehicle, forwarderId, forwarderPersonId';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Транспортна линия';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'trans/tpl/SingleLayoutLines.shtml';

    
    /**
     * Файл за единичния изглед в мобилен
     */
    public $singleLayoutFileNarrow = 'trans/tpl/SingleLayoutLinesNarrow.shtml';
    		
    		
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/door_in.png';
    
   
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.5|Логистика";
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'doc_UnsortedFolders';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'title,start,repeat';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('title', 'varchar', 'caption=Заглавие,mandatory');
    	$this->FLD('start', 'datetime', 'caption=Начало, mandatory');
    	$this->FLD('repeat', 'time(suggestions=1 ден|1 седмица|1 месец|2 дена|2 седмици|2 месеца|3 седмици)', 'caption=Повторение');
    	$this->FLD('state', 'enum(draft=Чернова,active=Активен,rejected=Оттеглен,closed=Затворен)', 'caption=Състояние,input=none');
    	$this->FLD('isRepeated', 'enum(yes=Да,no=Не)', 'caption=Генерирано на повторение,input=none');
    	$this->FLD('vehicle', 'varchar', 'caption=Превозвач->Превозно средство,oldFieldName=vehicleId');
    	$this->FLD('forwarderId', 'key(mvc=crm_Companies,select=name,group=suppliers,allowEmpty)', 'caption=Превозвач->Транспортна фирма');
    	$this->FLD('forwarderPersonId', 'key(mvc=crm_Persons,select=name,allowEmpty)', 'caption=Превозвач->МОЛ');
    	$this->FLD('description', 'richtext(bucket=Notes,rows=4)', 'caption=Допълнително->Бележки');
    	$this->FLD('countReady', 'int', 'input=none,notNull,value=0');
    	$this->FLD('countTotal', 'int', 'input=none,notNull,value=0');
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$titleArr = explode('/', $rec->title);
    	$start = dt::mysql2verbal($rec->start, "d.m.Y H:i");
    	$start = str_replace(' 00:00', '', $start);
    	
    	if(count($titleArr) == 2){
    		return "{$start}/{$titleArr[1]} ({$rec->countReady}/{$rec->countTotal})";
    	} else {
    		return "{$start}/{$rec->title} ({$rec->countReady}/{$rec->countTotal})";
    	}
    }
    
    
	/**
     * Малко манипулации след подготвянето на формата за филтриране
     */
	protected static function on_AfterPrepareListFilter($mvc, $data)
	{
		$data->listFilter->showFields = 'search';
		$data->listFilter->view = 'horizontal';
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
		$data->listFilter->input();
		
		$data->query->orderBy("#state");
		$data->query->orderBy("#start", "DESC");
	}


	/**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = $data->rec;
    	
    	if($data->rec->state == 'active'){
    		if(self::haveDraftDocuments($data->rec->id)){
    			if($data->toolbar->hasBtn('btnClose')){
    				$data->toolbar->setError('btnClose', "Линията не може да бъде затворена докато има чернови документи към нея|*!");
    			}
    		}
    	}

    	if($mvc->haveRightFor('single', $data->rec)){
    		$url = array($mvc, 'single', $data->rec->id, 'Printing' => 'yes', 'Width' => 'yes');
    		$data->toolbar->addBtn('Печат (Детайли)', $url, "id=w{$attr['id']},target=_blank,row=2", 'ef_icon = img/16/printer.png,title=Разширен печат на документа');
    	}
    }
    
    
    /**
     * След подготовка на формата
     */
    protected static function on_AfterPrepareEditForm(core_Mvc $mvc, $data)
    {
    	$form = &$data->form;
    	
    	$vehicleOptions = trans_Vehicles::makeArray4Select();
    	if(count($vehicleOptions) && is_array($vehicleOptions)){
    		$form->setSuggestions('vehicle', array('' => '') + arr::make($vehicleOptions, TRUE));
    	}
    }
    
    
	/**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		$rec = &$form->rec;
	    	
	    	$rec->isRepeated = 'no';
	    	if($rec->start < dt::today()){
	    		$form->setError('start', 'Не може да се създаде линия за предишен ден!');
	    	}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(isset($fields['-single'])){
    		if(!empty($rec->vehicle)){
    			if($vehicleRec = trans_Vehicles::fetch(array("#name = '[#1#]'", $rec->vehicle))){
    				$row->vehicle = trans_Vehicles::getHyperlink($vehicleRec->id, TRUE);
    				$row->regNumber = trans_Vehicles::getVerbal($vehicleRec, 'number');
    			}
    		}
	    	
    		if(isset($rec->forwarderPersonId) && !Mode::isReadOnly()){
    			$row->forwarderPersonId = ht::createLink($row->forwarderPersonId, crm_Persons::getSingleUrlArray($rec->forwarderPersonId));
    		}
    		
	    	$ownCompanyData = crm_Companies::fetchOwnCompany();
	    	$row->myCompany = cls::get('type_Varchar')->toVerbal($ownCompanyData->company);
	    	$row->logistic = core_Users::getVerbal($rec->createdBy, 'names');
    	}
    	
    	$row->handler = $mvc->getLink($rec->id, 0);
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
            'recTitle' => $title,
        );
        
        return $row;
    }
    
    
    /**
     * След подготовка на сингъла
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	$row = $data->row;
    	
    	$amount = $weight = $volume = 0;
    	$sumWeight = $sumVolume = TRUE;
    	$transUnits = $calcedUnits = array();
    	
    	$dQuery = trans_LineDetails::getQuery();
    	$dQuery->where("#lineId = {$data->rec->id}");
    	
    	while($dRec = $dQuery->fetch()){
    		$Document = doc_Containers::getDocument($dRec->containerId);
    		$transInfo = $Document->getTransportLineInfo();
    		$amount += $transInfo['baseAmount'];
    		
    		// Сумиране на ЛЕ от документа и подготвените
    		trans_Helper::sumTransUnits($transUnits, $dRec->readyLu);
    		trans_Helper::sumTransUnits($calcedUnits, $dRec->documentLu);
    		
    		// Сумиране на теглото от редовете
    		if($sumWeight === TRUE){
    			if($transInfo['weight']){
    				$weight += $transInfo['weight'];
    			} else {
    				unset($weight);
    				$sumWeight = FALSE;
    			}
    		}
    		
    		// Сумиране на обема от редовете
    		if($sumVolume === TRUE){
    			if($transInfo['volume']){
    				$volume += $transInfo['volume'];
    			} else {
    				unset($volume);
    				$sumVolume = FALSE;
    			}
    		}
    	}
    	
    	// Оцветяване на ЛЕ
    	$logisticUnitsSum = trans_LineDetails::colorTransUnits($calcedUnits, $transUnits);
    	$calcedUnits = empty($logisticUnitsSum->documentLu) ? 'N/A' : $logisticUnitsSum->documentLu;
    	$transUnits = empty($logisticUnitsSum->readyLu) ? 'N/A' : $logisticUnitsSum->readyLu;
    	
    	// Показване на сумарната информация
    	$data->row->logisticUnitsDocument = core_Type::getByName('html')->toVerbal($calcedUnits);
    	$data->row->logisticUnits = core_Type::getByName('html')->toVerbal($transUnits);
    	$data->row->weight = (!empty($weight)) ? cls::get('cat_type_Weight')->toVerbal($weight) : "<span class='quiet'>N/A</span>";
    	$data->row->volume = (!empty($volume)) ? cls::get('cat_type_Volume')->toVerbal($volume) : "<span class='quiet'>N/A</span>";
    	
    	$bCurrency = acc_Periods::getBaseCurrencyCode();
    	$data->row->totalAmount = " <span class='cCode'>{$bCurrency}</span> ";
    	$data->row->totalAmount .= core_Type::getByName('double(decimals=2)')->toVerbal($amount);
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    protected static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
    	$tpl->push('trans/tpl/LineStyles.css', 'CSS');
    }

    
    /**
     * Връща броя на документите в посочената линия
     * Може да се филтрират по #state и да се ограничат до maxDocs
     */
    private static function haveDraftDocuments($id)
    {
        $query = trans_LineDetails::getQuery();
        $query->where("#lineId = {$id}");
        $query->EXT('docState', 'doc_Containers', 'externalName=state,externalKey=containerId');
        $query->show('id');
        
        return $query->count();
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
    	$rec->countReady = $rec->countTotal = 0;
    	
    	// Изчисляване на готовите и не-готовите редове
    	$dQuery = trans_LineDetails::getQuery();
    	$dQuery->where("#lineId = {$rec->id}");
    	$dQuery->show('status');
    	while($dRec = $dQuery->fetch()){
    		$rec->countTotal++;
    		if($dRec->status == 'ready') {
    			$rec->countReady++;
    		}
    	}
    	
    	// Запис на изчислените полета
    	$rec->modifiedOn = dt::now();
    	$rec->modifiedBy = core_Users::getCurrent();
    	$this->save_($rec, 'countTotal,countReady,modifiedOn,modifiedBy');
    	
    	// Ако има не-готови линии, нишката се отваря
    	$Threads = cls::get('doc_Threads');
    	$threadState = ($rec->countReady < $rec->countTotal) ? 'opened' : 'closed';
    	$threadRec = doc_Threads::fetch($rec->threadId, 'state');
    	$threadRec->state = $threadState;
    	$Threads->save($threadRec, 'state');
    	$Threads->updateThread($threadRec->id);
    }
    
    
    /**
     * Връща всички активни линии с подходящи заглавие
     * 
     * @return array $linesArr - масив с опции
     */
    public static function getActiveLines()
    {
    	$linesArr = array();
    	$query = self::getQuery();
    	$query->where("#state = 'active'");
    	
    	$recs = $query->fetchAll();
    	array_walk($recs, function($rec) use (&$linesArr) {$linesArr[$rec->id] = self::getRecTitle($rec, FALSE);});
    	
    	return $linesArr;
    }
}
