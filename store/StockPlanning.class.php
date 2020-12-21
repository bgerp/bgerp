<?php


/**
 * Клас 'store_StockPlanning' за хоризонти на планиране
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_StockPlanning extends core_Manager
{


    /**
     * Заглавие
     */
    public $title = 'Хоризонти';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2,plg_Created, store_Wrapper, plg_StyleNumbers, plg_Sorting, plg_AlignDecimals2';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId,storeId,date,quantityIn,quantityOut,sourceId=Източник,createdOn';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,tdClass=leftAlign');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,tdClass=storeCol leftAlign');
        $this->FLD('date', 'datetime', 'caption=Дата');
        $this->FLD('quantityIn', 'double(maxDecimals=3)', 'caption=Количество->Влиза');
        $this->FLD('quantityOut', 'double(maxDecimals=3)', 'caption=Количество->Излиза');
        $this->FLD('sourceClassId', 'class', 'caption=Източник->Клас');
        $this->FLD('sourceId', 'int', 'caption=Източник->Ид');

        $this->setDbIndex('productId,storeId');
        $this->setDbIndex('sourceClassId,sourceId');
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
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
        $row->storeId = store_Stores::getHyperlink($rec->storeId, true);

        $Source = cls::get($rec->sourceClassId);
        $row->sourceId = $Source->hasPlugin('doc_DocumentPlg') ? $Source->getLink($rec->sourceId, 0) : $Source->getHyperlink($rec->sourceId, true);
    }


    /**
     * Премахва всички записи от дадения документ
     *
     * @param $class
     * @param $id
     * @return void
     */
    public static function remove($class, $id)
    {
        $Class = cls::get($class);
        self::delete("#sourceClassId = {$Class->getClassId()} AND #sourceId = {$id}");
    }
}

