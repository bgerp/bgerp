<?php



/**
 * Клас 'trans_Lines'
 *
 * Документ за Транспортни линии
 *
 *
 * @category  bgerp
 * @package   trans
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
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
    public $loadList = 'plg_RowTools2, trans_Wrapper, plg_Sorting, plg_Printing, plg_Clone,
                    doc_DocumentPlg, bgerp_plg_Blank, plg_Search, change_Plugin, doc_ActivatePlg, doc_plg_SelectFolder';

    
    
    /**
     * Кой може да променя активирани записи
     */
    var $canChangerec = 'ceo, trans';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'vehicleId';
    
    
    /**
     * По кои полета ще се търси
     */
    public $searchFields = 'title, vehicleId, forwarderId, forwarderPersonId, id';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, trans';
    
    
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
    var $canWrite = 'ceo, trans';


    /**
     * Детайла, на модела
     */
    public $details = 'Shipments=store_ShipmentOrders,Receipts=store_Receipts,Transfers=store_Transfers,Protocols=store_ConsignmentProtocols';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, handler=Документ, title, start, folderId, createdOn, createdBy';
    

    /**
     * Кои полета да могат да се променят след активацията на документа
     */
    public $changableFields = 'title, repeat, vehicleId, forwarderId, forwarderPersonId';
    
    
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
    public $singleIcon = 'img/16/lorry_go.png';
    
   
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
    	$this->FLD('vehicleId', 'key(mvc=trans_Vehicles,select=name,allowEmpty)', 'caption=Превозвач->Превозно средство');
    	$this->FLD('forwarderId', 'key(mvc=crm_Companies,select=name,group=suppliers,allowEmpty)', 'caption=Превозвач->Транспортна фирма');
    	$this->FLD('forwarderPersonId', 'key(mvc=crm_Persons,select=name,allowEmpty)', 'caption=Превозвач->МОЛ');
    	$this->FLD('description', 'richtext(bucket=Notes,rows=4)', 'caption=Допълнително->Бележки');
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
    		return "{$start}/{$titleArr[1]}";
    	} else {
    		return "{$start}/{$rec->title}";
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
    	
    	$changeUrl = array($mvc, 'changeState', $data->rec->id);
    	if($data->rec->state == 'active'){
    		if(self::getDocumentsCnt($data->rec->id, 'draft', 1)){
    			$error = ',error=Линията не може да бъде затворена докато има чернови документи към нея';
    		} else {
    			$warning= ',warning=Наистина ли искате да затворите линията?';
    		}
    		
    		$data->toolbar->addBtn('Затваряне', $changeUrl, "ef_icon=img/16/lock.png{$error}{$warning},title=Затваряне на линията");
    	}
    	
    	if($data->rec->state == 'closed' && $data->rec->start >= dt::today()){
    		$data->toolbar->addBtn('Активиране', $changeUrl, 'ef_icon=img/16/lock_unlock.png,warning=Искате ли да активирате линията?,title=Отваряне на линията');
    	}
    }
    
    
    /**
     * Екшън за отваряне/затваряне на линия
     */
    function act_ChangeState()
    {
    	$this->requireRightFor('write');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	expect($rec->state == 'active' || $rec->state == 'closed');
    	expect($rec->start >= dt::today() || $rec->state == 'active');
    	
    	$rec->state = ($rec->state == 'active') ? 'closed' : 'active';
    	
    	// Освобождаваме всички чернови документи в които е избрана линията която затваряме
    	if($rec->state == 'closed'){
    	    expect(!self::getDocumentsCnt($id, 'draft', 1));
    	}
    	
    	$this->save($rec);
    	$msg = ($rec->state == 'active') ? '|Линията е отворена успешно' : '|Линията е затворена успешно';
    	
    	
    	return new Redirect(array($this, 'single', $rec->id), $msg);
    }
    
    
	/**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		$rec = &$form->rec;
	    	
	    	$rec->isRepeated = 'no';
	    	if($rec->start < dt::now()){
	    		$form->setError('start', 'Не може да се създаде линия в миналото!');
	    	}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(isset($fields['-single'])){
	    	
	    	$attr = array();
	    	if($rec->vehicleId && trans_Vehicles::haveRightFor('read', $rec->vehicleId)){
	    		$attr['ef_icon'] = 'img/16/tractor.png';
	    	 	$row->vehicleId = ht::createLink($row->vehicleId, array('trans_Vehicles', 'single', $rec->vehicleId), NULL, $attr);
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
       
        $row = (object)array(
            'title'    => $rec->title,
            'authorId' => $rec->createdBy,
            'author'   => $this->getVerbal($rec, 'createdBy'),
            'state'    => $rec->state,
            'recTitle' => $rec->title,
        );
        
        return $row;
    }
    
    
    /**
     * След подготовка на сингъла
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	$weight = ($data->weight) ? $data->weight : 0;
    	$data->row->weight = cls::get('cat_type_Weight')->toVerbal($weight);
    	
    	$volume = ($data->volume) ? $data->volume : 0;
    	$data->row->volume = cls::get('cat_type_Volume')->toVerbal($volume);
    	
    	$count = ($data->palletCount) ? $data->palletCount : 0;
    	$data->row->palletCount = cls::get('type_Int')->toVerbal($count);
    	
    	$amount = ($data->totalAmount) ? $data->totalAmount : 0;
    	$data->row->totalAmount = cls::get('type_Double', array('params' => array('decimals' => 2)))->toVerbal($amount);
    	$bCurrency = acc_Periods::getBaseCurrencyCode();
    	$data->row->totalAmount .= " <span class='cCode'>{$bCurrency}</span>";
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    protected static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
    	$tpl->push('trans/tpl/LineStyles.css', 'CSS');
    }
        
    
    /**
     * Дали има свързано подотчетно лице към линията
     * @param int $id - ид на линията
     * @return boolean
     */
    public static function hasForwarderPersonId($id)
    {
    	expect($rec = static::fetch($id));
    	
    	return isset($rec->forwarderPersonId);
    }
    
    
    /**
     * Създава и затваря нови транспортни линии
     */
    function cron_CreateNewLines()
    {
    	$now = dt::now();
    	$query = $this->getQuery();
    	$query2 = clone $query;
    	$query->where("#state = 'active'");
    	$query->where("#start < '{$now}'");
    	
    	// Затварят се всички отворени линии, с начало в миналото
    	while($rec = $query->fetch()){
    		$rec->state = 'closed';
    		$this->save($rec);
    	}
    	
    	// Намират се затворените линии, които не са повторени и
    	// имат повторение и не са повторени
    	$query2->where("#state = 'closed'");
    	$query2->where("#repeat IS NOT NULL");
    	$query2->where("#isRepeated = 'no'");
    	while($rec = $query2->fetch()){
            
            // Генерира се новата линия
            $newRec = $this->getNewLine($rec);
    		
            if(self::getDocumentsCnt($rec->id, NULL, 1) || doc_Threads::fetchField($rec->threadId, 'allDocCnt') > 1) {
                // Ако в старата линия има документи, създава и записва новата линия
                $sudoUser = core_Users::sudo($rec->createdBy);
                $this->save($newRec);
                core_Users::exitSudo($sudoUser);
                
                // Линията се отбелязва като повторена
                $rec->isRepeated = 'yes';
                $this->save($rec, 'isRepeated');
            } else {
                // Вместо да създава нова линия, отваря старата, ако в нея няма документи
                $rec->start = $newRec->start;
                $rec->state = $newRec->state;
                $this->save($rec);
            }
    	}
    }
    
    
    /**
     * Създава нова линия възоснова на стара
     * @param stdClass $rec - старата линия
     * @return stdClass $newRec - Новата линия
     */
    private function getNewLine($rec)
    {
    	$newRec = new stdClass();
    	$newRec->repeat            = $rec->repeat;
    	$newRec->createdBy         = $rec->createdBy;
    	$newRec->folderId          = $rec->folderId;
    	$newRec->vehicleId 		   = $rec->vehicleId;
    	$newRec->forwarderId 	   = $rec->forwarderId;
    	$newRec->forwarderPersonId = $rec->forwarderPersonId;
    	$newRec->isRepeated 	   = 'no';
    	$newRec->start 			   = dt::addSecs($newRec->repeat, $newRec->start);
    	$newRec->title 			   = $rec->title;
    	$newRec->state 			   = 'active';
    	
    	return $newRec;
    }
    
    
	/**
     * Извиква се след setUp-а на таблицата за модела
     */
    protected static function on_AfterSetupMvc($mvc, &$res)
    {
    	$conf = core_Packs::getConfig('trans');
    	$period = $conf->TRANS_LINES_CRON_INTERVAL / 60;
    	
        $rec = new stdClass();
        $rec->systemId    = "CreateNewLines";
        $rec->description = "Затваряне и създаване на нови транспортни линии";
        $rec->controller  = "trans_Lines";
        $rec->action      = "CreateNewLines";
        $rec->period      = $period;
        $rec->offset 	  = mt_rand(0, $period - 1);
        $rec->delay 	  = 0;
        $rec->timeLimit   = 100;
        $res .= core_Cron::addOnce($rec);
    }

    /**
     * Връща броя на документите в посочената линия
     * Може да се филтрират по #state и да се ограничат до maxDocs
     */
    public static function getDocumentsCnt($lineId, $state = NULL, $maxDocs = 0)
    {
        $res = 0;
    	$me = cls::get(get_called_class());
        $details = arr::make($me->details, TRUE);
        foreach($details as $d) {
            $query = $d::getQuery();
    	    $query->where("#lineId = {$lineId}");
            if($state) {
    	        $query->where("#state = '{$state}'");
            }
            if($maxDoc) {
                $query->limit($maxDocs);
            }
            $res += $query->count();

            if($res >= $maxDocs) break;
        }

        return $res;
    }
}
