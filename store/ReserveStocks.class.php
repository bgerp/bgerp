<?php



/**
 * Клас 'store_ReserveStocks' - Документ за резервиране на складови наличности
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @deprecated
 */
class store_ReserveStocks extends core_Master
{
	
	
    /**
     * Заглавие
     */
    public $title = 'Резервиране на складови наличности';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Rss';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, store_iface_DocumentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_plg_StoreFilter, store_Wrapper, plg_Sorting, plg_Printing, acc_plg_DocumentSummary, doc_DocumentPlg, plg_Search';

    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'storeId,note';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'no_one';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo, store, planning, sales';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title=Документ, storeId, originId=От, activatedOn, activatedBy, modifiedOn,modifiedBy';


    /**
     * Детайла, на модела
     */
    public $details = 'store_ReserveStockDetails';
    

    /**
     * Кой е главния детайл
     *
     * @var string - име на клас
     */
    public $mainDetail = 'store_ReserveStockDetails';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Резервиране на складова наличност';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'store/tpl/SingleLayoutReserveStock.shtml';

   
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'activatedOn';
    
    
    /**
     * Файл за единичния изглед в мобилен
     */
    //public $singleLayoutFileNarrow = 'store/tpl/SingleLayoutTransfersNarrow.shtml';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';


	/**
	 * Икона на единичния изглед
	 */
	public $singleIcon = 'img/16/lock.png';


	/**
	 * Полета за филтър по склад
	 */
	public $filterStoreFields = 'storeId';
	
	
	/**
	 * Може ли да се редактират активирани документи
	 */
	public $canEditActivated = TRUE;
	
	
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,mandatory');
        $this->FLD('note', 'richtext(bucket=Notes,rows=3)', 'caption=Допълнително->Бележки');
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
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(get_called_class());
    	 
    	return tr("|{$self->singleTitle}|* №") . $rec->id;
    }
    
    
    /**
     * Преди запис
     */
    protected static function on_BeforeSave($mvc, &$id, $rec, $fields = NULL, $mode = NULL)
    {
    	if($rec->state == 'draft'){
    		$rec->state = 'active';
    		$rec->activatedOn = dt::now();
    		$rec->activatedBy = core_Users::getCurrent();
    	}
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
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
    	// Склада е този от ориджина
    	$origin = doc_Containers::getDocument($rec->originId);
    	if($origin->getInstance()->getField('shipmentStoreId', FALSE)){
    		$form->setDefault('storeId', $origin->fetchField('shipmentStoreId'));
    	}
    	
    	// Ако има детайли, склада не може да се променя
    	if(isset($rec->id)){
    		if(store_ReserveStockDetails::fetchField("#reserveId = {$rec->id}")){
    			$form->setreadOnly('storeId');
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->title = $mvc->getLink($rec->id, 0);
    	$row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
    	$row->originId = doc_Containers::getDocument($rec->originId)->getLink(0);
    	
    	if(isset($fields['-single'])){
    		$row->activatedOn = dt::mysql2verbal($rec->activatedOn, 'd.m.Y');
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'add' && isset($rec)){
    		if(!isset($rec->originId)){
    			$requiredRoles = 'no_one';
    		} else {
    			$document = doc_Containers::getDocument($rec->originId);
    			if(!$document->haveInterface('store_iface_ReserveStockSourceIntf')){
    				$requiredRoles = 'no_one';
    			} else {
    				$state = $document->fetchField('state');
    				if(!in_array($state, array('active', 'wakeup', 'stopped'))){
    					$requiredRoles = 'no_one';
    				}
    			}
    		}
    	}
    	
    	if(($action == 'add' || $action == 'restore') && isset($rec->originId) && $requiredRoles != 'no_one'){
    		$document = doc_Containers::getDocument($rec->originId);
    		
    		// Не може да се възстановява/добавя ако има друг активен документ от същия тип в нишката
    		if(self::fetchField("#threadId = {$document->fetchField('threadId')} AND #state = 'active'")){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Връща ид на всички нишки, в които има активни РнСН
     * 
     * @return array $res
     */
    public static function getThreads()
    {
    	$query = store_ReserveStocks::getQuery();
    	$query->where("#state = 'active'");
    	$query->show('threadId');
    	$res = arr::extractValuesFromArray($query->fetchAll(), 'threadId');
    	
    	return $res;
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
    	store_ReserveStockDetails::saveDefaultDetails($rec);
    }
}