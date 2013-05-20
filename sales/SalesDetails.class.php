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
                        plg_AlignDecimals';
    
    
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
    public $canRead = 'admin, sales';
    
    
    /**
     * Кой има право да променя?
     * 
     * @var string|array
     */
    public $canEdit = 'admin, sales';
    
    
    /**
     * Кой има право да добавя?
     * 
     * @var string|array
     */
    public $canAdd = 'admin, sales';
    
    
    /**
     * Кой може да го види?
     * 
     * @var string|array
     */
    public $canView = 'admin, sales';
    
    
    /**
     * Кой може да го изтрие?
     * 
     * @var string|array
     */
    public $canDelete = 'admin, sales';
    
    
    /**
     * Брой записи на страница
     * 
     * @var integer
     */
    public $listItemsPerPage;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, quantity, packagingId, uomId, packPrice, discount, amount';
    
        
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'RowNumb';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('saleId', 'key(mvc=sales_Sales)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('policyId', 'class(interface=price_PolicyIntf, select=title)', 'caption=Политика, silent');
        
        $this->FLD('productId', 'key(mvc=cat_Products, select=name, allowEmpty)', 'caption=Продукт,notNull,mandatory');
        $this->FLD('uomId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,input=none');
        $this->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty)', 'caption=Мярка/Опак.');

        // Количество в основна мярка
        $this->FLD('quantity', 'float', 'caption=Количество,input=none');
        
        $this->FLD('quantityDelivered', 'double', 'caption=К-во->Доставено,input=none'); // Сумата на доставената стока
        $this->FNC('packQuantityDelivered', 'double(minDecimals=0)', 'caption=К-во->Доставено,input=none'); // Сумата на доставената стока
        
        
        // Количество (в осн. мярка) в опаковката, зададена от 'packagingId'; Ако 'packagingId'
        // няма стойност, приема се за единица.
        $this->FLD('quantityInPack', 'float', 'input=none');
        
        // Цена за единица продукт в основна мярка
        $this->FLD('price', 'double(minDecimals=2)', 'caption=Цена,input=none');
        
        $this->FNC('amount', 'float(decimals=2)', 'caption=Сума');
        
        // Брой опаковки (ако има packagingId) или к-во в основна мярка (ако няма packagingId)
        $this->FNC('packQuantity', 'float', 'caption=К-во,input=input,mandatory');
        
        // Цена за опаковка (ако има packagingId) или за единица в основна мярка (ако няма
        // packagingId)
        $this->FNC('packPrice', 'float(minDecimals=2,maxDecimals=100)', 'caption=Цена,input=input');
        
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
     * Извиква се след успешен запис в модела
     * 
     * @param core_Detail $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave($mvc, &$id, $rec, $fieldsList = NULL)
    {
        // Подсигуряваме наличието на ключ към мастър записа
        if (empty($rec->{$mvc->masterKey})) {
            $rec->{$mvc->masterKey} = $mvc->fetchField($rec->id, $mvc->masterKey);
        }
        
        $mvc->updateMasterSummary($rec->{$mvc->masterKey}, $rec);
    }

    
    /**
     * Обновява агрегатни стойности в мастър записа
     * 
     * @param int $masterId ключ на мастър модела
     * @param stdClass $hotRec запис на модела, промяната на който е предизвикала обновяването
     */
    public static function updateMasterSummary($masterId, $hotRec = NULL)
    {
        $salesRec = sales_Sales::fetchRec($masterId);
        
        /* @var $query core_Query */
        $query = static::getQuery();
        
        $amountDeal = 0;
        
        $query->where("#saleId = '{$masterId}'");
        
        while ($rec = $query->fetch()) {
            $VAT = 1;
            
            if ($salesRec->chargeVat == 'yes') {
                $VAT += cat_Products::getVat($rec->productId, $salesRec->valior);
            }
            
            $amountDeal += $rec->amount * $VAT;
        }
        
        sales_Sales::save(
            (object)array(
                'id' => $masterId,
                'amountDeal' => $amountDeal,
            )
        );
    }

    /**
     * Извиква се преди изпълняването на екшън
     * 
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param string $action
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
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
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    }
    
    
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
            if ($salesRec->chargeVat == 'yes') {
                $rec->packPrice *= 1 + cat_Products::getVat($rec->productId, $masterRec->valior);
            }
            
            // Конвертираме цените във валутата на продажбата
            $rec->packPrice = $rec->packPrice / $salesRec->currencyRate;
            
            $rec->amount = $rec->packPrice * $rec->packQuantity;
            $rec->amount = round($rec->amount, 2);
            
            $salesRec->amountDeal += $rec->amount;
        }
    }
    
    
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
                    $shortUomName = cat_UoM::fetchField($rec->uomId, 'shortName');
                    $row->packagingId .= ' <small class="quiet">' . $row->quantityInPack . '  ' . $shortUomName . '</small>';
                }
                
                $row->quantity = new core_ET('
                    <div style="float: left; width: 50%; text-align: left;">[#packQuantity#]</div>
                    <div style="float: right; width: 50%; margin-left: -6px;">[#packQuantityDelivered#]</div>
                ');
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
        
        if ($policyId = $rec->policyId) {
            /* @var $Policy price_PolicyIntf */
            $Policy = cls::get($policyId);
            
            $data->form->setField('policyId', 'input=hidden');
            $data->form->setOptions('productId', 
                $Policy->getProducts($masterRec->contragentClassId, $masterRec->contragentId));
        }
        
        if (!empty($rec->packPrice)) {
            $rec->packPrice *= 1 + cat_Products::getVat($rec->productId, $masterRec->valior);
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
        if ($form->isSubmitted() && !$form->gotErrors()) {
            
            // Извличане на информация за продукта - количество в опаковка, единична цена
            
            $rec        = $form->rec;

            $masterRec  = sales_Sales::fetch($rec->{$mvc->masterKey});
            $contragent = array($masterRec->contragentClassId, $masterRec->contragentId);
            
            /* @var $productRef cat_ProductAccRegIntf */
            $productRef  = new core_ObjectReference('cat_Products', $rec->productId);
            $productInfo = $productRef->getProductInfo();
            
            expect($productInfo);
            
            // Определяне на цена, количество и отстъпка за опаковка
            
            /* @var $Policy price_PolicyIntf */
            $Policy = cls::get($rec->policyId);
            
            $policyInfo = $Policy->getPriceInfo(
                $masterRec->contragentClassId, 
                $masterRec->contragentId, 
                $rec->productId,
                $rec->packagingId,
                $rec->packQuantity,
                $masterRec->date
            );
            
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
            
            if (empty($rec->packPrice)) {
                // Цената идва от ценоразписа. От ценоразписа цените идват в основна валута и 
                // няма нужда от конвертиране.
                $rec->price = $policyInfo->price;
            } else {
                // Цената е въведена от потребителя. Потребителите въвеждат цените във валутата
                // на продажбата. Конвертираме цената към основна валута по курса, зададен
                // в мастър-продажбата.
                $rec->packPrice *= $masterRec->currencyRate;
                
                // Потребителя въвежда цените с ДДС
                $rec->packPrice /= 1 + cat_Products::getVat($rec->productId, $masterRec->valior);
                
                // Изчисляваме цената за единица продукт в осн. мярка
                $rec->price  = $rec->packPrice  / $rec->quantityInPack;
            }
            
            if (empty($rec->discount)) {
                $rec->discount = $policyInfo->discount;
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
    }
    
    
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
        if (!empty($data->toolbar->buttons['btnAdd'])) {
            $pricePolicies = core_Classes::getOptionsByInterface('price_PolicyIntf');
            
            $customerClass = $data->masterData->rec->contragentClassId;
            $customerId    = $data->masterData->rec->contragentId;
        
            $addUrl = $data->toolbar->buttons['btnAdd']->url;
            
            foreach ($pricePolicies as $policyId=>$Policy) {
                $Policy = cls::getInterface('price_PolicyIntf', $Policy);
                $data->toolbar->addBtn($Policy->getPolicyTitle($customerClass, $customerId), $addUrl + array('policyId' => $policyId,),
                    "id=btnAdd-{$policyId},class=btn-shop");
            }
            
            unset($data->toolbar->buttons['btnAdd']);
        }
    }
}
