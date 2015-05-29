<?php


/**
 * Клас 'planning_DirectProductNoteDetails'
 *
 * Детайли на мениджър на детайлите на протокола за бързо производство
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_DirectProductNoteDetails extends deals_ManifactureDetail
{
    
	
	/**
     * Заглавие
     */
    public $title = 'Детайли на протокола за бързо производство';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Продукт';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'noteId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_SaveAndNew, plg_Created, planning_Wrapper, plg_RowNumbering, plg_AlignDecimals';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, planning';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, planning';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, planning';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, planning';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'resourceId, productId=Материал, packagingId, packQuantity';
    
        
    /**
     * Активен таб
     */
    public $currentTab = 'Протоколи->Производство';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('noteId', 'key(mvc=planning_DirectProductionNote)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('resourceId', 'key(mvc=planning_Resources,select=title,allowEmpty)', 'silent,caption=Ресурс,mandatory,removeAndRefreshForm=productId|packagingId|quantityInPack|quantity|packQuantity|measureId');
        $this->FLD('input', 'enum(input=Влагане,pop=Отпадък)', 'caption=Действие');
        
        parent::setDetailFields($this);
        
        // Само вложими продукти
        $this->setDbUnique('noteId,resourceId,productId,classId');
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
    	
    	$classId = cat_Products::getClassId();
    	$noProducts = TRUE;
    	
    	if($rec->id){
    		$form->setReadOnly('resourceId');
    	}
    	
    	if(isset($rec->resourceId)){
    		$oQuery = planning_ObjectResources::getQuery();
    		$oQuery->EXT('type', 'planning_Resources', 'externalName=type,externalKey=resourceId');
    		$oQuery->where("#resourceId = {$rec->resourceId} AND #type = 'material'");
    		$oQuery->where("#classId = {$classId}");
    		
    		if($oQuery->count()){
    			while($oRec = $oQuery->fetch()){
    				$products[$oRec->objectId] = cat_Products::getTitleById($oRec->objectId, FALSE);
    			}
    			
    			if(count($products) == 1){
    				$form->setDefault('productId', key($products));
    			}
    			
    			$form->setOptions('productId', array('' => '') + $products);
    			$noProducts = FALSE;
    		}
    	}
    	
    	if($noProducts){
    		$form->setField('productId', 'input=none');
    		$form->setField('packagingId', 'input=none');
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
    	$rec = &$form->rec;
    	
    	if(empty($rec->productId) && isset($rec->resourceId)){
    		$shortUoM = cat_UoM::getShortName($rec->resourceId);
    		$form->setField('packQuantity', "unit={$shortUoM}");
    	}
    	
    	if($form->isSubmitted()){
    		if(empty($rec->productId)){
    			$rec->measureId = planning_Resources::fetchField($rec->resourceId);
    		}
    	}
    }
    
    
    /**
     * Преди подготвяне на едит формата
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->resourceId = planning_Resources::getShortHyperlink($rec->resourceId);
    	
    	if(empty($rec->productId)){
    		unset($row->productId);
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterPrepareListRows($mvc, &$data)
    {
    	if(!count($data->recs)) return;
    	$hideProductCol = TRUE;
    	
    	foreach ($data->rows as $id => &$row)
    	{
    		$rec = $data->recs[$id];
    		if($rec->input == 'pop'){
    			$row->packQuantity = "<span class='red'>-{$row->packQuantity}</span>";
    		}
    		
    		if($rec->productId){
    			$hideProductCol = FALSE;
    		}
    	}
    	
    	if($hideProductCol === TRUE){
    		unset($data->listFields['productId']);
    	}
    }
}