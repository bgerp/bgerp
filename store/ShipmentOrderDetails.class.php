<?php
/**
 * Клас 'store_ShipmentOrderDetails'
 *
 * Детайли на мениджър на експедиционни нареждания (@see store_ShipmentOrders)
 *
 * @category  bgerp
 * @package   store
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_ShipmentOrderDetails extends core_Detail
{
    /**
     * Заглавие
     * 
     * @var string
     */
    public $title = 'Детайли на ЕН';


    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'Продукт';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'shipmentId';
    
    
    /**
     * Плъгини за зареждане
     * 
     * var string|array
     */
    public $loadList = 'plg_RowTools, plg_Created, store_Wrapper, plg_RowNumbering, 
                        plg_AlignDecimals';
    
    
    /**
     * Активен таб на менюто
     * 
     * @var string
     */
    public $menuPage = 'Логистика:Складове';
    
    /**
     * Кой има право да чете?
     * 
     * @var string|array
     */
    public $canRead = 'admin, store';
    
    
    /**
     * Кой има право да променя?
     * 
     * @var string|array
     */
    public $canEdit = 'admin, store';
    
    
    /**
     * Кой има право да добавя?
     * 
     * @var string|array
     */
    public $canAdd = 'admin, store';
    
    
    /**
     * Кой може да го види?
     * 
     * @var string|array
     */
    public $canView = 'admin, store';
    
    
    /**
     * Кой може да го изтрие?
     * 
     * @var string|array
     */
    public $canDelete = 'admin, store';
    
    
    /**
     * Кой може да остойностява ЕН
     * 
     * @var string|array
     */
    public $canValuate = 'admin, acc';
    
    
    /**
     * Брой записи на страница
     * 
     * @var integer
     */
    public $listItemsPerPage;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, uomId, quantity, price, discount, amount';
    
        
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'RowNumb';
    

    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('shipmentId', 'key(mvc=store_ShipmentOrders)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('policyId', 'class(interface=price_PolicyIntf, select=title)', 'caption=Политика, silent');
        
        $this->FLD('productId', 'key(mvc=cat_Products, select=name, allowEmpty)', 'caption=Продукт,notNull,mandatory');
        $this->FLD('uomId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,input=none');
        $this->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty)', 'caption=Мярка/Опак.');
        
        // Количество в основна мярка
        $this->FLD('quantity', 'float', 'caption=К-во,input=none');
        
        // Количество (в осн. мярка) в опаковката, зададена от 'packagingId'; Ако 'packagingId'
        // няма стойност, приема се за единица.
        $this->FLD('quantityInPack', 'float', 'input=none,column=none');
        
        // Цена за единица продукт в основна мярка
        $this->FLD('price', 'float', 'caption=Цена,input=none');
        
        $this->FNC('amount', 'float(decimals=2)', 'caption=Сума');
        
        // Брой опаковки (ако има packagingId) или к-во в основна мярка (ако няма packagingId)
        $this->FNC('packQuantity', 'float', 'caption=К-во,input=input,mandatory');
        
        // Цена за опаковка (ако има packagingId) или за единица в основна мярка (ако няма 
        // packagingId)
        $this->FNC('packPrice', 'float', 'caption=Цена,input=input');
        
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
    
        $rec->amount = round($rec->amount, 2);
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
        
        $mvc->updateMasterSummary($rec->{$mvc->masterKey});
    }

    
    /**
     * Обновява агрегатни стойности в мастър записа
     * 
     * @param int $masterId ключ на мастър модела
     * @param boolean $force FALSE - само запомни ключа на мастъра, но не прави промени по БД
     * 
     *                       TRUE  - направи промените в БД сега! Тези промени ще засегнат:
     *                       
     *                               * Мастър записа с ключ $masterId, ако $masterId не е 
     *                                 празно;
     *                               * Всички мастър записи с ключове, добавени по-рано чрез 
     *                                 извикване на този метод с параметър $force = FALSE.
     *                        
     */
    public function updateMasterSummary($masterId = NULL, $force = FALSE)
    {
        static $updatedMasterIds = array();
         
        if (!$force) {
            if (!empty($masterId)) {
                $updatedMasterIds[$masterId] = $masterId;
            }
            
            return;
        }
        
        $masterIds = empty($masterId) ? $updatedMasterIds : array($masterId);
        
        foreach ($masterIds as $masterId) {
            /* @var $query core_Query */
            $query = static::getQuery();
            
            $amountDelivered = 0;
            
            $query->where("#{$this->masterKey} = '{$masterId}'");
            
            while ($rec = $query->fetch()) {
                $amountDelivered += $rec->amount;
            }
            
            store_ShipmentOrders::save(
                (object)array(
                    'id' => $masterId,
                    'amountDelivered' => $amountDelivered
                )
            );
        }
    }
    
    
    public function on_Shutdown($mvc)
    {
        $mvc->updateMasterSummary(NULL /* ALL */, TRUE /* force update now */);
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
        if ($action == 'delete' || $action == 'add') {
            // Изтриването на ред от ЕН може да се прави от същите потребители, които имат 
            // права да го редактират
            $action = 'edit';
        }
        
        // Прехвърляме правата за достъп до ЕН (мастъра) върху всеки ред от детайлите му. 
        $requiredRoles = store_ShipmentOrders::getRequiredRoles($action, 
            (object)array('id'=>$rec->shipmentId), $userId);
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
    }


    public function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
        $rows = $data->rows;
    
        // Скриваме полето "мярка"
        $data->listFields = array_diff_key($data->listFields, arr::make('uomId', TRUE));
        
        // Само който има право да контира експ. нареждане, само той вижда ценовата информация
        if (!store_ShipmentOrders::haveRightFor('valuate', $data->masterData->rec)) {
            $data->listFields = array_diff_key($data->listFields, arr::make('price, discount, amount', TRUE));
        }
    
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
        if ($policyId = $data->form->rec->policyId) {
            /* @var $Policy price_PolicyIntf */
            $Policy = cls::get($policyId);
            
            $data->form->setField('policyId', 'input=hidden');
            $data->form->setOptions('productId', $Policy->getProducts($data->masterRec->contragentClassId, $data->masterRec->contragentId));
        }
        
        $masterId = $data->form->rec->{$data->masterKey};
        
        // Само който има право да контира експ. нареждане, само той вижда полетата за цена и 
        // отстъпка 
        if (!store_ShipmentOrders::haveRightFor('valuate', (object)array('id'=>$masterId))) {
            $data->form->setField('packPrice', 'input=none');
            $data->form->setField('discount', 'input=none');
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

            $masterRec  = store_ShipmentOrders::fetch($rec->{$mvc->masterKey});
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
                $rec->price = $policyInfo->price;

                // Цената идва от ценоразписа в основна валута. Конвертираме я към валутата
                // на продажбата.
                $rec->price = 
                    currency_CurrencyRates::convertAmount(
                        $rec->price, 
                        $masterRec->date, 
                        NULL, // Основната валута към $masterRec->date
                        $masterRec->currencyId
                    );
            } else {
                $rec->price  = $rec->packPrice  / $rec->quantityInPack;
            }
            
            if (empty($rec->discount)) {
                $rec->discount = $policyInfo->discount;
            }
            
            $rec->price = sales_Sales::roundPrice($rec->price);
            
            // Записваме основната мярка на продукта
            $rec->uomId    = $productInfo->productRec->measureId;
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
