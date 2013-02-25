<?php
/**
 * Клас 'sales_SalesSales'
 *
 * Детайли на мениджър на документи за продажба на продукти от каталога (@see sales_Sales)
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
    public $title = 'Детайли на Продажби';


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
        $this->FLD('saleId', 'key(mvc=sales_Sales)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('policyId', 'class(interface=price_PolicyIntf, select=title)', 'caption=Политика, silent');
        
        $this->FLD('productId', 'key(mvc=cat_Products, select=name, allowEmpty)', 'caption=Продукт,notNull,mandatory');
        $this->FLD('uomId', 'key(mvc=cat_UoM, select=shortName)', 'caption=Мярка,input=none');
        $this->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty)', 'caption=Мярка/Опак.');
        
        // Количество в брой опаковки
        $this->FLD('packQuantity', 'float', 'mandatory,caption=К-во');
        
        // Количество в основна мярка
        $this->FLD('quantity', 'float', 'input=none,caption=К-во');
        
        // Цена за опаковка
        $this->FLD('packPrice', 'float(minDecimals=2)', 'caption=Цена');
        
        // Цена за единица продукт в основна мярка
        $this->FLD('price', 'float(minDecimals=2)', 'input=none,caption=Ед. Цена');
        
        $this->FLD('discount', 'percent', 'caption=Отстъпка');
        $this->FNC('amount', 'float(minDecimals=2)', 'caption=Сума');
    }
    
    
    /**
     * Изчисляване на сумата на реда
     * 
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public function on_CalcAmount(core_Mvc $mvc, $rec)
    {
        if (empty($rec->packPrice) || empty($rec->packQuantity)) {
            return;
        }
        
        $rec->amount = $rec->packPrice * $rec->packQuantity;
        
        if (!empty($rec->discount)) {
            $rec->amount *= (1-$rec->discount);
        }
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
    public function updateMasterSummary($masterId, $hotRec = NULL)
    {
        /* @var $query core_Query */
        $query = static::getQuery();
        
        $amountDeal = 0;
        
        $query->where("#{$this->masterKey} = '{$masterId}'");
        
        while ($rec = $query->fetch()) {
            $amountDeal += $rec->amount;
        }
        
        sales_Sales::save(
            (object)array(
                'id' => $masterId,
                'amountDeal' => $amountDeal
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
                
                $haveDiscount = $haveDiscount || ($rec->discount != 0);
    
                if (empty($rec->packagingId)) {
                    $row->packagingId = cat_UoM::fetchField($rec->uomId, 'name');
                } else {
                    $price = $row->price; // Единична цена
                    $row->price  = $row->packPrice;
                    $row->price .= 
                        ' <small class="quiet" style="display: block;">' 
                        . $row->quantity . ' x ' . $price . ' за ' . $row->uomId 
                        . '</small>';
                    $row->packagingId .= ' ' . ($rec->quantity / $rec->packQuantity) . ' ' . $row->uomId;
                    $row->quantity = $row->packQuantity;
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
                $rec->quantity,
                $masterRec->date
            );
            
            if (empty($rec->packagingId)) {
                // В продажба в основна мярка
                $productsPerPack = 1;
                $rec->price    = $rec->packPrice;
                $rec->quantity = $rec->packQuantity;
            } else {
                // Продажба на опаковки
                if (!$packInfo = $productInfo->packagings[$rec->packagingId]) {
                    $form->setError('packagingId', 'Избрания продукт не се предлага в тази опаковка');
                    return;
                }
                
                $productsPerPack = $packInfo->quantity;
            }
            
            if (empty($rec->packPrice)) {
                $rec->packPrice = $policyInfo->price;

                // Цената идва от ценоразписа в основна валута. Конвертираме я към валутата
                // на продажбата.
                $rec->packPrice = 
                    currency_CurrencyRates::convertAmount(
                        $rec->packPrice, 
                        $masterRec->date, 
                        NULL, // Основната валута към $masterRec->date
                        $masterRec->currencyId,
                        4
                    );
            }
            if (empty($rec->discount)) {
                $rec->discount = $policyInfo->discount;
            }
            
            $rec->quantity = $rec->packQuantity * $productsPerPack;
            $rec->price    = round($rec->packPrice / $productsPerPack, 2);
            
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
                    "id=btnAdd-{$policyId},class=btn-add");
            }
            
            unset($data->toolbar->buttons['btnAdd']);
        }
    }
}
