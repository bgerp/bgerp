<?php



/**
 * Клас 'store_ReserveStocks' - Документ за резервиране на складови наличности
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
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title=Документ, storeId, originId,activatedOn, createdBy,modifiedOn,modifiedBy';


    /**
     * Детайла, на модела
     */
    public $details = 'store_ReserveStockDetails';
    

    /**
     * Кой е главния детайл
     *
     * @var string - име на клас
     */
    public $mainDetail = 'store_TransfersDetails';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Резервиране на складова наличност';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'store/tpl/SingleLayoutReserveStock.shtml';

   
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
	public $singleIcon = 'img/16/transfers.png';


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
    	$me = cls::get(get_called_class());
    	
    	return tr("{$me->singleTitle}") . " №" . $rec->id;
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
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
    	$origin = doc_Containers::getDocument($rec->originId);
    	if($origin->getInstance()->getField('shipmentStoreId', FALSE)){
    		$form->setDefault('storeId', $origin->fetchField('shipmentStoreId'));
    	}
    	
    	if(isset($rec->id)){
    		if(store_ReserveStockDetails::fetchField("#reserveId = {$rec->id}")){
    			$form->setreadOnly('storeId');
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
    	$row->originId = doc_Containers::getDocument($rec->originId)->getLink(0);
    	
    	$row->activatedOn = dt::mysql2verbal($rec->activatedOn, 'd.m.Y');
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
}