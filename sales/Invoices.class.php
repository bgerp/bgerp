<?php

/**
 * Фактури
 */
class sales_Invoices extends core_Master
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Фактури";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, plg_Created, sales_Wrapper, plg_Sorting, plg_State, plg_Rejected,
                     InvoiceDetails=sales_InvoiceDetails, plg_ExportCsv';
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'number, vatDate, account, tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    var $details =  'sales_InvoiceDetails' ;
    
    
    var $canRead = 'admin, sales';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin, sales';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin, sales';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin, sales';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        // $this->FLD("number", "int"); Уникален номер, инкрементално нараства
        $this->FLD('number', 'int', 'caption=Номер, notnull, input=none, export=Csv');
        
        $this->FLD('date', 'date', 'caption=Дата,  notNull, mandatory');
        
        // $this->FLD("contragentId", "int"); mvc=crm_Companies 
        $this->FLD('contragentId', 'int', 'caption=Номер на контрагента, mandatory');
        
        /* Повторение на данните за фирмата с възможности за модифициране */
        // $this->FLD("contragentName", "string(64)"); 
        $this->FLD('contragentName',    'varchar(255)', 'caption=Контрагент->Име');
        // $this->FLD("contragentCountry", "string(64)");
        $this->FLD('contragentCountry', 'key(mvc=drdata_Countries,select=commonName)', 'caption=Контрагент->Държава,mandatory');
        // $this->FLD("contragentAddress", "string(128)");
        $this->FLD('contragentAddress', 'varchar(255)', 'caption=Контрагент->Адрес');
        $this->FLD('contragentVatId',   'varchar(255)', 'caption=Контрагент->Vat Id');
        
        // $this->FLD("vatCanonized", "string(32)"); да се мине през функцията за канонизиране от common_Vats 
        $this->FLD('vatCanonized', 'varchar(255)', 'caption=Vat Canonized, input=none');
        $this->FLD('dealPlace', 'varchar(255)', 'caption=Място на сделката');
        $this->FLD('dealValue', 'double(decimals=2)', 'caption=Стойност, input=none');
        $this->FLD('vatRate', 'double(decimals=2)', 'caption=ДДС');
        
        // $this->FLD("vatReason", "string(128)"); plg_Resent
        $this->FLD('vatReason', 'varchar(255)', 'caption=Данъчно основание');
        
        // $this->FLD("creatorName", "string(64)"); 
        /* $this->FLD('creatorName', 'varchar(255)', 'caption=Съставил'); */
        
        /* Кога е дан. събитие. Ако не се въведе е датата на фактурата */
        // $this->FLD("vatDate", "date");
        $this->FLD('vatDate', 'date', 'caption=Данъчна дата');
        
        // $this->FLD("currency", "string(3)"); mvc=currency_Currencies по-подразбиране е основната валута
        // ако няма такава деф. конст трябва да дефинираме
        $this->FLD('currency', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута');
        
        /* ако не се въведе да взема курса към датата на фактурата */
        // $this->FLD("curencyRate", "number");
        $this->FLD('curencyRate', 'double(decimals=2)', 'caption=Курс');
        $this->FLD('paymentMethod', 'key(mvc=bank_PaymentMethods, select=name)', 'caption=Начин на плащане');
        
        // $this->FLD("delivery", "string(16)"); mvc=common_DeliveryTerm 
        $this->FLD('delivery', 'varchar(255)', 'caption=Начин на доставка');
        
        /* перо от номенклатурата банкови с-ки */
        // $this->FLD("account", "int");
        $this->FLD('account', 'varchar(64)', 'caption=Номер на банкова сметка, export=Csv');
        
        // $this->FLD("factoringAccount", "text");
        /* $this->FLD('factoringAccount', 'varchar(255)', 'caption=Сметка за фактуриране'); */
        
        // $this->FLD("additionalInfo", "text");
        $this->FLD('additionalInfo', 'text', 'caption=Допълнителна информация');
        // $this->FLD("createdOn", "datetime");
        // $this->FLD("createdBy", "key(mvc=Users)" );
         
        $this->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 'caption=Статус, input=none');
        
        // $this->FLD("type", "enum(invoice=Чернова, credit_note=Кредитно известие, debit_note=Дебитно известие)" );
        $this->FLD('type', 'enum(invoice=Чернова, credit_note=Кредитно известие, debit_note=Дебитно известие)', 'caption=Вид, input=none');
        
        // $this->FLD("noteReason", "int");
        /* $this->FLD('noteReason', 'varchar(255)', 'caption=Основание'); */
        
        // $this->FLD("saleId", "key(mvc=Sales)");
        /* ? */// $this->FLD('saleId', 'key(mvc=sales_Sales,select=title)', 'caption=Продажба');
        // $this->FLD("paid", "int");
        
        /* $this->FLD('paid', 'int', 'caption=Платено'); */
        // $this->FLD("paidAmount", "number");
        /* $this->FLD('paidAmount', 'double(decimals=2)', 'caption=Сума'); */
    }
    
    
    /**
     * Преди извличане на записите филтър по number
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('#number', 'DESC');
    }    

    
    /**
     * При добавяне слага пореден номер   
     * 
     * @param core_Mvc $mvc
     * @param int $id
     * @param stdClass $rec
     */    
    function on_BeforeSave($mvc, &$id, $rec)
    {
        if ($rec->number === NULL) {
            $query = $mvc->getQuery();
            $where = "1=1";
            $query->limit(1);
            $query->orderBy('number', 'DESC');        
    
            while($recInvoices = $query->fetch($where)) {
                $lastNumber = $recInvoices->number;
            }

           $rec->number = $lastNumber + 1;
        }
    }
    
    
    /**
     * @param stdClass $data
     * @return core_Et $res
     */
    function renderSingleLayout_($data)
    {
        $viewSingle = cls::get('sales_tpl_ViewSingleLayoutInvoice', array('data' => $data));
        
        return $viewSingle;
    }

}