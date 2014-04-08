<?php
/**
 * Клас 'sales_SalesDetails'
 *
 * Детайли на мениджър на документи за продажба на продукти (@see sales_Sales)
 *
 * @category  bgerp
 * @package   sales
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_SalesDetails extends core_Detail
{
    /**
     * Заглавие
     * 
     * @var string
     */
    public $title = 'Детайли на продажби';


    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'saleId';
    
    
    /**
     * Плъгини за зареждане
     * 
     * var string|array
     */
    public $loadList = 'plg_RowTools, plg_Created, sales_Wrapper, plg_RowNumbering, 
                        plg_AlignDecimals, doc_plg_HidePrices';
    
    
    /**
     * Активен таб на менюто
     * 
     * @var string
     */
    public $menuPage = 'Търговия:Продажби';
    
    
    /**
     * Кой има право да чете?
     * 
     * @var string|array
     */
    public $canRead = 'ceo, sales';
    
    
    /**
     * Кой има право да променя?
     * 
     * @var string|array
     */
    public $canEdit = 'ceo, sales';
    
    
    /**
     * Кой има право да добавя?
     * 
     * @var string|array
     */
    public $canAdd = 'ceo, sales';
    
    
    /**
     * Кой може да го види?
     * 
     * @var string|array
     */
    public $canView = 'ceo, sales';
    
    
    /**
     * Кой може да го изтрие?
     * 
     * @var string|array
     */
    public $canDelete = 'ceo, sales';
    
    
    /**
     * Брой записи на страница
     * 
     * @var integer
     */
    public $listItemsPerPage;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, uomId, quantity=К-во: Д / П, packPrice, discount, amount';
    
        
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'RowNumb';


    /**
     * Полета свързани с цени
     */
    var $priceFields = 'price,amount,discount,packPrice';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('saleId', 'key(mvc=sales_Sales)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('classId', 'class(interface=cat_ProductAccRegIntf, select=title)', 'caption=Мениджър,silent,input=hidden');
        $this->FLD('productId', 'int(cellAttr=left)', 'caption=Продукт,notNull,mandatory', 'tdClass=large-field');
        $this->FLD('uomId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,input=none');
        $this->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty)', 'caption=Мярка/Опак.', 'tdClass=small-field');

        // Количество в основна мярка
        $this->FLD('quantity', 'double', 'caption=Количество,input=none');
        
        $this->FLD('quantityDelivered', 'double', 'caption=К-во->Доставено,input=none'); // Експедирано количество (в основна мярка)
        $this->FNC('packQuantityDelivered', 'double(minDecimals=0)', 'caption=К-во->Доставено,input=none'); // Експедирано количество (в брой опаковки)
        
        $this->FLD('quantityInvoiced', 'double', 'caption=К-во->Фактурирано,input=none'); // Фактурирано количество (в основна мярка)
        
        // Количество (в осн. мярка) в опаковката, зададена от 'packagingId'; Ако 'packagingId'
        // няма стойност, приема се за единица.
        $this->FLD('quantityInPack', 'double(smartRound)', 'input=none');
        
        // Цена за единица продукт в основна мярка
        $this->FLD('price', 'double(minDecimals=2)', 'caption=Цена,input=none');
        
        // Брой опаковки (ако има packagingId) или к-во в основна мярка (ако няма packagingId)
        $this->FNC('packQuantity', 'double(Min=0)', 'caption=К-во,input=input,mandatory');
        $this->FNC('amount', 'double(decimals=2)', 'caption=Сума');
        
        // Цена за опаковка (ако има packagingId) или за единица в основна мярка (ако няма packagingId)
        $this->FNC('packPrice', 'double', 'caption=Цена,input');
        $this->FLD('discount', 'percent', 'caption=Отстъпка');
    }
    
    
	/**
     * Изчисляване на сумата на реда
     * 
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public function on_CalcAmount(core_Mvc $mvc, $rec)
    {
        if (empty($rec->price) || empty($rec->quantity)) {
            return;
        }
        
        $rec->amount = $rec->price * $rec->quantity;
    }
    
    
    /**
     * Изчисляване на цена за опаковка на реда
     * 
     * @param core_Mvc $mvc
     * @param stdClass $rec
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
     * 
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
        if (!isset($rec->price) || empty($rec->quantity) || empty($rec->quantityInPack)) {
            return;
        }
        
        $rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }
    
    
    /**
     * Изчисляване на доставеното количеството на реда в брой опаковки
     * 
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public function on_CalcPackQuantityDelivered(core_Mvc $mvc, $rec)
    {
        if (empty($rec->quantityDelivered) || empty($rec->quantityInPack)) {
            return;
        }
        
        $rec->packQuantityDelivered = $rec->quantityDelivered / $rec->quantityInPack;
    }
    
    
    /**
     * 
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(&$mvc)
    {
        // Скриване на полетата за създаване
        $mvc->setField('createdOn', 'column=none');
        $mvc->setField('createdBy', 'column=none');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        $requiredRoles = sales_Sales::getRequiredRoles('edit', (object)array('id'=>$rec->saleId), $userId);
    }
    
    
    /**
     * След извличане на записите от базата данни
     */
    public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
        if (empty($data->recs)) return;
    	$recs = &$data->recs;
        $salesRec = $data->masterData->rec;
        
        $map = ($data->masterData->fromProforma) ? array('alwaysHideVat' => TRUE) : array();
        price_Helper::fillRecs($recs, $salesRec, $map);
    }
    
    
    /**
     * След подготовка на записите от базата данни
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
                $rec = $data->recs[$i];
                
                $haveDiscount = $haveDiscount || !empty($rec->discount);
    			
                if (empty($rec->packagingId)) {
                    if ($rec->uomId) {
                        $row->packagingId = $row->uomId;
                    } else {
                        $row->packagingId = '???';
                    }
                } else {
                    $shortUomName = cat_UoM::getShortName($rec->uomId);
                    $row->packagingId .= ' <small class="quiet">' . $row->quantityInPack . ' ' . $shortUomName . '</small>';
                	$row->packagingId = "<span class='nowrap'>{$row->packagingId}</span>";
                }
                
                $quantity = new core_ET('<!--ET_BEGIN packQuantityDelivered-->[#packQuantityDelivered#] /<!--ET_END packQuantityDelivered--> [#packQuantity#]');
                $quantity->placeObject($row);
                $quantity->removeBlocks();
                $row->quantity = $quantity;
            }
        }

        if(!$haveDiscount) {
            unset($data->listFields['discount']);
        }
    }
    
    
    /**
     * Преди подготвяне на едит формата
     */
    static function on_BeforePrepareEditForm($mvc, &$res, $data)
    {
    	if($classId = Request::get('classId', 'class(interface=cat_ProductAccRegIntf)')){
    		$data->ProductManager = cls::get($classId);
    		$mvc->fields['productId']->type = cls::get('type_Key', array('params' => array('mvc' => $data->ProductManager->className, 'select' => 'name')));
    	}
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $rec       = &$data->form->rec;
        $masterRec = $data->masterRec;
       	$ProductManager = ($data->ProductManager) ? $data->ProductManager : cls::get($rec->classId);
       	
       	$data->form->fields['packPrice']->unit = "|*" . $masterRec->currencyId . ", ";
        $data->form->fields['packPrice']->unit .= ($masterRec->chargeVat == 'yes') ? "|с ДДС|*" : "|без ДДС|*";
        
        $products = $ProductManager->getProducts($masterRec->contragentClassId, $masterRec->contragentId);
        expect(count($products));
        
        if (empty($rec->id)) {
        	$data->form->addAttr('productId', array('onchange' => "addCmdRefresh(this.form);document.forms['{$data->form->formAttr['id']}'].elements['id'].value ='';this.form.submit();"));
			$data->form->setOptions('productId', $products);
        	
        } else {
            // Нямаме зададена ценова политика. В този случай задъжително трябва да имаме
            // напълно определен продукт (клас и ид), който да не може да се променя във формата
            // и полето цена да стане задължително
            $data->form->setOptions('productId', array($rec->productId => $products[$rec->productId]));
        }
        
        if (!empty($rec->packPrice)) {
            if ($masterRec->chargeVat == 'yes') {
                
                // Начисляваме ДДС в/у цената
                $rec->packPrice *= 1 + $ProductManager->getVat($rec->productId, $masterRec->valior);
            }
            
            $rec->packPrice /= $masterRec->currencyRate;
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    { 
        $rec = &$form->rec;
        $update = FALSE;
        
        /* @var $ProductMan core_Manager */
        expect($ProductMan = cls::get($rec->classId));
    	if($form->rec->productId){
    		$form->setOptions('packagingId', $ProductMan->getPacks($rec->productId));
    		
    		// Само при рефреш слагаме основната опаковка за дефолт
    		if($form->cmd == 'refresh'){
	    		$baseInfo = $ProductMan->getBasePackInfo($rec->productId);
	    		if($baseInfo->classId == cat_Packagings::getClassId()){
	    			$form->rec->packagingId = $baseInfo->id;
	    		}
    		}
        }
    	
    	if ($form->isSubmitted() && !$form->gotErrors()) {
           
    		if(empty($rec->id)){
    			$where = "#saleId = {$rec->saleId} AND #classId = {$rec->classId} AND #productId = {$rec->productId} AND #packagingId";
    			$where .= ($rec->packagingId) ? "={$rec->packagingId}" : " IS NULL";
    			if($id = $mvc->fetchField($where)){
    				$form->setWarning("productId", "Има вече такъв продукт с тази опаковка. Искате ли да го обновите?");
    				$rec->id = $id;
	            	$update = TRUE;
    			}
            }
            
            // Извличане на информация за продукта - количество в опаковка, единична цена
            $masterRec  = sales_Sales::fetch($rec->{$mvc->masterKey});
            $contragent = array($masterRec->contragentClassId, $masterRec->contragentId);
            
            /* @var $productRef cat_ProductAccRegIntf */
            $productRef  = new core_ObjectReference($ProductMan, $rec->productId);
            $productInfo = $productRef->getProductInfo();
            
            expect($productInfo);
            
            if (empty($rec->packagingId)) {
                // В продажба в основна мярка
                $rec->quantityInPack = 1;
            } else {
                // Продажба на опаковки
                if (!$packInfo = $productInfo->packagings[$rec->packagingId]) {
                    $form->setError('packagingId', 'Избрания продукт не се предлага в тази опаковка');
                    return;
                }
                
                $rec->quantityInPack = $packInfo->quantity;
            }
            
            $rec->quantity = $rec->packQuantity * $rec->quantityInPack;
            
            // Определяне на цена, количество и отстъпка за опаковка
            
            if (!isset($rec->packPrice)) {
                // Цената идва от ценоразписа. От ценоразписа цените идват в основна валута и 
                // няма нужда от конвертиране.
                
                // Възможно е, обаче, ценоразписа да не върне цена. В този случай трябва да
                // да сигнализираме на потребителя, че полето за цена е задължително и да не 
                // допускаме записи без цени.
                
            	$priceAtDate = ($masterRec->pricesAtDate) ? $masterRec->pricesAtDate : $masterRec->valior;
                $policyInfo = $ProductMan->getPriceInfo(
                    $masterRec->contragentClassId, 
                    $masterRec->contragentId, 
                    $rec->productId,
                    $rec->classId,
                    $rec->packagingId,
                    $rec->packQuantity,
                    $priceAtDate
                );
            
                if (empty($policyInfo->price)) {
                    $form->setError('price', "Артикула няма цена към дата|* '{$priceAtDate}'");
                }
                
                $rec->price = $policyInfo->price;
            } else {
                // Цената е въведена от потребителя. Потребителите въвеждат цените във валутата
                // на продажбата. Конвертираме цената към основна валута по курса, зададен
                // в мастър-продажбата.
                $rec->packPrice *= $masterRec->currencyRate;
                
                if ($masterRec->chargeVat == 'yes') {
                	if(!$update || ($update && Request::get('Ignore'))){
                		
                		// Потребителя въвежда цените с ДДС
                    	$rec->packPrice /= 1 + $ProductMan->getVat($rec->productId, $masterRec->valior);
                	} 
                }
                
                // Изчисляваме цената за единица продукт в осн. мярка
                $rec->price  = $rec->packPrice  / $rec->quantityInPack;
            }
            
            // Записваме основната мярка на продукта
            $rec->uomId = $productInfo->productRec->measureId;
            
            // При редакция, ако е променена опаковката слагаме преудпреждение
            if($rec->id){
            	$oldPack = $mvc->fetchField($rec->id, 'packagingId');
            	if($rec->packagingId != $oldPack){
            		$form->setWarning('packPrice,packagingId', 'Опаковката е променена без да е променена цената.|*<br />| Сигурнили сте че зададената цена отговаря на  новата опаковка!');
            	}
            }
        }
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
        $ProductManager = cls::get($rec->classId);
        
        $row->productId = $ProductManager->getTitleById($rec->productId, TRUE, $rec->tplLang);
       
        if(!Mode::is('printing') && !Mode::is('text', 'xhtml')){
        	$row->productId = ht::createLinkRef($row->productId, array($ProductManager, 'single', $rec->productId));
        }
        
        if($ProductManager instanceof techno_Specifications){
        	
        	//@TODO да махна изискването да има дебъг
        	if(haveRole('debug') && mp_Jobs::haveRightFor('add') && !Mode::is('printing') && !Mode::is('text', 'xhtml')){
        		$img = ht::createElement('img', array('src' => sbf('img/16/clipboard_text.png', '')));
        		$row->productId .= "<span style='margin-left:5px'>" . ht::createLink($img, array('mp_Jobs', 'add', 'originClass' => $mvc->getClassId(), 'originDocId' => $rec->id), NULL, 'title=Ново задание') . "</span>";
        	}
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
            	$products = $productMan->getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->date);
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
