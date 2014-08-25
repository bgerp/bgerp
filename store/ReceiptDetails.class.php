<?php
/**
 * Клас 'store_ReceiptDetails'
 *
 * Детайли на мениджър на детайлите на складовите разписки (@see store_ReceiptDetails)
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_ReceiptDetails extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на складовите разписки';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Продукт';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'receiptId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Created, store_Wrapper, plg_RowNumbering,Policy=purchase_PurchaseLastPricePolicy, 
                        plg_AlignDecimals2, doc_plg_HidePrices, store_plg_DocumentDetail';
    
    
    /**
     * Активен таб на менюто
     */
    public $menuPage = 'Логистика:Складове';
    
    
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
    public $listFields = 'productId, packagingId, uomId, packQuantity, packPrice, discount, amount, weight, volume';
    
        
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
	/**
     * Полета свързани с цени
     */
    public $priceFields = 'price, amount, discount, packPrice';
    
    
    /**
     * Полета за скриване/показване от шаблоните
     */
    public $toggleFields = 'packagingId=Опаковка,packQuantity=Количество,packPrice=Цена,discount=Отстъпка,amount=Сума,weight=Обем,volume=Тегло,info=Инфо';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('receiptId', 'key(mvc=store_Receipts)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('classId', 'class(select=title)', 'caption=Мениджър,silent,input=hidden');
        $this->FLD('productId', 'int', 'caption=Продукт,notNull,mandatory,tdClass=leftCol');
        $this->FLD('uomId', 'key(mvc=cat_UoM, select=shortName)', 'caption=Мярка,input=none');
        $this->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty)', 'caption=Мярка');
        $this->FLD('quantity', 'double', 'caption=К-во,input=none');
        $this->FLD('quantityInPack', 'double(smartRound)', 'input=none,column=none');
        $this->FLD('price', 'double(decimals=2)', 'caption=Цена,input=none');
        $this->FLD('weight', 'cat_type_Weight', 'input=hidden,caption=Тегло');
        $this->FLD('volume', 'cat_type_Volume', 'input=hidden,caption=Обем');
        $this->FNC('amount', 'double(minDecimals=2,maxDecimals=2)', 'caption=Сума,input=none');
        $this->FNC('packQuantity', 'double(Min=0,decimals=2)', 'caption=К-во,input=input,mandatory');
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
        if(($action == 'edit' || $action == 'delete' || $action == 'add') && isset($rec)){
        	if($mvc->Master->fetchField($rec->receiptId, 'state') != 'draft'){
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
        $receiptRec = $data->masterData->rec;
        
        if (empty($recs)) return;
        
        deals_Helper::fillRecs($mvc->Master, $recs, $receiptRec);
    }
    
    
    /**
     * След обработка на записите от базата данни
     */
    public function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
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
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
    	$ProductManager = cls::get('cat_Products');
    	
        // Намираме всички скалдируеми продукти, ако документа е обратен взимаме продаваемите, иначе купуваемите
        $property = ($mvc->Master->fetchField($form->rec->receiptId, 'isReverse') == 'yes') ? 'canSell' : 'canBuy';
        $products = $ProductManager::getByProperty($property);
        
        $products2 = $ProductManager::getByProperty('canStore');
        $products = array_intersect_key($products, $products2);
         
        expect(count($products));
        if (empty($form->rec->id)) {
        	$data->form->addAttr('productId', array('onchange' => "addCmdRefresh(this.form);document.forms['{$data->form->formAttr['id']}'].elements['id'].value ='';document.forms['{$data->form->formAttr['id']}'].elements['packPrice'].value ='';document.forms['{$data->form->formAttr['id']}'].elements['discount'].value ='';this.form.submit();"));
        	$data->form->setOptions('productId', array('' => ' ') + $products);
        } else {
        	$data->form->setOptions('productId', array($rec->productId => $products[$form->rec->productId]));
        }
    }
    
    
    /**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if (!empty($data->toolbar->buttons['btnAdd'])) {
    			$masterRec = $data->masterData->rec;
    			$ProductManager = cls::get('cat_Products');
    			$products = $ProductManager::getByProperty('canBuy');
    			$products2 = $ProductManager::getByProperty('canStore');
    			$products = array_intersect_key($products, $products2);
    			 
    			if(!count($products)){
    				$error = "error=Няма продаваеми {$ProductManager->title}";
    			}
    
    			$data->toolbar->addBtn($ProductManager->singleTitle, array($mvc, 'add', $mvc->masterKey => $masterRec->id, 'classId' => $ProductManager->getClassId(), 'ret_url' => TRUE),
    					"id=btnAdd-{$manId},{$error},order=10", 'ef_icon = img/16/shopping.png');
    			unset($error);
    
    		unset($data->toolbar->buttons['btnAdd']);
    	}
    }
}