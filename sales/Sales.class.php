<?php
/**
 * Клас 'sales_Sales'
 *
 * Мениджър на документи за продажба на продукти от каталога
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Sales extends core_Master
{
    /**
     * Заглавие
     * 
     * @var string
     */
    var $title = 'Продажби';
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf';
    
    
    /**
     * Плъгини за зареждане
     * 
     * var string|array
     */
    var $loadList = 'plg_RowTools, sales_Wrapper, plg_Sorting,
                    doc_DocumentPlg, plg_ExportCsv,
					doc_EmailCreatePlg, doc_ActivatePlg, bgerp_plg_Blank, plg_Printing,
                    doc_SequencerPlg, doc_plg_BusinessDoc';
    
    
    /**
     * Активен таб на менюто
     * 
     * @var string
     */
    var $menuPage = 'Търговия:Продажби';
    
    /**
     * Кой има право да чете?
     * 
     * @var string|array
     */
    var $canRead = 'admin,sales';
    
    
    /**
     * Кой има право да променя?
     * 
     * @var string|array
     */
    var $canEdit = 'admin,sales';
    
    
    /**
     * Кой има право да добавя?
     * 
     * @var string|array
     */
    var $canAdd = 'admin,sales';
    
    
    /**
     * Кой може да го види?
     * 
     * @var string|array
     */
    var $canView = 'admin,sales';
    
    
    /**
     * Кой може да го изтрие?
     * 
     * @var string|array
     */
    var $canDelete = 'admin,sales';
    
    
    /**
     * Брой записи на страница
     * 
     * @var integer
     */
//     var $listItemsPerPage;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
//     var $listFields;
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     * 
     * @var string
     */
    var $rowToolsField;


    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    var $singleTitle = 'Документ за Продажба';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        
        $this->FLD('date', 'date', 'caption=Дата, mandatory');
        $this->FLD('pricesAtDate', 'date', 'caption=Цени към');
        $this->FLD('note', 'text', 'caption=Забележка', array('attr'=>array('rows'=>3)));
        $this->FLD('makeInvoice', 'enum(yes=Да,monthend=Периодично,no=Не)', 
            'caption=Фактуриране,maxRadio=4');
        
        /*
         * Стойности
         */
        $this->FLD('amountDeal', 'float', 'caption=Стойности->Продажба'); // Сумата на договорената стока
        $this->FLD('amountDelivered', 'float', 'caption=Стойности->Доставено'); // Сумата на доставената стока
        $this->FLD('amountPaid', 'float', 'caption=Стойности->Платено'); // Сумата която е платена
        
        /*
         * Контрагент
         */ 
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden');
        $this->FLD('contragentId', 'int', 'input=hidden');
        
        /*
         * Доставка
         */
        $this->FLD('deliveryTermId', 'key(mvc=trans_DeliveryTerms,select=name)', 
            'caption=Доставка->Условие');
        $this->FLD('deliveryLocationId', 'key(mvc=crm_Locations, select=title)', 
            'caption=Доставка->Обект до'); // обект, където да бъде доставено (allowEmpty)
        $this->FLD('deliveryTime', 'datetime', 
            'caption=Доставка->Срок до'); // до кога трябва да бъде доставено
        $this->FLD('shipmentStoreId', 'key(mvc=store_Stores,select=name,allowEmpty)', 
            'caption=Доставка->От склад'); // наш склад, от където се експедира стоката
        
        /*
         * Плащане
         */
        $this->FLD('paymentMethodId', 'key(mvc=bank_PaymentMethods,select=name)',
            'caption=Плащане->Начин,mandatory');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)',
            'caption=Плащане->Валута');
        $this->FLD('bankAccountId', 'key(mvc=bank_OwnAccounts,select=title,allowEmpty)',
            'caption=Плащане->Банкова сметка');
        $this->FLD('caseId', 'key(mvc=cash_Cases,select=name,allowEmpty)',
            'caption=Плащане->Каса');
    }


    /**
     * Извиква се преди изпълняването на екшън
     * 
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param string $action
     */
    static function on_BeforeAction($mvc, &$res, $action)
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
    static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    }
    
    
    /**
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareEditForm($mvc, &$data)
    {
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    static function on_AfterInputEditForm($mvc, &$form)
    {
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    }
    
    
    /**
     * 
     * @param int $id key(mvc=sales_Sales)
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
        expect($rec = $this->fetch($id));
        
        $row = (object)array(
            'title'    => "Продажба №{$rec->id}",
            'authorId' => $rec->createdBy,
            'author'   => $this->getVerbal($rec, 'createdBy'),
            'state'    => $rec->state,
            'recTitle' => "Продажба №{$rec->id}",
        );
        
        return $row;
    }
}
