<?php


/**
 * Зони в палетния склад
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Milen Georgiev <milen2experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rack_Products extends store_Products
{
    /**
     * Заглавие
     */
    public $title = 'Артикули в склада';
    
    
    /**
     * Плъгини за зареждане
     */
    //var $loadList = 'plg_Created, rack_Wrapper, plg_RowTools2';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,rack';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,rack';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,rack';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    public $listFields = 'code=Код,productId=Наименование, measureId=Мярка,quantity=Количество->Общо,quantityNotOnPallets,quantityOnPallets,storeId=Склад';
    
    
    /**
     * Задължително филтър по склад
     */
    protected $mandatoryStoreFilter = true;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->loadList = arr::make($this->loadList, true);
        unset($this->loadList['store_Wrapper']);
        $this->loadList['rack_Wrapper'] = 'rack_Wrapper';
        $this->loadList['plg_RowTools2'] = 'plg_RowTools2';
        parent::description();
        
        $this->FNC('quantityNotOnPallets', 'double', 'caption=Количество->Непалетирано,input=hidden,smartCenter');
        $this->FLD('quantityOnPallets', 'double', 'caption=Количество->На палети,input=hidden,smartCenter');
    }
    
    
    /**
     * Изчисляване на функционално поле
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     *
     * @return void|float
     */
    public static function on_CalcQuantityNotOnPallets(core_Mvc $mvc, $rec)
    {
        return $rec->quantityNotOnPallets = $rec->quantity - $rec->quantityOnPallets;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        core_RowToolbar::createIfNotExists($row->_rowTools);
        $row->_rowTools->addLink('Палетиране', array('rack_Pallets', 'add', 'productId' => $rec->id, 'ret_url' => true), 'ef_icon=img/16/pallet1.png,title=Палетиране на артикул');
        $row->_rowTools->addLink('Търсене', array('rack_Pallets', 'list', 'productId' => $rec->id, 'ret_url' => true), 'ef_icon=img/16/filter.png,title=Търсене на палети с артикул');
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     *
     * @param rack_Products $mvc
     * @param stdClass      $rec
     * @param array         $fields
     * @param NULL|string   $mode
     */
    public static function on_AfterSaveArray($mvc, $res, $recs)
    {
        foreach ($recs as $rec) {
            $rec = self::fetch("#productId = {$rec->productId} AND #storeId = {$rec->storeId}");
            if ($rec) {
                rack_Pallets::recalc($rec->id);
            }
        }
    }
    
    
    /**
     * Избягване скриването на бутоните в rowTools2
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListTitle($mvc, $data)
    {
        $data->masterMvc = true;
    }
}
