<?php


/**
 * Клас 'purchase_PurchasesDetails'
 *
 * Детайли на мениджър на документи за покупка на продукти (@see purchase_Requests)
 *
 * @category  bgerp
 * @package   purchase
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class purchase_PurchasesDetails extends deals_DealDetail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на покупки';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'requestId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, purchase_Wrapper,deals_plg_ImportDealDetailProduct, cat_plg_LogPackUsage, plg_Sorting, plg_RowNumbering,acc_plg_ExpenseAllocation, doc_plg_HidePrices, plg_SaveAndNew, plg_AlignDecimals2,Policy=purchase_PurchaseLastPricePolicy,
                                                    cat_plg_CreateProductFromDocument,doc_plg_HideMeasureAndQuantityColumns,cat_plg_ShowCodes, plg_PrevAndNext';
    
    
    /**
     * Активен таб на менюто
     */
    public $menuPage = 'Логистика:Покупки';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, purchase';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, purchase, partner';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, purchase, partner';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'user';
    
    /**
     * Кой може да го импортира артикули?
     *
     * @var string|array
     */
    public $canImport = 'user';
    
    
    /**
     * Кой има право да променя?
     *
     * @var string|array
     */
    public $canImportlisted = 'user';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, packQuantity=К-во, packPrice, discount=Отст., amount';


    /**
     * Полета за скриване/показване от шаблоните
     */
    public $toggleFields = 'packagingId=Опаковка,packQuantity=К-во,packPrice=Цена,discount=Отст.,amount=Сума';


    /**
     * Активен таб
     */
    public $currentTab = 'Покупки';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'price,amount,discount,packPrice';
    
    
    /**
     * Какви мета данни да изискват продуктите, които да се показват
     */
    public $metaProducts = 'canBuy';
    
    
    /**
     * Филтър на прототипи по свойство
     */
    public $filterProtoByMeta = 'canBuy';
    
    
    /**
     * Какво движение на партида поражда документа в склада
     *
     * @param out|in|stay - тип движение (излиза, влиза, стои)
     */
    public $batchMovementDocument = 'in';
    
    
    /**
     * Да се показва ли вашия номер
     */
    public $showReffCode = true;


    /**
     * Кой може клонира артикулите от оригиналния клониран договор?
     *
     * @var string|array
     */
    public $canCopydetailsfromcloned = 'ceo, purchase';


    /**
     * Дали се позволява да се въвежда цена за к-то
     */
    public $allowInputPriceForQuantity = true;


    /**
     * Дали при импорт да се обединяват редовете с еднакви стойности
     */
    public $combineSameRecsWhenImport = true;


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('requestId', 'key(mvc=purchase_Purchases)', 'column=none,notNull,silent,hidden,mandatory');
        $this->setDbIndex('requestId');

        parent::getDealDetailFields($this);
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;
        $masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
        if (isset($rec->productId)) {
            $form->info = purchase_PurchasesData::getLastPurchaseFormInfo($rec->productId, $masterRec->valior, $masterRec->chargeVat, $masterRec->currencyRate, $masterRec->currencyId);
        }

        parent::inputDocForm($mvc, $form);
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'add') && isset($rec)) {
            if ($requiredRoles != 'no_one') {
                $roles = purchase_Setup::get('ADD_BY_PRODUCT_BTN');
                if (!haveRole($roles, $userId)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if ($action == 'importlisted') {
            $roles = purchase_Setup::get('ADD_BY_LIST_BTN');
            if (!haveRole($roles, $userId)) {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Извиква се преди подготовката на колоните
     */
    protected static function on_BeforePrepareListFields($mvc, &$res, $data)
    {
        $data->showCodeColumn = purchase_Setup::get('SHOW_CODE_IN_SEPARATE_COLUMN') == 'yes';
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $rows = &$data->rows;

        if (!countR($data->recs)) return;
        $masterRec = $data->masterData->rec;

        foreach ($rows as $id => $row) {
            $rec = $data->recs[$id];

            $row->discount = deals_Helper::getDiscountRow($rec->discount, $rec->inputDiscount, $rec->autoDiscount, $masterRec->state);
        }
    }


    /**
     * Изпълнява се преди клониране на детайла
     */
    protected static function on_BeforeSaveClonedDetail($mvc, &$rec, $oldRec)
    {
        $rec->discount = $oldRec->inputDiscount;
    }
}
