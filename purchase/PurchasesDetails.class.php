<?php
/**
 * Клас 'purchase_PurchasesDetails'
 *
 * Детайли на мениджър на документи за покупка на продукти (@see purchase_Requests)
 *
 * @category  bgerp
 * @package   purchase
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
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
    public $loadList = 'plg_RowTools, plg_Created, purchase_Wrapper, plg_RowNumbering, doc_plg_HidePrices, plg_SaveAndNew, plg_AlignDecimals2,Policy=purchase_PurchaseLastPricePolicy';
    
    
    /**
     * Активен таб на менюто
     */
    public $menuPage = 'Логистика:Покупки';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, purchase';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'purchase_RequestDetails';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, purchase';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, uomId, packQuantityDelivered, packQuantity, packPrice, discount, amount';
    
        
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';


    /**
     * Активен таб
     */
    public $currentTab = 'Покупки';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'price,amount,discount,packPrice';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('requestId', 'key(mvc=purchase_Purchases)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('classId', 'class(interface=cat_ProductAccRegIntf, select=title)', 'caption=Мениджър,silent,input=hidden');
        
        $this->FLD('productId', 'int', 'caption=Продукт,mandatory,silent', 'tdClass=large-field leftCol');
        $this->FLD('uomId', 'key(mvc=cat_UoM, select=shortName)', 'caption=Мярка,input=none');
        $this->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty)', 'caption=Мярка', 'tdClass=small-field');

        // Количество в основна мярка
        $this->FLD('quantity', 'double(Min=0)', 'caption=Количество,input=none', 'tdClass=small-field');
        $this->FLD('quantityDelivered', 'double', 'caption=К-во->Доставено,input=none'); // Сумата на доставената стока
        $this->FNC('packQuantityDelivered', 'double(minDecimals=0)', 'caption=Дост.,input=none'); // Сумата на доставената стока
        
        // Количество (в осн. мярка) в опаковката, зададена от 'packagingId'; Ако 'packagingId'
        // няма стойност, приема се за единица.
        $this->FLD('quantityInPack', 'double(smartRound)', 'input=none');
        
        // Цена за единица продукт в основна мярка
        $this->FLD('price', 'double(decimals=2)', 'caption=Цена,input=none');
        $this->FNC('amount', 'double(minDecimals=2,maxDecimals=2)', 'caption=Сума');
        
        // Брой опаковки (ако има packagingId) или к-во в основна мярка (ако няма packagingId)
        $this->FNC('packQuantity', 'double', 'caption=К-во,input=input,mandatory');
        
        // Цена за опаковка (ако има packagingId) или за единица в основна мярка (ако няма packagingId)
        $this->FNC('packPrice', 'double(minDecimals=2)', 'caption=Цена,input=input');
        
        $this->FLD('discount', 'percent(min=-1,max=1)', 'caption=Отстъпка');
    }
}