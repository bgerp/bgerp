<?php



/**
 * Фактури
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Invoices extends core_Master
{
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf';
    
    
    /**
     * Абревиатура
     */
    var $abbr = 'Inv';
    
    
    /**
     * Заглавие
     */
    var $title = 'Фактури за продажби';
    
    
    /**
     * @todo Чака за документация...
     */
    var $singleTitle = 'Фактура за продажба';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, sales_Wrapper, plg_Sorting, doc_DocumentPlg, plg_ExportCsv, 
                     doc_EmailCreatePlg, fax_FaxCreatePlg, doc_ActivatePlg, bgerp_plg_Blank, plg_Printing';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    var $onlyFirstInThread = TRUE;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'number, vatDate, account ';
    
    
     
    /**
     * Детайла, на модела
     */
    var $details = 'sales_InvoiceDetails' ;
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, sales';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin, sales';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, sales';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin, sales';
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'sales/tpl/SingleLayoutInvoice.shtml';

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
        $this->FLD('contragentName', 'varchar(255)', 'caption=Контрагент->Име');
        
        // $this->FLD("contragentCountry", "string(64)");
        $this->FLD('contragentCountry', 'key(mvc=drdata_Countries,select=commonName)', 'caption=Контрагент->Държава,mandatory');
        
        // $this->FLD("contragentAddress", "string(128)");
        $this->FLD('contragentAddress', 'varchar(255)', 'caption=Контрагент->Адрес');
        $this->FLD('contragentVatId', 'varchar(255)', 'caption=Контрагент->Vat Id');
        
        // $this->FLD("vatCanonized", "string(32)"); да се мине през функцията за канонизиране от drdata_Vats 
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
        
        // $this->FLD("delivery", "string(16)"); mvc=trans_DeliveryTerm 
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
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
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
    static function on_BeforeSave($mvc, &$id, $rec)
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
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща данните за адресанта
     */
    static function getContragentData($id)
    {
        //TODO не може да се вземат всичките данни, защото класа не е завършен напълно
        $rec = sales_Invoices::fetch($id);
        
        $contrData = new stdClass();
        $contrData->company = sales_Invoices::getVerbal($rec, 'contragentId');;
        $contrData->name = $rec->contragentName;
        
        //        $contrData->tel = $rec->tel;
        //        $contrData->fax = $rec->fax;
        $contrData->country = sales_Invoices::getVerbal($rec, 'contragentCountry');
        
        //        $contrData->pcode = $rec->pcode;
        //        $contrData->place = $rec->place;
        $contrData->address = $rec->contragentAddress;
        
        //        $contrData->email = $rec->email;
        
        return $contrData;
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото наимей по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = sales_Invoices::getHandle($id);
        
        //Създаваме шаблона
        $tpl = new ET(tr("Моля запознайте се с приложената фактура:") . "\n[#handle#]");
        
        //Заместваме датата в шаблона
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row->title = $this->getHandle($rec->id);   //TODO може да се премени
        //        $row->title = $this->getVerbal($rec, 'contragentId');
        
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        $row->authorId = $rec->createdBy;
        
        $row->state = $rec->state;
        
        return $row;
    }
}
