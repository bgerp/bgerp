<?php 

/**
 * Invoice (Details)
 */
class acc_InvoiceDetails extends core_Detail
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Детайли на фактурата";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = "Фактури";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_Printing, acc_Wrapper, plg_Sorting, 
                     Invoices=acc_Invoices';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $masterKey = 'invoiceId';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = '';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $tabName = "acc_Invoices";
    
    
    /**
     * Права
     */
    var $canWrite = 'acc, admin';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canRead = 'acc, admin';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('invoiceId',  'key(mvc=acc_Invoices)', 'caption=Поръчка, input=hidden, silent');
        $this->FLD('actionType', 'enum(sale,downpayment,deduct,discount)', 'caption=Тип');
        $this->FLD('invPeraId',  'int', 'caption=Пера');
        $this->FLD('orderId',    'int', 'caption=Поръчка');
        $this->FLD('note',       'text', 'caption=Пояснение');
        $this->FLD('productId',  'key(mvc=cat_Products, select=title)', 'caption=Продукт');
        $this->FLD('unit',       'key(mvc=common_Units, select=name)', 'caption=Мярка');
        $this->FLD('quantity',   'int', 'caption=Количество');
        $this->FLD('priceForOne',      'double(decimals=2)', 'caption=Ед. цена');
        
        $this->setDbUnique('invoiceId, productId');
    }

}