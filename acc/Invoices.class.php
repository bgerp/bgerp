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
    var $loadList = 'plg_RowTools, plg_Created, acc_Wrapper, plg_Sorting, plg_Rejected,
                     InvoiceDetails=acc_InvoiceDetails';
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'number, tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    var $details = array('acc_InvoiceDetails');
    
    
    var $canRead = 'admin, acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin, acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin, acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin, acc';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        // $this->FLD("number", "int"); Уникален номер, инкрементално нараства
        $this->FLD('number', 'int', 'caption=Номер, notnull, input=none');
        
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
        
        // $this->FLD("currency", "string(3)"); mvc=common_Currencies по-подразбиране е основната валута
        // ако няма такава деф. конст трябва да дефинираме
        $this->FLD('currency', 'key(mvc=common_Currencies, select=code)', 'caption=Валута');
        
        /* ако не се въведе да взема курса към датата на фактурата */
        // $this->FLD("curencyRate", "number");
        $this->FLD('curencyRate', 'double(decimals=2)', 'caption=Курс');
        $this->FLD('paymentMethod', 'key(mvc=common_PaymentMethodsNew, select=name)', 'caption=Начин на плащане');
        
        // $this->FLD("delivery", "string(16)"); mvc=common_DeliveryTerm 
        $this->FLD('delivery', 'varchar(255)', 'caption=Начин на доставка');
        
        /* перо от номенклатурата банкови с-ки */
        // $this->FLD("account", "int");
        $this->FLD('account', 'varchar(64)', 'caption=Номер на банкова сметка');
        
        // $this->FLD("factoringAccount", "text");
        /* $this->FLD('factoringAccount', 'varchar(255)', 'caption=Сметка за фактуриране'); */
        
        // $this->FLD("additionalInfo", "text");
        $this->FLD('additionalInfo', 'text', 'caption=Допълнителна информация');
        // $this->FLD("createdOn", "datetime");
        // $this->FLD("createdBy", "key(mvc=Users)" );
        // $this->FLD("rejectedOn", "datetime");
        // $this->FLD("rejectedBy", "key(mvc=Users)" );
        
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
     * Рендираме общия изглед за 'List'
     * 
     * @param stdClass $data
     * @return core_Et $tpl
     */
    function renderSingle_($data)
    {
        // Рендираме общия лейаут
        $tpl = $this->renderSingleLayout($data);
        
        // Поставяме данните от реда
        $tpl->placeObject($data->row);
        
        // Поставя титлата
        $tpl->replace($this->renderSingleTitle($data), 'SingleTitle');
        
        // Поставяме toolbar-а
        $tpl->replace($this->renderSingleToolbar($data), 'SingleToolbar');
        
        return $tpl;
    }
        
    
    /**
     * @param stdClass $data
     * @return core_Et $res
     */
    function renderSingleLayout_($data)
    {
        $res = new ET("[#SingleToolbar#]<h2>[#SingleTitle#]</h2>");
        
        // Prepare HTML for invoice template
        $invoiceHeaderTpl       = cls::get('acc_tpl_ViewSingleInvoiceHeader', array('data' => $data));
        $invoiceProductLinesTpl = $this->getInvoiceProductsLinesRendered($mvc, $data);
        $invoiceFooterTpl       = cls::get('acc_tpl_ViewSingleInvoiceFooter', array('data' => $data));
        
        // totalSum
        $totalSum = number_format($data->rec->totalSum, 2, ', ', '');
        $invoiceFooterTpl->replace($totalSum, 'totalSum');
        
        // ДДС
        $dds = $data->rec->totalSum * 0.20;
        $dds = number_format($dds, 2, ', ', '');
        $invoiceFooterTpl->replace($dds, 'dds');
        
        // totalSumPlusDds
        $totalSumPlusDds = $data->rec->totalSum * 1.20;
        $totalSumPlusDds = number_format($totalSumPlusDds, 2, ', ', '');
        $invoiceFooterTpl->replace($totalSumPlusDds, 'totalSumPlusDds');
        
        // append HTML blocks to $res
        $res->append($invoiceHeaderTpl);
        $res->append($invoiceProductLinesTpl);
        $res->append($invoiceFooterTpl);
                
        return $res;
    }
    
    
    /**
     *  Генерира HTML с редовете на продултите за фактурата 
     * 
     * @param $mvc core_Mvc
     * @param $data stdClass
     * @return core_Et
     */
    function getInvoiceProductsLinesRendered($mvc, $data)
    {
        $InvoiceDetails = cls::get('acc_InvoiceDetails');
        $queryInvoiceDetails = $InvoiceDetails->getQuery();
        
        // Брояч на редовете
        $rowId = 0;
        
        $where = "#invoiceId = {$data->rec->id}";
        
        while($recInvoiceDetails = $queryInvoiceDetails->fetch($where)) {
            $rowId += 1;
            
            // product
            $Products = cls::get('cat_Products');
            $productTitle = $Products->fetchField("#id = {$recInvoiceDetails->productId}", 'title');
            
            // unit
            $Units = cls::get('common_Units');
            $unitName = $Units->fetchField("#id = {$recInvoiceDetails->unit}", 'name');
            
            // priceForOne
            $priceForOne =  number_format($recInvoiceDetails->priceForOne, 2, ',', ' ');
            
            // SUM price for a product
            $sumPrice = number_format($recInvoiceDetails->quantity * $recInvoiceDetails->priceForOne, 2, ',', ' ');
            
            // totalSum
            $data->rec->totalSum += $recInvoiceDetails->quantity * $recInvoiceDetails->priceForOne;
            
            $html .= "
                    <tr>
                        <td class=\"cell\" nowrap=\"nowrap\" align=\"right\">"  . $rowId . "</td>
                        <td class=\"cell\" align=\"left\">"                     . $productTitle . "</td>
                        <td class=\"cell\" nowrap=\"nowrap\" align=\"center\">" . $unitName . "</td>
                        <td class=\"cell\" nowrap=\"nowrap\" align=\"right\">"  . $recInvoiceDetails->quantity . "</td>
                        <td class=\"cell\" nowrap=\"nowrap\" align=\"right\">"  . $priceForOne . "</td>
                        <td class=\"cell\" nowrap=\"nowrap\" align=\"right\">"  . $sumPrice . "</td>
                    </tr>";
        }
        
        return new ET($html);
    }
        
}