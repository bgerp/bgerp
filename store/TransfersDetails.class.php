<?php
/**
 * Клас 'store_TransfersDetails'
 *
 * Детайли на мениджър на детайлите на междускладовите трансфери (@see store_Transfers)
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_TransfersDetails extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на междускладовите трансфери';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Продукт';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'transferId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Created, store_Wrapper, plg_RowNumbering, plg_AlignDecimals';
    
    
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
     * Кой може да го види?
     */
    public $canView = 'ceo, store';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, uomId, packQuantity';
    
        
    /**
     * Активен таб
     */
    public $currentTab = 'Трансфери';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('transferId', 'key(mvc=store_Transfers)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('classId', 'class(select=title)', 'caption=Мениджър,silent,input=hidden');
        $this->FLD('productId', 'int(cellAttr=left)', 'caption=Продукт,notNull,mandatory');
        $this->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty)', 'caption=Мярка/Опак.');
        $this->FLD('uomId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,input=none');
        $this->FLD('quantity', 'double', 'caption=К-во,input=none');
        $this->FLD('quantityInPack', 'double(decimals=2)', 'input=none,column=none');
        $this->FLD('isConvertable', 'enum(no,yes)', 'input=none');
        $this->FNC('packQuantity', 'double(decimals=2)', 'caption=К-во,input,mandatory');
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     */
    public function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
        if (empty($rec->quantity) || empty($rec->quantityInPack)) {
            return;
        }
    
        $rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }


    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave($mvc, &$id, $rec, $fieldsList = NULL)
    {
        // Подсигуряваме наличието на ключ към мастър записа
        if (empty($rec->{$mvc->masterKey})) {
            $rec->{$mvc->masterKey} = $mvc->fetchField($rec->id, $mvc->masterKey);
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if(($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec)){
        	if($mvc->Master->fetchField($rec->transferId, 'state') != 'draft'){
        		$requiredRoles = 'no_one';
        	}
        }
    }
    
    
    /**
     * След обработка на записите от базата данни
     */
    public function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
        $rows = $data->rows;
    
        $data->listFields = array_diff_key($data->listFields, arr::make('uomId', TRUE));
        if(count($data->rows)) {
            foreach ($data->rows as $i => &$row) {
                $rec = &$data->recs[$i];
                $ProductManager = cls::get($rec->classId);
                
    			$row->productId = $ProductManager->getTitleById($rec->productId);
    			
                if (empty($rec->packagingId)) {
                    $row->packagingId = ($rec->uomId) ? $row->uomId : '???';
                } else {
                    $shortUomName = cat_UoM::getShortName($rec->uomId);
                    $row->quantityInPack = $mvc->fields['quantityInPack']->type->toVerbal($rec->quantityInPack);
                    $row->packagingId .= ' <small class="quiet">' . $row->quantityInPack . '  ' . $shortUomName . '</small>';
                }
            }
        }
    }
        
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        $ProductManager = cls::get($rec->classId);
        
    	if (empty($rec->id)) {
        	$data->form->addAttr('productId', array('onchange' => "addCmdRefresh(this.form);document.forms['{$data->form->formAttr['id']}'].elements['id'].value ='';this.form.submit();"));
            $data->form->setOptions('productId', $ProductManager::getByProperty('canStore'));
        	
        } else {
            $data->form->setOptions('productId', array($rec->productId => $ProductManager->getTitleById($rec->productId)));
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    { 
    	$rec = $form->rec;
    	expect($ProductMan = cls::get($rec->classId));
	    if($form->rec->productId){
	    	$form->setOptions('packagingId', $ProductMan->getPacks($rec->productId));
	    }
	    	
    	if ($form->isSubmitted() && !$form->gotErrors()) {
        
            // Извличаме ид на политиката, кодирано в ид-то на продукта 
            $rec->packagingId = ($rec->packagingId) ? $rec->packagingId : NULL;
            $productInfo = $ProductMan->getProductInfo($rec->productId, $rec->packagingId);
			
            if (empty($rec->packagingId)) {
                $rec->quantityInPack = 1;
            } else {
             	if ($rec->packagingId != $productInfo->packagingRec->packagingId) {
                    $form->setError('packagingId', 'Избрания продукт не се предлага в тази опаковка');
                    return;
                }
                $rec->quantityInPack = $productInfo->packagingRec->quantity;
            }
            
            // Отбелязване дали продукта е вложим
            $rec->isConvertable = isset($productInfo->meta['canConvert']) ? 'yes' : 'no';
            $rec->quantity = $rec->packQuantity * $rec->quantityInPack;
            $rec->uomId = $productInfo->productRec->measureId;
        }
    }
    
    
	/**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
    	if (!empty($data->toolbar->buttons['btnAdd'])) {
            $productManagers = core_Classes::getOptionsByInterface('cat_ProductAccRegIntf');
            $masterRec = $data->masterData->rec;
            $addUrl = $data->toolbar->buttons['btnAdd']->url;
            
            foreach ($productManagers as $manId => $manName) {
            	$productMan = cls::get($manId);
            	$products = $productMan::getByProperty('canStore');
                if(!count($products)){
                	$error = "error=Няма продаваеми {$productMan->title}";
                }
                
                $data->toolbar->addBtn($productMan->singleTitle, $addUrl + array('classId' => $manId),
                    "id=btnAdd-{$manId},{$error},order=10", 'ef_icon = img/16/shopping.png');
            	unset($error);
            }
            
            unset($data->toolbar->buttons['btnAdd']);
        }
    }
}