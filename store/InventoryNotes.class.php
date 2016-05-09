<?php
/**
 * Клас 'store_InventoryNotes'
 *
 * Мениджър за документ за инвентаризация на склад
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov<ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_InventoryNotes extends core_Master
{
    
    
    /**
     * Заглавие
     * 
     * @var string
     */
    public $title = 'Протоколи за инвентаризация';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Ivn';
    
    
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
     * Заглавие в единствено число
     */
    public $singleTitle = 'Протокол за инвентаризация';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/shipment.png';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.8|Логистика";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_Wrapper,doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary, plg_Search';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'store_InventoryNoteSummary,store_InventoryNoteDetails';
    
    
    /**
     * Главен детайл на модела
     */
    public $mainDetail = 'store_InventoryNoteSummary';
   
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'store/tpl/SingleLayoutInventoryNote.shtml';
    
    
    /**
     * Да се забрани ли кеширането на документа
     */
    public $preventCache = TRUE;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('valior', 'date', 'caption=Вальор, mandatory');
    	$this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад, mandatory');
    	$this->FLD('groups', 'keylist(mvc=cat_Groups,select=name)', 'caption=Маркери');
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
    	$form->setDefault('valior', dt::today());
    	
    	$form->setDefault('storeId', doc_Folders::fetchCoverId($form->rec->folderId));
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
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
    	core_App::setTimeLimit(300);
    	$products = $mvc->getProductsFromBalance($rec);
    	foreach ($products as $pRec){
    		$dRec = (object)array('noteId'     => $rec->id,
    							  'groups'     => $pRec->groups,
    							  'productId'  => $pRec->productId,
    							  'blQuantity' => $pRec->quantity);
    	
    		store_InventoryNoteSummary::save($dRec);
    	}
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	if($mvc->haveRightFor('single', $data->rec->id)){
    		$url = array($mvc, 'single', $data->rec->id);
        	$url['Printing'] = 'yes';
        	$url['Blank'] = 'yes';
        	
    		$data->toolbar->addBtn('Бланка', $url, 'ef_icon = img/16/star_2.png,title=Принтиране на бланка,target=_blank');
    	}
    }
    
    
    /**
     * Преди подготовка на сингъла
     */
    public static function on_BeforePrepareSingle(core_Mvc $mvc, &$res, $data)
    {
    	if(Request::get('Blank', 'varchar')){
    		Mode::set('blank');
    	}
    }
    
    
    /**
     * След подготовка на сингъла
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	$rec = &$data->rec;
    	
    	$ownCompanyData = crm_Companies::fetchOwnCompany();
    	$data->row->MyCompany = cls::get('type_Varchar')->toVerbal($ownCompanyData->company);
    	$data->row->MyCompany = transliterate(tr($row->MyCompany));
    	$data->row->MyAddress = cls::get('crm_Companies')->getFullAdress($ownCompanyData->companyId, TRUE)->getContent();
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
    	if(!Mode::is('printing') && !Mode::is('text', 'xhtml') && !Mode::is('pdf')){
    		$tpl->push('store/js/InventoryNotes.js', 'JS');
    		jquery_Jquery::run($tpl, "noteActions();");
    	}
    }
    
    
    /**
     * Масив с артикулите срещани в счетоводството
     * 
     * @param stClass $rec
     * @return array
     * 		o productId - ид на артикул
     * 	    o groups    - списък с маркери
     *  	o quantity  - к-во
     */
    private function getProductsFromBalance($rec)
    {
    	$res = array();
    	
    	// Търсим артикулите от два месеца назад
    	$from = dt::addMonths(-2, $rec->valior);
    	$from = dt::verbal2mysql($from, FALSE);
    	$to = dt::addDays(-1, $rec->valior);
    	$to = dt::verbal2mysql($to, FALSE);
    	
    	// Изчисляваме баланс за подадения период за склада
    	$storeItemId = acc_items::fetchItem('store_Stores', $rec->storeId)->id;
    	$Balance = new acc_ActiveShortBalance(array('from' => $from, 'to' => $to, 'accs' => '321', 'cacheBalance' => FALSE, 'item1' => $storeItemId));
    	$bRecs = $Balance->getBalance('321');
    	
    	$productPositionId = acc_Lists::getPosition('321', 'cat_ProductAccRegIntf');
    	
    	// Подготвяме записите в нормален вид
    	if(is_array($bRecs)){
    		foreach ($bRecs as $bRec){
    			$productId = acc_Items::fetchField($bRec->{"ent{$productPositionId}Id"}, 'objectId');
    			$res[$productId] = (object)array("productId" => $productId,
    											 "groups"    => cat_Products::fetchField($productId, 'groups'),
    								   			 "quantity"  => $bRec->blQuantity,);
    		}
    	}
    	
    	// Връщаме намерените артикули
    	return $res;
    }
}
