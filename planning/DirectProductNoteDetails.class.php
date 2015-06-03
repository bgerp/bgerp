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
    public $singleTitle = 'Ресурс';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'noteId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_SaveAndNew, plg_Created, planning_Wrapper, plg_RowNumbering, plg_AlignDecimals, plg_Sorting';
    
    
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
        $this->FLD('type', 'enum(input=Влагане,pop=Отпадък)', 'caption=Действие,silent,input=hidden');
        
        parent::setDetailFields($this);
        $this->FLD('conversionRate', 'double', 'input=none');
        
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
    	
    	// Не може да се променя ресурса при редакция
    	if($rec->id){
    		$form->setReadOnly('resourceId');
    	}
    	
    	$form->setDefault('classId', $classId);
    	
    	if(isset($rec->resourceId) && $rec->type == 'input'){
    		$materialsArr = planning_ObjectResources::fetchRecsByClassAndType($rec->resourceId, $classId, 'material');
    		
    		// При редакция ако е имало избран артикул, но вече не е към ресурса, все още може да се избира
    		if($rec->id && $rec->productId){
    			if(!array_key_exists($rec->productId, $materialsArr)){
    				$materialsArr[$rec->productId] = (object)array('objectId' => $rec->productId);
    			}
    		}
    		
    		// Ако има достъпни материали за избор
    		if(count($materialsArr)){
    			foreach($materialsArr as $oRec){
    				$products[$oRec->objectId] = cat_Products::getTitleById($oRec->objectId, FALSE);
    			}
    			
    			// Ако има точно една опция, избираме я по дефолт
    			if(count($products) == 1){
    				$form->setDefault('productId', key($products));
    			}
    			
    			// Задаваме достъпните опции
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
    		
    		if($rec->type == 'pop'){
    			$rType = planning_Resources::fetchField($rec->resourceId, 'type');
    			if($rType != 'material'){
    				$form->setError('resourceId,type', 'Отпадният ресурс трябва да е материал');
    			} else {
    				if(!planning_Resources::fetchField($rec->resourceId, 'selfValue')){
    					$form->setError('type', 'Отпадния ресурс няма себестойност');
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * Преди запис
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	$rec->conversionRate = ($rec->productId) ? planning_ObjectResources::fetchField("#resourceId = {$rec->resourceId} AND #objectId = {$rec->productId}", 'conversionRate') : 1;
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
    		
    		if($rec->productId){
    			$hideProductCol = FALSE;
    		}
    		
    		if($rec->type == 'pop'){
    			$row->packQuantity .= " {$row->packagingId}";
    		}
    	}
    	
    	// Ако няма нито един запис с артикул, не показваме колонката му
    	if($hideProductCol === TRUE){
    		unset($data->listFields['productId']);
    	}
    }
    
    
    /**
     * След подготовка на детайлите, изчислява се общата цена
     * и данните се групират
     */
    public static function on_AfterPrepareDetail($mvc, $res, $data)
    {
    	$data->inputArr = $data->popArr = array();
    	$countInputed = $countPoped = 1;
    	$Int = cls::get('type_Int');
    	
    	// За всеки детайл (ако има)
    	if(count($data->rows)){
    		foreach ($data->rows as $id => $row){
    			$rec = $data->recs[$id];
    			
    			// Разделяме записите според това дали са вложими или не
    			if($rec->type == 'input'){
    				$row->RowNumb = $Int->toVerbal($countInputed);
    				$data->inputArr[$id] = $row;
    				$countInputed++;
    			} else {
    				$row->RowNumb = $Int->toVerbal($countPoped);
    				$data->popArr[$id] = $row;
    				$countPoped++;
    			}
    		}
    	}
    }
    
    
    /**
     * Променяме рендирането на детайлите
     * 
     * @param stdClass $data
     * @return core_ET $tpl
     */
    function renderDetail_($data)
    {
    	$tpl = new ET("");
    	
    	// Рендираме таблицата с вложените артикули
    	$table = cls::get('core_TableView', array('mvc' => $this));
    	$detailsInput = $table->get($data->inputArr, $data->listFields);
    	$detailsInput = ht::createElement("div", array('style' => 'margin-top:5px'), $detailsInput);
    	
    	$tpl->append($detailsInput, 'planning_DirectProductNoteDetails');
    	
    	// Добавяне на бутон за нов ресурс
    	if($this->haveRightFor('add', (object)array('noteId' => $data->masterId))){
    		$tpl->append(ht::createBtn('Нов ресурс', array($this, 'add', 'noteId' => $data->masterId, 'type' => 'input', 'ret_url' => TRUE),  NULL, NULL, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/star_2.png')), 'planning_DirectProductNoteDetails');
    	}
    	
    	// Рендираме таблицата с избор на отпадъци
    	$data->listFields['resourceId'] = 'Отпадък';
    	unset($data->listFields['productId'], $data->listFields['packagingId']);
    	$detailsPop = $table->get($data->popArr, $data->listFields);
    	$detailsPop = ht::createElement("div", array('style' => 'margin-top:5px;margin-bottom:5px'), $detailsPop);
    	$tpl->append($detailsPop, 'planning_DirectProductNoteDetails');
    	
    	// Добавяне на бутон за нов отпадък
    	if($this->haveRightFor('add', (object)array('noteId' => $data->masterId))){
    		$tpl->append(ht::createBtn('Отпадък', array($this, 'add', 'noteId' => $data->masterId, 'type' => 'pop', 'ret_url' => TRUE),  NULL, NULL, array('style' => 'margin-top:5px;;margin-bottom:10px;', 'ef_icon' => 'img/16/star_2.png')), 'planning_DirectProductNoteDetails');
    	}
    	
    	// Връщаме шаблона
    	return $tpl;
    }
}