<?php


/**
 * Партидни наличности в палетния склад
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Milen Georgiev <milen2experta.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rack_ProductsByBatches extends batch_Items
{
    /**
     * Заглавие
     */
    public $title = 'Артикули в склада по партиди';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,rackSee';


    /**
     * Кой може да преизчислява кешираните количества?
     */
    public $canRecalccachecquantity = 'admin,debug,rackMaster';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';


    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'code=Код, productId, storeId, batch, measureId=Мярка, quantity=Количество->Налично,quantityOnPallets,quantityOnZones,quantityNotOnPallets,state';


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
        unset($this->loadList['batch_Wrapper']);
        $this->loadList['rack_Wrapper'] = 'rack_Wrapper';
        $this->loadList['plg_RowTools2'] = 'plg_RowTools2';
        parent::description();

        $this->FLD('quantityOnPallets', 'double(maxDecimals=2)', 'caption=Количество->На палети,input=hidden,smartCenter');
        $this->FLD('quantityOnZones', 'double(maxDecimals=2)', 'caption=Количество->В зони,input=hidden,smartCenter');
        $this->XPR('quantityNotOnPallets', 'double(maxDecimals=2)', '#quantity - IFNULL(#quantityOnPallets, 0)- IFNULL(#quantityOnZones, 0)', 'caption=Количество->На пода,input=hidden,smartCenter');
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        core_RowToolbar::createIfNotExists($row->_rowTools);
        if (rack_Movements::haveRightFor('add', (object)array('productId' => $rec->productId)) && $rec->quantityNotOnPallets > 0) {
            $measureId = cat_Products::fetchField($rec->productId, 'measureId');
            $link = ht::createLink('', array('rack_Movements', 'add', 'productId' => $rec->productId, 'batch' => $rec->batch, 'packagingId' => $measureId, 'movementType' => 'floor2rack', 'ret_url' => true), false, 'ef_icon=img/16/pallet1.png,title=Палетиране на партидата');
            $row->quantityNotOnPallets = "{$link} {$row->quantityNotOnPallets}";
        }

        $row->quantityNotOnPallets = ht::styleIfNegative($row->quantityNotOnPallets, $rec->quantityNotOnPallets);
    }


    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $rows = &$data->rows;

        if (countR($rows)) {
            foreach ($rows as $id => &$row) {
                $rec = $data->recs[$id];

                if ($rec->quantityOnPallets > 0) {
                    $row->quantityOnPallets = ht::createLink('', array('rack_Pallets', 'list', 'productId' => $rec->productId, 'search' => $rec->batch, 'ret_url' => true), false, 'ef_icon=img/16/google-search-icon.png,title=Показване на палетите с този артикул и партида') . '&nbsp;' . $row->quantityOnPallets;
                }
            }
        }
    }


    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $storeId = store_Stores::getCurrent('id');
        $data->listFilter->setDefault('store', $storeId);
        $data->query->orderBy('productId', 'ASC');
    }


    /**
     * Преизчислява наличността на палети за посочения продукт
     */
    public static function recalc($productId, $storeId, $batch)
    {
        $query = rack_Pallets::getQuery();
        $query->where(array("#productId = {$productId} AND #storeId = {$storeId} AND #batch = '[#1#]' AND #state != 'closed'", $batch));
        $query->XPR('sum', 'double', 'SUM(#quantity)');
        $query->show('sum,productId,storeId,batch');
        $sum = $query->fetch()->sum;
        $sum = ($sum) ? $sum : null;

        $bRec = static::fetch(array("#productId = {$productId} AND #storeId = {$storeId} AND #batch = '[#1#]'", $batch), 'id,quantityOnPallets');
        if($bRec){
            $bRec->quantityOnPallets = $sum;
            $bRec->state = 'active';
            static::save($bRec);

            return $bRec;
        }

        return null;
    }


    /**
     * Рекалкулира какво количество на партидата по зони
     *
     * @param int $productId
     * @param string $batch
     * @param int $storeId
     * @return void
     */
    public static function recalcQuantityOnZones($productId, $batch, $storeId)
    {
        $bItemRec = rack_ProductsByBatches::fetch(array("#productId = {$productId} AND #batch = '[#1#]' AND #storeId = {$storeId}", $batch));
        if(is_object($bItemRec)){
            $bItemRec->quantityOnZones = rack_ZoneDetails::calcProductQuantityOnZones($productId, $storeId, $batch);
            rack_ProductsByBatches::save($bItemRec, 'quantityOnZones');
        }
    }
}