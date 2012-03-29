<?php



/**
 * Мениджър на продажби - детайл
 *
 *
 * @category  all
 * @package   acc
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_SaleDetails extends core_Detail
{
    
    
    /**
     * Кой линк от главното меню на страницата да бъде засветен?
     */
    var $menuPage = 'Счетоводство';
    
    
    /**
     * Заглавие
     */
    var $title = 'Продажби';
    
    
    /**
     * @todo Чака за документация...
     */
    var $currentTab = 'acc_Sales';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'saleId';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_SaveAndNew, 
                    acc_Wrapper, Items=acc_Items';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,acc,broker,designer';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin,acc';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin,acc,broker,designer';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin,acc,broker,designer';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,acc';
    
    
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 300;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'productId,unitId,orderedQuantity,deliveredQuantity,price,discount,
        regularDiscount,state,tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
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
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('saleId', 'key(mvc=acc_Sales)', 'input=hidden,column=none,silent,mandatory');    // мастер ид
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
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm(acc_SaleDetails $mvc, $data)
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
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    static function on_AfterInputEditForm(acc_SaleDetails $mvc, core_Form $form)
    {
        $mvc->evalRegularDiscount($form->rec);
    }
    
    
    /**
     * @todo Чака за документация...
     */
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