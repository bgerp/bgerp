<?php


/**
 * Базов клас за таблиците с индекса на параметрите на артикулите
 *
 * Таблиците са еднакви: вътрешната (@see cat_products_ParamIndex) има всички индексирани артикули,
 * а тази на е-магазина (@see cat_products_EshopParamIndex) - само ползваните в е-артикули
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class cat_products_ProtoParamIndex extends core_Manager
{
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Индексиран параметър';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'cat_Wrapper, plg_Sorting';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId,paramId,kind=Вид,lg,value=Стойност,valueNum,valueKey,valueId';


    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'valueNum,valueKey,valueId';


    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 100;


    /**
     * Кой може да листва
     */
    public $canList = 'debug';


    /**
     * Кой може да добавя
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да редактира
     */
    public $canEdit = 'no_one';


    /**
     * Кой може да изтрива
     */
    public $canDelete = 'no_one';


    /**
     * Описание на модела
     */
    public function description()
    {
        // Иначе framework-ът кръщава таблицата на този клас и наследниците пишат в една
        $this->dbTableName = EF_DB_TABLE_PREFIX . str::phpToMysqlName($this->className);

        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        $this->FLD('paramId', 'key(mvc=cat_Params,select=typeExt)', 'caption=Параметър');
        $this->FLD('lg', 'varchar(2)', 'caption=Език');
        $this->FLD('valueNum', 'double(smartRound)', 'caption=Индекс->Число');
        $this->FLD('valueKey', 'varchar(255)', 'caption=Индекс->Ключ');
        $this->FLD('valueVerbal', 'varchar(255)', 'caption=Текст');
        $this->FLD('valueId', 'int', 'caption=Индекс->Обект');

        $this->setDbIndex('productId');

        // Артикулът е в края, за да се търси само по индекса, без четене на редовете
        $this->setDbIndex('paramId,valueNum,productId');
        $this->setDbIndex('paramId,lg,valueKey,productId');
    }


    /**
     * Полета, които определят реда - по тях се търси
     */
    public static $rowKeyFields = 'productId,paramId,lg,valueKey,valueNum';


    /**
     * Полета, които се обновяват на място, ако са се променили
     */
    public static $rowValueFields = 'valueVerbal,valueId';


    /**
     * Синхронизира редовете на артикула - пише само разликите, без момент, в който той липсва от индекса
     *
     * @param int   $productId - ид на артикул
     * @param array $rows      - новите редове, без повтарящи се по $rowKeyFields
     *
     * @return void
     */
    public static function replaceRows($productId, $rows)
    {
        $query = static::getQuery();
        $query->where("#productId = {$productId}");
        $query->orderBy('id', 'ASC');
        $query->show('id,' . static::$rowKeyFields . ',' . static::$rowValueFields);

        // Числото се сравнява с точността, с която се записва - от базата идва като „0.000000008“, а новото е 8.0E-9
        $syncKeyFields = str_replace('valueNum', 'valueNumKey', static::$rowKeyFields);
        foreach ($rows as $row) {
            $row->valueNumKey = isset($row->valueNum) ? sprintf('%.14g', $row->valueNum) : null;
        }

        // syncArrays пази само първия от повтарящите се стари редове - другите се трият изрично
        $exRecs = $delete = $seen = array();
        foreach ($query->fetchAll() as $exRec) {
            $exRec->valueNumKey = isset($exRec->valueNum) ? sprintf('%.14g', $exRec->valueNum) : null;
            $key = arr::makeUniqueIndex($exRec, $syncKeyFields);
            if (isset($seen[$key])) {
                $delete[$exRec->id] = $exRec->id;
                continue;
            }
            $seen[$key] = true;
            $exRecs[$exRec->id] = $exRec;
        }

        $synced = arr::syncArrays($rows, $exRecs, $syncKeyFields, static::$rowValueFields);
        $delete += $synced['delete'];

        // Първо новите и обновените, после изтритите - MyISAM няма транзакции
        $me = cls::get(get_called_class());
        if (countR($synced['insert'])) {
            $me->saveArray($synced['insert'], static::$rowKeyFields . ',' . static::$rowValueFields);
        }
        if (countR($synced['update'])) {
            $me->saveArray($synced['update'], 'id,' . static::$rowValueFields);
        }
        if (countR($delete)) {
            static::delete('#id IN (' . implode(',', array_map('intval', $delete)) . ')');
        }
    }


    /**
     * След подготовка на тулбара на списъчния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        cat_products_ParamIndexState::addResetBtn($data->toolbar);
    }


    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->FLD('product', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,withClosed,allowEmpty)', 'caption=Артикул,silent');
        $data->listFilter->FLD('param', 'key(mvc=cat_Params,select=typeExt,allowEmpty)', 'caption=Параметър,silent');
        $data->listFilter->setOptions('param', array('' => '') + cat_Params::makeArray4Select('typeExt', "#filterable = 'yes'"));
        $data->listFilter->showFields = 'product,param';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input(null, 'silent');
        $data->query->orderBy('productId', 'DESC');
        $data->query->orderBy('paramId,lg,id', 'ASC');

        $filterRec = $data->listFilter->rec;
        if (!empty($filterRec->product)) {
            $data->query->where(array("#productId = [#1#]", $filterRec->product));
        }
        if (!empty($filterRec->param)) {
            $data->query->where(array("#paramId = [#1#]", $filterRec->param));
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
        $row->paramId = ht::createLink(cat_Params::getVerbal($rec->paramId, 'typeExt'), cat_Params::getSingleUrlArray($rec->paramId));
        $row->lg = strlen($rec->lg ?? '') ? $mvc->getFieldType('lg')->toVerbal($rec->lg) : "<span class='quiet'>" . tr('всички') . '</span>';

        $pRec = cat_Params::fetch($rec->paramId);
        $Driver = $pRec ? cat_Params::getDriver($pRec) : null;
        if (!$Driver) {
            $row->kind = "<span class='red'>" . tr('Няма тип') . '</span>';

            return;
        }

        $row->kind = tr(cls::getTitle($Driver));
        $row->value = $Driver->getIndexVerbal($pRec, 'cat_Products', $rec->productId, $rec);

        if (isset($rec->valueId)) {
            $row->valueId = "#{$rec->valueId}";
        }
    }
}
