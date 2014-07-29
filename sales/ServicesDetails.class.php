<?php
/**
 * Клас 'sales_ServicesDetails'
 *
 * Детайли на мениджър на предавателните протоколи
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_ServicesDetails extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на предавателния протокол';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Услуга';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'shipmentId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Created, sales_Wrapper, plg_RowNumbering, plg_SaveAndNew, 
                        plg_AlignDecimals2, doc_plg_HidePrices, store_plg_DocumentDetail';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, sales';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, sales';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, sales';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, uomId, packQuantity, packPrice, discount, amount';
    
        
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
	/**
     * Полета свързани с цени
     */
    public $priceFields = 'price,amount,discount,packPrice';
    
    
    /**
     * Полета за скриване/показване от шаблоните
     */
    public $toggleFields = 'packagingId=Опаковка,packQuantity=Количество,packPrice=Цена,discount=Отстъпка,amount=Сума,weight=Обем,volume=Тегло,info=Инфо';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('shipmentId', 'key(mvc=sales_Services)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('classId', 'class(select=title)', 'caption=Мениджър,silent,input=hidden');
        $this->FLD('productId', 'int(cellAttr=left)', 'caption=Продукт,notNull,mandatory', 'tdClass=large-field');
        $this->FLD('uomId', 'key(mvc=cat_UoM, select=shortName)', 'caption=Мярка,input=none');
        $this->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty)', 'caption=Мярка/Опак.,input=none');
        $this->FLD('quantity', 'double', 'caption=К-во,input=none');
        $this->FLD('quantityInPack', 'double(decimals=2)', 'input=none,column=none');
        $this->FLD('price', 'double(decimals=2)', 'caption=Цена,input=none');
        $this->FNC('amount', 'double(minDecimals=2,maxDecimals=2)', 'caption=Сума,input=none');
        $this->FNC('packQuantity', 'double(Min=0)', 'caption=К-во,input=input,mandatory');
        $this->FNC('packPrice', 'double(minDecimals=2)', 'caption=Цена,input');
        $this->FLD('discount', 'percent', 'caption=Отстъпка');
    }


    /**
     * Изчисляване на цена за опаковка на реда
     */
    public function on_CalcPackPrice(core_Mvc $mvc, $rec)
    {
        if (!isset($rec->price) || empty($rec->quantity) || empty($rec->quantityInPack)) {
            return;
        }
    
        $rec->packPrice = $rec->price * $rec->quantityInPack;
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     */
    public function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
        if (!isset($rec->price) || empty($rec->quantity) || empty($rec->quantityInPack)) {
            return;
        }
    
        $rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }
    
    
    /**
     * Изчисляване на сумата на реда
     */
    public function on_CalcAmount(core_Mvc $mvc, $rec)
    {
        if (empty($rec->price) || empty($rec->quantity)) {
            return;
        }
    
        $rec->amount = $rec->price * $rec->quantity;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if(($action == 'edit' || $action == 'delete' || $action == 'add') && isset($rec)){
        	if($mvc->Master->fetchField($rec->shipmentId, 'state') != 'draft'){
        		$requiredRoles = 'no_one';
        	}
        }
    }


	/**
     * След извличане на записите от базата данни
     */
    public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
        $recs = &$data->recs;
        $orderRec = $data->masterData->rec;
        
        if (empty($recs)) return;
        
        deals_Helper::fillRecs($mvc->Master, $recs, $orderRec);
    }
    
    
    /**
     * След обработка на записите от базата данни
     */
    public function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
        $rows = $data->rows;
    	
        // Скриваме полето "мярка"
        $data->listFields = array_diff_key($data->listFields, arr::make('uomId', TRUE));
        
        // Флаг дали има отстъпка
        $haveDiscount = FALSE;
    
        if(count($data->rows)) {
            foreach ($data->rows as $i => &$row) {
            	$rec = &$data->recs[$i];
            	$ProductManager = cls::get($rec->classId);
        		$row->productId = $ProductManager->getTitleById($rec->productId);
        		$haveDiscount = $haveDiscount || !empty($rec->discount);
    			
                if (empty($rec->packagingId)) {
                    $row->packagingId = ($rec->uomId) ? $row->uomId : '???';
                } else {
                    $shortUomName = cat_UoM::getShortName($rec->uomId);
                    $row->quantityInPack = $mvc->getFieldType('quantityInPack')->toVerbal($rec->quantityInPack);
                    $row->packagingId .= ' <small class="quiet">' . $row->quantityInPack . ' ' . $shortUomName . '</small>';
                	$row->packagingId = "<span class='nowrap'>{$row->packagingId}</span>";
                }
            }
        }
    
        if(!$haveDiscount) {
            unset($data->listFields['discount']);
        }
    }
        
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
    	$rec = &$data->form->rec;
    	$masterRec = $data->masterRec;
    	
    	$ProductManager = ($data->ProductManager) ? $data->ProductManager : cls::get($rec->classId);
    	
    	// Намираме всички продаваеми продукти, и оттях оставяме само складируемите за избор
    	$products = $ProductManager::getByProperty('canSell');
    	$products2 = $ProductManager::getByProperty('canStore');
    	
    	$products = array_diff_key($products, $products2);
    	
    	expect(count($products));
    	if (empty($rec->id)) {
    		$data->form->addAttr('productId', array('onchange' => "addCmdRefresh(this.form);document.forms['{$data->form->formAttr['id']}'].elements['id'].value ='';document.forms['{$data->form->formAttr['id']}'].elements['packPrice'].value ='';document.forms['{$data->form->formAttr['id']}'].elements['discount'].value ='';this.form.submit();"));
    		$data->form->setOptions('productId', array('' => ' ') + $products);
    	} else {
    		$data->form->setOptions('productId', array($rec->productId => $products[$rec->productId]));
    	}
    }
    
    
    /**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if (!empty($data->toolbar->buttons['btnAdd'])) {
    		$productManagers = core_Classes::getOptionsByInterface('cat_ProductAccRegIntf');
    		$masterRec = $data->masterData->rec;
    
    		foreach ($productManagers as $manId => $manName) {
    			$productMan = cls::get($manId);
    			$products = $productMan::getByProperty('canSell');
    			$products2 = $productMan::getByProperty('canStore');
    			$products = array_diff_key($products, $products2);
    			
    			if(!count($products)){
    				$error = "error=Няма купуваеми {$productMan->title}";
    			}
    
    			$data->toolbar->addBtn($productMan->singleTitle, array($mvc, 'add', $mvc->masterKey => $masterRec->id, 'classId' => $manId, 'ret_url' => TRUE),
    					"id=btnAdd-{$manId},{$error},order=10", 'ef_icon = img/16/shopping.png');
    			unset($error);
    		}
    
    		unset($data->toolbar->buttons['btnAdd']);
    	}
    }
}