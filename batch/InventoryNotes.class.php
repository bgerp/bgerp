<?php



/**
 * Движения на партиди
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class batch_InventoryNotes extends core_Master {
    
	
    /**
     * Заглавие
     */
    public $title = 'Инвентаризация на партидност';
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools2, batch_Wrapper,batch_plg_DocumentMovement, doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary, doc_ActivatePlg, plg_Search, bgerp_plg_Blank';
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf,batch_MovementSourceIntf=batch_movements_InventoryNote';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.9|Логистика";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior,title=Документ,storeId,folderId,createdOn,createdBy,modifiedOn,modifiedBy';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Инвентаризация на партидност";
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'batch,ceo,storeMaster';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'batch,ceo,storeMaster';
    
    
    /**
     * Кой може да активира?
     */
    public $canActivate = 'batch,ceo,storeMaster';
    
    
    /**
     * Какви детайли има този мастер
     */
    public $details = 'batch_InventoryNoteDetails';
    
    
    /**
     * Кой е основния детайл
     */
    public $mainDetail = 'batch_InventoryNoteDetails';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Bin";
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'batch/tpl/SingleLayoutInventoryNotes.shtml';
    
    
    /**
     * Поле за вальора
     */
    public $valiorFld = 'valior';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'storeId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('valior', 'date', 'caption=Към дата, mandatory');
    	$this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад, mandatory');
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
    	if($rec->id){
    		$detailsKeywords = '';
    
    		// Добавяме данни от детайла към ключовите думи на документа
    		$dQuery = batch_InventoryNoteDetails::getQuery();
    		$dQuery->where("#noteId = '{$rec->id}'");
    		while($dRec = $dQuery->fetch()){
    			$detailsKeywords .= " " . plg_Search::normalizeText(cat_Products::getTitleById($dRec->productId));
    			
    			if(!empty($dRec->batchIn)){
    				$detailsKeywords .= " " . plg_Search::normalizeText($dRec->batchIn);
    			}
    			
    			if(!empty($dRec->batchOut)){
    				$detailsKeywords .= " " . plg_Search::normalizeText($dRec->batchOut);
    			}
    		}
    
    		$res = " " . $res . " " . $detailsKeywords;
    	}
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
    	$form->setDefault('valior', dt::today());
    	 
    	$form->setDefault('storeId', doc_Folders::fetchCoverId($form->rec->folderId));
    	$form->setReadOnly('storeId');
    }
    
    
    /**
     * Можели документа да се добави в посочената папка
     *
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
    	$folderClass = doc_Folders::fetchCoverClassName($folderId);
    	 
    	return ($folderClass == 'store_Stores') ? TRUE : FALSE;
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
    	$threadRec = doc_Threads::fetch($threadId);
    	 
    	return static::canAddToFolder($threadRec->folderId);
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а на формата за редактиране/добавяне
     */
    protected static function on_AfterPrepareEditToolbar($mvc, $data)
    {
    	$data->form->toolbar->removeBtn('activate');
    }
    
    
    /**
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
    	expect($rec = $this->fetch($id));
    
    	$row = (object)array(
    			'title'    => self::getRecTitle($rec),
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
    
    	return tr($self->singleTitle) . " №{$rec->id}";
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
    	$row->title = $mvc->getLink($rec->id, 0);
    }
    
    
    /**
     * След подготовка на сингъла
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	$headerInfo = deals_Helper::getDocumentHeaderInfo(NULL, NULL);
    	$data->row = (object)((array)$data->row + (array)$headerInfo);
    }
    
    
    /**
     *
     * Функция, която се извиква преди активирането на документа
     *
     * @param unknown_type $mvc
     * @param unknown_type $rec
     */
    public static function on_BeforeActivation($mvc, $rec)
    {
    	$r = (is_numeric($rec)) ? $mvc->fetch($rec) : $mvc->fetch($rec->id);
    	$dQuery = batch_InventoryNoteDetails::getQuery();
    	$dQuery->where("#noteId = {$rec->id}");
    	$dQuery->where("#batchOut IS NOT NULL OR #batchOut != ''");
    	
    	$error = FALSE;
    	while($dRec = $dQuery->fetch()){
    		$quantity = batch_Items::getQuantity($dRec->productId, $dRec->batchOut, $r->storeId);
    		if($quantity < $dRec->quantity){
    			$error = TRUE;
    			break;
    		}
    	}
    	
    	if($error === TRUE){
    		core_Statuses::newStatus('Не може да се активира докато има редове, чието количество е над наличното', 'error');
    		return FALSE;
    	}
    }
}