<?php

/**
 * Мениджър на продажби - детайл
 *
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 *
 */
class acc_SaleDetails extends core_Detail
{
    /**
     *  @todo Чака за документация...
     */
    var $menuPage = 'Счетоводство';
    

    /**
     *  @todo Чака за документация...
     */
    var $title = 'Продажби';


    /**
     *  @todo Чака за документация...
     */
    var $currentTab = 'acc_Sales';


    /**
     *  @todo Чака за документация...
     */
    var $masterKey = 'saleId';


    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_Rejected, plg_State2, plg_SaveAndNew, 
                    acc_Wrapper, Items=acc_Items';


    /**
     * Права
     */
    var $canRead = 'admin,acc,broker,designer';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,acc,broker,designer';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,acc,broker,designer';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listItemsPerPage = 300;
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'productId,unitId,orderedQuantity,deliveredQuantity,price,discount,
        regularDiscount,state,tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    

    /**
     * @var acc_Items
     */
    var $Items;
    
    
    /**
     * @var acc_Sales
     */
    var $Master;
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('saleId', 'key(mvc=acc_Sales)', 'input=hidden,column=none,silent,mandatory'); // мастер ид
        $this->FLD('productId', 'key(mvc=cat_Products,select=title)', 'caption=Продукт,mandatory');
        
        
        /**
         *  някои от атрибутите на продукта са от тип packaging, и имат информация колко
         *  единици има в 1 опаковка. Винаги участва и опаковката "1 measure".
         *
         *  TODO: Не ми е ясно, да изясня какво е това поле!
         */
        $this->FLD('unitId', 'key(mvc=cat_UoM,select=name)', 'caption=Количество->Мярка,mandatory');
        
        
        /**
         * Поръчано к-во (в мярката, определена от полето packaging)
         */
        $this->FLD('orderedQuantity', 'double', 'caption=Количество->Заявено,mandatory');
        
        
        /**
         * Доставено к-во (в мярката, определена от полето packaging)
         */
        $this->FLD('deliveredQuantity', 'double', 'caption=Количество->Доставено,mandatory');
        
        
        /**
         * Цена на единица к-во (в мярката, определена от полето packaging)
         */
        $this->FLD('price', 'double', 'caption=Ед. цена,mandatory');
        
        
        /**
         * 0 - 1 - конкретната отстъпка този продукт за тази продажба
         */
        $this->FLD('discount', 'double', 'caption=Отстъпка->Отстъпка');
        
        
        /**
         * 0 - 1 - обичайната отстъпка за този клиент
         */
        $this->FLD('regularDiscount', 'double', 'caption=Отстъпка->Обичайна,input=readonly');
    }
    
    
    /**
     *  Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    function on_AfterPrepareEditForm(acc_SaleDetails $mvc, $data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        if (!isset($rec->discount)) {
            $mvc->evalRegularDiscount($rec);
            
            if (!empty($rec->regularDiscount)) {
                $rec->discount = $rec->regularDiscount;
            }
        }
        
        //        $form->setReadOnly('regularDiscount');
    }
    
    
    /**
     *  Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    function on_AfterInputEditForm(acc_SaleDetails $mvc, core_Form $form)
    {
        $mvc->evalRegularDiscount($form->rec);
    }
    
    private function evalRegularDiscount($rec)
    {
        if (!$rec->productId || !$rec->pricelistDate) {
            return;
        }
        
        $masterRec = $this->Master->fetch($rec->{$this->masterKey});
        
        
        /**
         * Клиента по продажбата като обект от регистъра на контактите
         */
        $customerId = $this->Items->fetchField($masterRec->customerEntId, 'objectId');
        
        if ($rec->productId) {
            $rec->regularDiscount =
            acc_Discounts::getDiscount($customerId, $rec->productId, $rec->pricelistDate);
        }
    }
}