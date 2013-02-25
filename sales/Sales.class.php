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
    public $title = 'Продажби';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf,
                          acc_RegisterIntf=sales_RegisterImpl,
                          acc_TransactionSourceIntf=sales_TransactionSourceImpl';
    
    
    /**
     * Плъгини за зареждане
     * 
     * var string|array
     */
    public $loadList = 'plg_RowTools, sales_Wrapper, plg_Sorting, plg_Printing,
                    doc_DocumentPlg, plg_ExportCsv,
					doc_EmailCreatePlg, doc_ActivatePlg, bgerp_plg_Blank,
                    doc_plg_BusinessDoc, acc_plg_Registry, acc_plg_Contable';
    
    
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
    public $canRead = 'admin,sales';
    
    
    /**
     * Кой има право да променя?
     * 
     * @var string|array
     */
    public $canEdit = 'admin,sales';
    
    
    /**
     * Кой има право да добавя?
     * 
     * @var string|array
     */
    public $canAdd = 'admin,sales';
    
    
    /**
     * Кой може да го види?
     * 
     * @var string|array
     */
    public $canView = 'admin,sales';
    
    
    /**
     * Кой може да го изтрие?
     * 
     * @var string|array
     */
    public $canDelete = 'admin,sales';
    
    
    /**
     * Брой записи на страница
     * 
     * @var integer
     */
     public $listItemsPerPage;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
     public $listFields;
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     * 
     * @var string
     */
    public $rowToolsField;


    /**
     * Детайла, на модела
     *
     * @var string|array
     */
    public $details = 'sales_SalesDetails' ;
    

    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'Документ за Продажба';
    
    
    /**
     * Списък от systemId-та на номеклатури, в които документа се добавя автоматично
     * 
     * @var string|array
     */
    public $autoList = 'sales';
    
    
    /**
     * 
     */
   var $singleLayoutFile = 'sales/tpl/SingleLayoutInvoiceSale.shtml';
   
    /**
     * Групиране на документите
     */ 
   var $newBtnGroup = "3.1|Търговия";
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        
        $this->FLD('date', 'date', 'caption=Дата, mandatory');
        $this->FLD('makeInvoice', 'enum(yes=Да,no=Не,monthend=Периодично)', 
            'caption=Фактуриране,maxRadio=3,columns=3');
        
        /*
         * Стойности
         */
        $this->FLD('amountDeal', 'float', 'caption=Стойности->Продажба,input=none'); // Сумата на договорената стока
        $this->FLD('amountDelivered', 'float', 'caption=Стойности->Доставено,input=none'); // Сумата на доставената стока
        $this->FLD('amountPaid', 'float', 'caption=Стойности->Платено,input=none'); // Сумата която е платена
        
        /*
         * Контрагент
         */ 
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden');
        $this->FLD('contragentId', 'int', 'input=hidden');
        
        /*
         * Доставка
         */
        $this->FLD('deliveryTermId', 'key(mvc=trans_DeliveryTerms,select=codeName)', 
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
        
        /*
         * Наш персонал
         */
        $this->FLD('initiatorId', 'user(roles=user,allowEmpty)',
            'caption=Наш персонал->Инициатор');
        $this->FLD('dealerId', 'user(roles=sales,allowEmpty)',
            'caption=Наш персонал->Търговец');

        /*
         * Допълнително
         */
        $this->FLD('pricesAtDate', 'date', 'caption=Допълнително->Цени към');
        $this->FLD('note', 'richtext', 'caption=Допълнително->Бележки', array('attr'=>array('rows'=>3)));
    	
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
            'caption=Статус, input=none'
        );
    }
    
    public static function on_AfterSave($mvc)
    {
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
        switch ($action) {
            /*
             * Контират се само документи (продажби) които генерират *непразни* транзакции.
             * Документите (продажбите), които не генерират счетоводни транзакции могат да се
             * активират.
             */
            case 'conto':
            case 'activate':
                if (empty($rec->id) || $rec->state != 'draft') {
                    // Незаписаните продажби не могат нито да се контират, нито да се активират
                    $requiredRoles = 'no_one';
                } else {
                    $transaction  = $mvc->getValidatedTransaction($rec);
                    
                    if ($transaction === FALSE) {
                        // Възникнала е грешка при генериране на транзакция
                        if ($action == 'activate') {
                            $requiredRoles = 'no_one';
                        }
                    } else {
                        // Активиране е позволено само за продажби, които не генерират транзакции
                        // Контиране е позволено само за продажби, които генерират транзакции
                        $deniedAction = ($transaction->isEmpty() ? 'conto' : 'activate');
                        
                        if ($action == $deniedAction) {
                            $requiredRoles = 'no_one';
                        }
                    }
                }
                break;
        }
    }
    
    
    public function getValidatedTransaction($rec)
    {
        try {
            $transactionSource = cls::getInterface('acc_TransactionSourceIntf', $this);
            $transaction       = $transactionSource->getTransaction($rec);
            
            if (!empty($transaction)) {
                // Проверяваме валидността на транзакцията
                $transaction = new acc_journal_Transaction($transaction);
                if (!$transaction->check()) {
                    return FALSE;
                }
            } 
        } catch (core_exception_Expect $ex) {
            // Транзакцията не се валидира
            $transaction = FALSE;
        }
        
        return $transaction;
    }
    
    function on_beforeRenderSingle($mvc, $res, $data)
    {
      //bp($data);
    }
    
    function on_afterRenderSingle($mvc, $tpl, $data)
    {
        $tpl->replace($data->row->state, 'stateText');
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
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Задаване на стойности на полетата на формата по подразбиране
        
        /* @var $form core_Form */
        $form = $data->form;
       
        $form->setDefault('date', dt::now());
        
        $form->setDefault('bankAccountId',bank_OwnAccounts::getCurrent('id', FALSE));
        $form->setDefault('caseId', cash_Cases::getCurrent('id', FALSE));
        $form->setDefault('shipmentStoreId', store_Stores::getCurrent('id', FALSE));
        
        if (empty($data->form->rec->dealerId)) {
            $form->setDefault('dealerId', $mvc::getDefaultDealer($data->form->rec));
        }
        
        if (empty($data->form->rec->currencyId)) {
            $form->setDefault('currencyId', $mvc::getDefaultCurrencyCode($data->form->rec));
        }
        
        $form->setDefault('makeInvoice', 'yes');
        
        if (empty($form->rec->folderId)) {
            expect($form->rec->folderId = core_Request::get('folderId', 'key(mvc=doc_Folders)'));
        } 
        
        $form->setDefault('contragentClassId', doc_Folders::fetchCoverClassId($form->rec->folderId));
        $form->setDefault('contragentId', doc_Folders::fetchCoverId($form->rec->folderId));
        
        // Поле за избор на локация - само локациите на контрагента по продажбата
        $form->getField('deliveryLocationId')->type->options = 
            array(''=>'') +
            crm_Locations::getContragentOptions($form->rec->contragentClassId, $form->rec->contragentId);
    }
    
    
    /**
     * Помощен метод за определяне на търговец по подразбиране.
     * 
     * Правило за определяне:
     * 
     *  1/ Отговорника на папката на контрагента, ако той има роля sales;
     *  2/ Текущият потребител, ако той има роля sales
     *  3/ иначе е празен (NULL)
     *
     * @param stdClass $rec запис на модела sales_Sales
     * @return int|NULL user(roles=sales)
     */
    public static function getDefaultDealer($rec)
    {
        expect($rec->folderId);
        
        $requiredRole   = 'sales';

        $inChargeUserId = doc_Folders::fetchField($rec->folderId, 'inCharge');
        if (core_Users::haveRole($requiredRole, $inChargeUserId)) {
            // Отговорника на папката има роля 'sales'
            return $inChargeUserId;
        }
        
        $currentUserId = core_Users::getCurrent('id');
        if (core_Users::haveRole($requiredRole, $currentUserId)) {
            return $currentUserId;
        }
        
        return NULL;
    }
    
    
    /**
     * Определяне на валутата по подразбиране при нова продажба.
     * 
     * @param stdClass $rec
     * @param string 3-буквен ISO код на валута (ISO 4217) 
     */
    public static function getDefaultCurrencyCode($rec)
    {
        $currencyBaseCode = core_Packs::getConfig('currency')->CURRENCY_BASE_CODE;
         
        return $currencyBaseCode;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
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
        $row->amountDeal = $row->currencyId . ' ' . sprintf('%0.2f', $rec->amountDeal);
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
