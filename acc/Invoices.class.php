<?php

/**
 * Фактури
 */
class acc_Invoices extends core_Master
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Фактури";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, plg_Created, acc_Wrapper, plg_Sorting,
                             plg_Rejected';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id, tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * var $details          = array('acc_InvoiceDetails');
     */
    var $canRead = 'admin, catering';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin, catering';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin, catering';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin, catering';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        // $this->FLD("number", "int"); Уникален номер, инкрементално нараства
        $this->FLD('number', 'int', 'caption=Номер, notnull');
        
        // $this->FLD("date", "date"); 
        $this->FLD('date', 'date', 'caption=Дата,  notNull, mandatory');
        
        // $this->FLD("contragentId", "int"); mvc=crm_Companies 
        $this->FLD('contragentId', 'int', 'caption=Номер на контрагента, mandatory');
        
        /* Повторение на данните за фирмата с възможности за модифициране */
        // $this->FLD("contragentName", "string(64)"); 
        $this->FLD('contragentName', 'varchar(255)', 'caption=Контрагент->име');
        // $this->FLD("contragentCountry", "string(64)");
        $this->FLD('contragentCountry', 'varchar(255)', 'caption=Държава на контрагента');
        // $this->FLD("contragentAddress", "string(128)");
        $this->FLD('contragentAddress', 'varchar(255)', 'caption=Адрес на контрагента');
        // $this->FLD("contragentVatId", "string(32)");
        $this->FLD('contragentVatId', 'varchar(255)', 'caption=Vat Id');
        
        // $this->FLD("vatCanonized", "string(32)"); да се мине през функцията за канонизиране от common_Vats 
        $this->FLD('vatCanonized', 'varchar(255)', 'caption=Vat Canonized, input=none');
        
        // $this->FLD("dealPlace", "string(128)");
        $this->FLD('dealPlace', 'varchar(255)', 'caption=Място на сделката');
        
        // $this->FLD("dealValue", "number");
        $this->FLD('dealValue', 'double(decimals=2)', 'caption=Стойност, input=none');
        
        // $this->FLD("vatRate", "number");
        $this->FLD('vatRate', 'double(decimals=2)', 'caption=ДДС');
        
        // $this->FLD("vatReason", "string(128)"); plg_Resent
        $this->FLD('vatReason', 'varchar(255)', 'caption=Данъчно основание');
        
        // $this->FLD("creatorName", "string(64)"); 
        /* $this->FLD('creatorName', 'varchar(255)', 'caption=Съставил'); */
        
        /* Кога е дан. събитие. Ако не се въведе е датата на фактурата */
        // $this->FLD("vatDate", "date");
        $this->FLD('vatDate', 'date', 'caption=Данъчна дата');
        
        // $this->FLD("currency", "string(3)"); mvc=common_Currencies по-подразбиране е основната валута
        // ако няма такава деф. конст трябва да дефинираме
        $this->FLD('currency', 'varchar(3)', 'caption=Валута');
        
        /* ако не се въведе да взема курса към датата на фактурата */
        // $this->FLD("curencyRate", "number");
        $this->FLD('curencyRate', 'double(decimals=2)', 'caption=Курс');
        
        // $this->FLD("paymentMethod", "string(16)"); mvc=common_PaymentMethods
        $this->FLD('paymentMethod', 'varchar(255)', 'caption=Начин на плащане');
        
        // $this->FLD("delivery", "string(16)"); mvc=common_DeliveryTerm 
        $this->FLD('delivery', 'varchar(255)', 'caption=Начин на доставка');
        
        /* перо от номенклатурата банкови с-ки */
        // $this->FLD("account", "int");
        $this->FLD('account', 'int', 'caption=Номер на банкова сметка');
        
        // $this->FLD("factoringAccount", "text");
        /* $this->FLD('factoringAccount', 'varchar(255)', 'caption=Сметка за фактуриране'); */
        
        // $this->FLD("additionalInfo", "text");
        $this->FLD('additionalInfo', 'text', 'caption=Допълнителна информация');
        // $this->FLD("createdOn", "datetime");
        // $this->FLD("createdBy", "key(mvc=Users)" );
        // $this->FLD("rejectedOn", "datetime");
        // $this->FLD("rejectedBy", "key(mvc=Users)" );
        
        /* plg_State */
        // $this->FLD("state", "enum(draft=Чернова, active=Контиран, rejected=Сторнирана)" );
        $this->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 'caption=Статус, input=none');
        
        // $this->FLD("type", "enum(invoice=Чернова, credit_note=Кредитно известие, debit_note=Дебитно известие)" );
        $this->FLD('type', 'enum(invoice=Чернова, credit_note=Кредитно известие, debit_note=Дебитно известие)', 'caption=Вид, input=none');
        
        // $this->FLD("noteReason", "int");
        /* $this->FLD('noteReason', 'varchar(255)', 'caption=Основание'); */
        
        // $this->FLD("saleId", "key(mvc=Sales)");
        /* ? */// $this->FLD('saleId', 'key(mvc=acc_Sales,select=title)', 'caption=Продажба');
        // $this->FLD("paid", "int");
        
        /* $this->FLD('paid', 'int', 'caption=Платено'); */
        // $this->FLD("paidAmount", "number");
        /* $this->FLD('paidAmount', 'double(decimals=2)', 'caption=Сума'); */
    }
}