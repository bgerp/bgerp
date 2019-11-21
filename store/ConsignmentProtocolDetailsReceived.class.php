<?php


/**
 * Клас 'store_ConsignmentProtocolDetailsReceived'
 *
 * Детайли на мениджър на детайлите на протоколите за отговорно пазене-върнати
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_ConsignmentProtocolDetailsReceived extends store_InternalDocumentDetail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на протоколите за отговорно пазене-върнати';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'върнат артикул';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'protocolId';
    
    
    /**
     * Плъгини за зареждане
     *
     * var string|array
     */
    public $loadList = 'plg_RowTools2, plg_Created, store_Wrapper, plg_RowNumbering, plg_SaveAndNew, 
                        plg_AlignDecimals2, LastPricePolicy=sales_SalesLastPricePolicy,deals_plg_ImportDealDetailProduct,plg_PrevAndNext,store_plg_TransportDataDetail';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, store, distributor';
    
    
    /**
     * Какви мета данни да изискват продуктите, които да се показват
     */
    public $metaProducts = 'canSell,canStore';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, store, distributor';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, store, distributor';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId=Получено от Клиент/Доставчик, packagingId, packQuantity, weight=Тегло,volume=Обем, packPrice, amount,transUnitId=ЛЕ';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'price, amount, discount, packPrice';
    
    
    /**
     * Кой може да го импортира артикули?
     *
     * @var string|array
     */
    public $canImport = 'ceo, store, distributor';
    
    
    /**
     * Да се забрани ли създаването на нова партида
     */
    public $cantCreateNewBatch = true;
    
    
    /**
     * Какво движение на партида поражда документа в склада
     *
     * @param out|in|stay - тип движение (излиза, влиза, стои)
     */
    public $batchMovementDocument = 'in';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('protocolId', 'key(mvc=store_ConsignmentProtocols)', 'column=none,notNull,silent,hidden,mandatory');
        parent::setFields($this);
        $this->setDbUnique('protocolId,productId,packagingId');
    }
}
