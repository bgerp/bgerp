<?php
/**
 * Клас 'sales_SalesDetails'
 *
 * Детайли на мениджър на документи за продажба на продукти (@see sales_Sales)
 *
 * @category  bgerp
 * @package   sales
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
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
    public $singleTitle = 'Продукт';
    
    
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
    public $listFields = 'productId, packagingId, uomId, quantity, packPrice, discount, amount';
    
        
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
        $this->FLD('productId', 'int(cellAttr=left)', 'caption=Продукт,notNull,mandatory');
        $this->FLD('uomId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,input=none');
        $this->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty)', 'caption=Мярка/Опак.');

        // Количество в основна мярка
        $this->FLD('quantity', 'double', 'caption=Количество,input=none');
        
        $this->FLD('quantityDelivered', 'double', 'caption=К-во->Доставено,input=none'); // Експедирано количество (в основна мярка)
        $this->FNC('packQuantityDelivered', 'double(minDecimals=0)', 'caption=К-во->Доставено,input=none'); // Експедирано количество (в брой опаковки)
        
        $this->FLD('quantityInvoiced', 'double', 'caption=К-во->Фактурирано,input=none'); // Фактурирано количество (в основна мярка)
        
        // Количество (в осн. мярка) в опаковката, зададена от 'packagingId'; Ако 'packagingId'
        // няма стойност, приема се за единица.
        $this->FLD('quantityInPack', 'double', 'input=none');
        
        // Цена за единица продукт в основна мярка
        $this->FLD('price', 'double(minDecimals=2)', 'caption=Цена,input=none');
        
        $this->FNC('amount', 'double(decimals=2)', 'caption=Сума');
        
        // Брой опаковки (ако има packagingId) или к-во в основна мярка (ако няма packagingId)
        $this->FNC('packQuantity', 'double', 'caption=К-во,input=input,mandatory');
        
        // Цена за опаковка (ако има packagingId) или за единица в основна мярка (ако няма
        // packagingId)
        $this->FNC('packPrice', 'double(decimals=2)', 'caption=Цена,input');
        
        $this->FLD('discount', 'percent', 'caption=Отстъпка');
    }
    
    
    /**
     * Изчисляване на цена за опаковка на реда
     * 
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public function on_CalcPackPrice(core_Mvc $mvc, $rec)
    {
        if (empty($rec->price) || empty($rec->quantity) || empty($rec->quantityInPack)) {
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
        if (empty($rec->price) || empty($rec->quantity) || empty($rec->quantityInPack)) {
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
        $recs     = $data->recs;
        $salesRec = $data->masterData->rec;
        
        // amountDeal е записана в БД, но за да се избегнат грешки от закръгление я пресмятаме
        // тук отново
        $salesRec->amountDeal = 0;
        
        if (empty($recs)) {
            return;
        }
        
        foreach ($recs as $rec) {
            // Начисляваме ДДС, при нужда
            if ($salesRec->chargeVat == 'yes' || $salesRec->chargeVat == 'no') {
                $ProductManager = cls::get($rec->classId);
                $rec->packPrice *= 1 + $ProductManager->getVat($rec->productId, $masterRec->valior);
            }
            
            // Конвертираме цените във валутата на продажбата
            $rec->packPrice = $rec->packPrice / $salesRec->currencyRate;
            
            $rec->amount = $rec->packPrice * $rec->packQuantity;
            $rec->amount = round($rec->amount, 2);
            
            $salesRec->amountDeal += $rec->amount;
        }
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
            foreach ($data->rows as $i=>&$row) {
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
                    $row->packagingId .= ' <small class="quiet">' . $row->quantityInPack . '  ' . $shortUomName . '</small>';
                }
                
                $row->quantity = new core_ET('[#packQuantity#] <!--ET_BEGIN packQuantityDelivered-->(<span style="font-size:0.9em;">[#packQuantityDelivered#] ' . tr('дост') . '</span>)<!--ET_END packQuantityDelivered-->');
                $row->quantity->placeObject($row);
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
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $rec       = $data->form->rec;
        $masterRec = $data->masterRec;
       
        $data->form->fields['packPrice']->unit = ($masterRec->chargeVat == 'yes') ? 'с ДДС' : 'без ДДС';
        
        if (empty($rec->id)) {
        	$data->form->addAttr('productId', array('onchange' => "addCmdRefresh(this.form);this.form.submit();"));
            
        	$ProductManager = cls::get($rec->classId);
            $data->form->setOptions('productId', 
            	$ProductManager->getProducts($masterRec->contragentClassId, $masterRec->contragentId));
        	
        } else {
            // Нямаме зададена ценова политика. В този случай задъжително трябва да имаме
            // напълно определен продукт (клас и ид), който да не може да се променя във формата
            // и полето цена да стане задължително

            $ProductManager = cls::get($rec->classId);
            $data->form->setOptions('productId', array($rec->productId => $ProductManager->getTitleById($rec->productId)));
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
        }
    	
    	if ($form->isSubmitted() && !$form->gotErrors()) {
            
    		if(empty($rec->id) && $id = $mvc->fetchField("#saleId = {$rec->saleId} AND #classId = {$rec->classId} AND #productId = {$rec->productId}", 'id')){
            	$form->setWarning("productId", "Има вече такъв продукт! Искатели да го обновите ?");
            	$rec->id = $id;
            	$update = TRUE;
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
            
            if (empty($rec->packPrice)) {
                // Цената идва от ценоразписа. От ценоразписа цените идват в основна валута и 
                // няма нужда от конвертиране.
                
                // Възможно е, обаче, ценоразписа да не върне цена. В този случай трябва да
                // да сигнализираме на потребителя, че полето за цена е задължително и да не 
                // допускаме записи без цени.
                
                $policyInfo = $ProductMan->getPriceInfo(
                    $masterRec->contragentClassId, 
                    $masterRec->contragentId, 
                    $rec->productId,
                    $rec->classId,
                    $rec->packagingId,
                    $rec->packQuantity,
                    ($masterRec->pricesAtDate) ? $masterRec->pricesAtDate : $masterRec->date
                );
            
                if (empty($policyInfo->price)) {
                    $form->setError('price', 'Продукта няма цена в избраната ценова политика');
                }
                
                $rec->price = $policyInfo->price;
                
                if (empty($rec->discount)) {
                    $rec->discount = $policyInfo->discount;
                }
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
        $row->productId = $ProductManager->getTitleById($rec->productId);
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