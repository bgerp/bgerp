<?php


/**
 * Kеширани цени
 *
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     История с кеширани цени
 */
class price_Cache extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Кеширани цени';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Кеширана цена';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, price_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, listId, productId, price,createdOn,createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Кой може да го прочете?
     */
    public $canRead = 'ceo';
    
    
    /**
     * Кой може да го промени?
     */
    public $canWrite = 'ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin,debug';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'priceMaster,ceo';
    
    
    /**
     * Масив с кеш на изчислените стойности
     */
    protected static $cache = array();
    
    
    /**
     * Db engine
     */
    public $dbEngine = 'MEMORY';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('listId', 'key(mvc=price_Lists,select=title)', 'caption=Ценоразпис, autoFilter');
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,allowEmpty,selectSource=price_ListRules::getSellableProducts)', 'caption=Продукт,mandatory,silent, autoFilter');
        $this->FLD('price', 'double(decimals=5)', 'caption=Цена');
        
        $this->setDbUnique('listId,productId');
    }
    
    
    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $form = $data->listFilter;
        
        // В хоризонтален вид
        $form->view = 'horizontal';
        
        // Добавяме бутон
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета
        // на модела ще се появят
        $form->showFields = 'listId, productId';
        
        $form->fields['productId']->mandatory = false;
        
        $form->input('listId, productId', 'silent');
        
        if ($form->rec->listId) {
            $data->query->where(array("#listId = '{$form->rec->listId}'"));
        }
        
        if ($form->rec->productId) {
            $data->query->where(array("#productId = '{$form->rec->productId}'"));
        }
    }
    
    
    /**
     * Връща кешираната цена за продукта
     */
    public static function getPrice($listId, $productId, $packagingId = null)
    {
        $cond = "#listId = {$listId} AND #productId = {$productId}";
        
        $price = self::fetchField($cond, 'price');
        
        return $price;
    }
    
    
    /**
     * Записва кеш за цената на продукта
     */
    public static function setPrice($price, $listId, $productId)
    {
        $rec = new stdClass();
        $rec->listId = $listId;
        $rec->productId = $productId;
        $rec->price = $price;
        self::save($rec, null, 'REPLACE');
        
        return $rec;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if (haveRole('admin,debug')) {
            $data->toolbar->addBtn('Изтриване', array($mvc, 'Truncate', 'ret_url' => true), 'ef_icon=img/16/sport_shuttlecock.png, title=Премахване на кешираните записи');
        }
    }
    
    
    /**
     * Изтриване на всички цени за посочената политика, както и тези от дъщерните й политики
     */
    public static function callback_InvalidatePriceList($priceListId)
    {
        self::delete("#listId = {$priceListId}");
        
        $plQuery = price_Lists::getQuery();
        while ($plRec = $plQuery->fetch("#parent = {$priceListId}")) {
            self::callback_InvalidatePriceList($plRec->id);
        }
    }
    
    
    /**
     * Изтриване на всички цени за посочения продукт
     */
    public static function invalidateProduct($productId)
    {
        self::delete("#productId = {$productId}");
    }
    
    
    /**
     * Изтрива цените, които са над 24 часа
     */
    public static function cron_RemoveExpiredPrices()
    {
        $before24h = dt::addSecs(-24 * 60 * 60);
        self::delete("#createdOn < '{$before24h}'");
    }
    
    
    /**
     * Екшън за изтриване на всички кеширани цени
     */
    public function act_Truncate()
    {
        requireRole('admin,debug');
        
        self::truncate();
        core_Statuses::newStatus('Кешираните цени са изтрити');
        
        followRetUrl();
    }
}
