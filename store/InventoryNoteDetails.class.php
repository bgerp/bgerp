<?php


/**
 * Клас 'store_InventoryNoteDetails'
 *
 * Детайли на мениджър на детайлите на протоколите за инвентаризация (@see store_InventoryNotes)
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_InventoryNoteDetails extends doc_Detail
{
	
	
    /**
     * Заглавие
     */
    public $title = 'Детайли на протокола за инвентаризация';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'опис на артикул';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'noteId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, store_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, store';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, store';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, store';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, store';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
        
    /**
     * Активен таб
     */
    //public $currentTab = 'Трансфери';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('noteId', 'key(mvc=store_InventoryNotes)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Продукт,input=none,mandatory,silent,refreshForm');
        $this->FLD('packagingId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,mandatory,smartCenter,input=hidden,tdClass=small-field nowrap');
        $this->FLD('quantity', 'double(Min=0)', 'caption=Количество,input=none');
        $this->FLD('quantityInPack', 'double(decimals=2)', 'input=none,column=none');
        $this->FNC('packQuantity', 'double(decimals=2)', 'caption=Количество,input,mandatory');
    
        $this->setDbUnique('noteId,productId,packagingId');
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
    	if (empty($rec->quantity) || empty($rec->quantityInPack)) {
    		return;
    	}
    
    	$rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }
    
    
    /**
     * Изпълнява се след опаковане на детайла от мениджъра
     *
     * @param stdClass $data
     */
    function renderDetail($data)
    {
    	return new core_ET("");
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->packagingId = cat_UoM::getShortName($rec->packagingId);
    }
    
    
    public function act_Insert()
    {
    	$this->requireRightFor('add');
    	$noteId = Request::get('noteId', 'key(mvc=store_InventoryNotes)');
    	$productId = Request::get('productId', 'key(mvc=cat_Products)');
    	$rec = (object)array('noteId' => $noteId, 'productId' => $productId);
    	$this->requireRightFor('add', $rec);
    	
    	$form = $this->getInsertForm($rec);
    	$form->input();
    	
    	if($form->isSubmitted()){
    		$rec = $form->rec;
    		$arr = (array)$rec;
    		$quantity = NULL;
    		foreach ($arr as $key => $value){
    			$recToClone = (object)array('noteId' => $rec->noteId, 'productId' => $rec->productId);
    			
    			if(strpos($key, 'pack') !== FALSE){
    				$packagingId = str_replace('pack', '', $key);
    				
    				if(isset($value)){
    					$dRec = clone $recToClone;
    					$dRec->packagingId = $packagingId;
    					$dRec->quantityInPack = ($rec->{"quantityInPack{$packagingId}"}) ? $rec->{"quantityInPack{$packagingId}"} : 1;
    					$dRec->quantity = $value * $dRec->quantityInPack;
    					
    					$this->isUnique($dRec, $fields, $exRec);
    					if($exRec){
    						$dRec->id = $exRec->id;
    					}
    					
    					$quantity += $dRec->quantity;
    					store_InventoryNoteDetails::save($dRec);
    				} else {
    					store_InventoryNoteDetails::delete("#noteId = {$rec->noteId} AND #productId = {$rec->productId} AND #packagingId = {$packagingId}");
    				}
    			}
    		}
    		
    		$summeryId = store_InventoryNoteSummary::force($rec->noteId, $productId);
    		
    		$sRec = (object)array('id' => $summeryId, 'quantity' => $quantity);
			store_InventoryNoteSummary::save($sRec);
 			
    		redirect(array('store_InventoryNotes', 'single', $rec->noteId), FALSE, 'Количествата са променени успешно');
    	}
    	
    	$form->toolbar->addSbBtn('Запис', 'save', 'id=save, ef_icon = img/16/disk.png', 'title=Запис на документа');
    	$form->toolbar->addBtn('Отказ', getRetUrl(),  'id=cancel, ef_icon = img/16/close16.png', 'title=Прекратяване на действията');
    	
    	// Получаваме изгледа на формата
        $tpl = $form->renderHtml();
        
        // Опаковаме изгледа
        $tpl = $this->renderWrapping($tpl);
        
        return $tpl;
    }
    
    private function getInsertForm()
    {
    	$form = cls::get('core_Form');
    	$form->FLD('noteId', 'key(mvc=store_InventoryNotes)', 'mandatory,silent,input=hidden');
    	$form->FLD('productId', 'key(mvc=cat_Products, select=name)', 'mandatory,silent,caption=Артикул,removeAndRefreshForm');
    	
    	$form->input(NULL, 'silent');
    	
    	$rec = &$form->rec;
    	if(Request::get('edit', 'int')){
    		$form->title = 'Промяна на установените количества';
    		$form->setReadOnly('productId');
    	} else {
    		$form->title = 'Добавяне на нов артикул за опис';
    		$products = cat_Products::getByProperty('canStore');
			$productsInSummary = store_InventoryNoteSummary::getProductsInSummary($rec->noteId);
			$notUsedProducts = array_diff_key($products, $productsInSummary);
			
			$form->setOptions('productId', array('' => '') + $notUsedProducts);
    	}
    	
    	if(isset($rec->productId)){
    		$refreshForm = array();
    		$packs = cat_Products::getPacks($rec->productId);
    		
    		foreach ($packs as $packId => $value){
    			$form->FLD("pack{$packId}", 'double');
    			
    			$exRec = store_InventoryNoteDetails::fetch("#noteId = {$rec->noteId} AND #productId = {$rec->productId} AND #packagingId = {$packId}");
    			if($exRec){
    				$quantityInPack = $exRec->quantityInPack;
    				$form->setDefault("pack{$packId}", core_Math::roundNumber($exRec->quantity / $quantityInPack));
    			} else {
    				$pRec = cat_products_Packagings::getPack($rec->productId, $packId);
    				$quantityInPack = ($pRec) ? $pRec->quantity : 1;
    			}
    			
    			deals_Helper::getPackInfo($value, $rec->productId, $packId, $quantityInPack);
    			$value = strip_tags($value);
    			
    			$form->setField("pack{$packId}", "caption=|*{$value}");
    			
    			$form->FLD("quantityInPack{$packId}", 'double', "input=hidden");
    			$form->setDefault("quantityInPack{$packId}", $quantityInPack);
    			$refreshForm[] = "pack{$packId}";
    			$refreshForm[] = "quantityInPack{$packId}";
    		}
    		
    		$refreshForm = implode('|', $refreshForm);
    		$form->setField('productId', "removeAndRefreshForm={$refreshForm}");
    	}
    	
    	return $form;
    }
}