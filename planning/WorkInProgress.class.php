<?php


/**
 * Клас 'store_WorkInProgress' за наличните в незавършеното производство на артикули
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_WorkInProgress extends core_Manager
{


    /**
     * Каква да е максималната дължина на стринга за пълнотекстово търсене
     *
     * @see plg_Search
     */
    public $maxSearchKeywordLen = 13;


    /**
     * Дефолтна сметка за незавършеното производство
     */
    const DEFAULT_ACC_SYS_ID = 61101;


    /**
     * Ключ с който да се заключи ъпдейта на таблицата
     */
    const SYNC_LOCK_KEY = 'syncWorkInProgress';


    /**
     * Заглавие
     */
    public $title = 'Незавършено производство';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, planning_Wrapper, plg_StyleNumbers, plg_Sorting, plg_AlignDecimals2, plg_State';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,planning,production';


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
    public $listFields = 'history,code=Код,productId=Артикул,measureId=Мярка,quantity,lastUpdated=Промяна на||Changed on';


    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'history';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,hasProperties=canStore,hasnotProperties=generic,maxSuggestions=100,forceAjax,titleFld=name)', 'caption=Артикул,tdClass=nameCell,silent');
        $this->EXT('measureId', 'cat_Products', 'externalName=measureId,externalKey=productId');
        $this->FLD('quantity', 'double(maxDecimals=3)', 'caption=Налично,tdClass=stockCol');
        $this->FLD('lastUpdated', 'datetime(format=smartTime)', 'caption=Промяна на');

        $this->setDbIndex('productId');
    }


    /**
     * Проверяваме дали колонката с инструментите не е празна, и ако е така я махаме
     */
    protected static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        $data->listTableMvc->FLD('code', 'varchar', 'tdClass=small-field nowrap');

        if (!countR($data->rows)) return;

        $to = dt::today();
        $from = dt::mysql2verbal($to, 'Y-m-1', null, false);
        foreach ($data->rows as $id => &$row) {
            $rec = $data->recs[$id];

            $pRec = cat_Products::fetch($rec->productId, 'code,isPublic,createdOn');
            $row->measureId = cat_UoM::getShortName($rec->measureId);
            $row->code = cat_Products::getVerbal($pRec, 'code');
            $row->productId = cat_Products::getHyperlink($rec->productId, true);

            // Линк към хронологията
            if (acc_BalanceDetails::haveRightFor('history')) {
                $histUrl = array('acc_BalanceHistory', 'History', 'fromDate' => $from, 'toDate' => $to, 'accNum' => static::DEFAULT_ACC_SYS_ID);
                $histUrl['ent1Id'] = acc_Items::fetchItem('cat_Products', $rec->productId)->id;
                $row->history = ht::createLink('', $histUrl, null, 'title=Хронологична справка,ef_icon=img/16/clock_history.png');
            }
        }
    }


    /**
     * Синхронизиране на запис от счетоводството с модела, Вика се от крон-а
     * (@see acc_Balances::cron_Recalc)
     *
     * @param array $arr - масив идващ от баланса във вида:
     *                   array('store_id|class_id|product_Id' => 'quantity')
     */
    public static function sync($arr)
    {
        $query = self::getQuery();
        $query->show('productId,quantity');
        $oldRecs = $query->fetchAll();
        $res = arr::syncArrays($arr, $oldRecs, 'productId', 'quantity');

        if (!core_Locks::get(self::SYNC_LOCK_KEY, 60, 1)) {
            self::logWarning('Синхронизирането на незавършеното производство е заключено от друг процес');

            return;
        }

        // Добавят се и се обновяват новите
        $self = cls::get(get_called_class());
        $self->saveArray($res['insert']);
        $self->saveArray($res['update'], 'id,quantity,lastUpdated');

        // Изтриват се тези дето ги няма
        if(countR($res['delete'])){
            $deleteStr = implode(',', $res['delete']);
            static::delete("#id IN ($deleteStr)");
        }

        // Изтриват се и нулевите количества
        static::delete("#quantity = 0");

        core_Locks::release(self::SYNC_LOCK_KEY);
    }


    /**
     * След подготовка на филтъра
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Подготвяме формата
        $data->title = "Незавършено производство към|*: <b style='color:green;'>" . dt::mysql2verbal(dt::now(), 'd.m.Y H:i') . "</b>";
        arr::placeInAssocArray($data->listFields, array('history' => ' '), 'code');
        $data->listFilter->FNC('filters', "bgerp_type_CustomFilter(classes=planning_WorkInProgress)", 'caption=Филтри,input,silent,remember,autoFilter');
        $data->listFilter->FNC('groupId', 'key2(mvc=cat_Groups,select=name,allowEmpty)', 'placeholder=Група,caption=Група,input,silent,remember,autoFilter');
        $data->listFilter->FNC('search', 'varchar', 'placeholder=Търсене,caption=Търсене,input,silent,recently');

        // Подготвяме в заявката да може да се търси по полета от друга таблица
        $field36groups = cls::get('cat_Products')->getExpandFieldName36();
        $data->query->EXT('keywords', 'cat_Products', 'externalName=searchKeywords,externalKey=productId');
        $data->query->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
        $data->query->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
        $data->query->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
        $data->query->EXT('groups', 'cat_Products', "externalName=groups,externalKey=productId");
        $data->query->EXT('name', 'cat_Products', 'externalName=name,externalKey=productId');
        $data->query->EXT('productCreatedOn', 'cat_Products', 'externalName=createdOn,externalKey=productId');
        $data->query->EXT('pState', 'cat_Products', 'externalName=state,externalKey=productId');

        $data->listFilter->layout = new ET(tr('|*' . getFileContent('acc/plg/tpl/FilterForm.shtml')));
        $data->listFilter->setDefault('filters', 'activeProducts');
        $data->listFilter->showFields = 'search,productId,filters,groupId';
        $data->listFilter->input();

        // Ако има филтър
        if ($rec = $data->listFilter->rec) {
            if(isset($rec->productId)){
                $data->query->where("#productId = {$rec->productId}");
            }

            // Ако се търси по ключови думи, търсим по тези от външното поле
            if (isset($rec->search)) {
                plg_Search::applySearch($rec->search, $data->query, 'keywords');

                // Ако ключовата дума е число, търсим и по ид
                if (type_Int::isInt($rec->search)) {
                    $data->query->orWhere("#productId = {$rec->search}");
                }
            }

            $filtersArr = bgerp_type_CustomFilter::toArray($data->listFilter->rec->filters);
            cat_Products::applyAdditionalListFilters($filtersArr, $data->query, 'productId', 'pState');

            if(isset($filtersArr['lastAdded'])){
                $data->query->orderBy('#productCreatedOn=DESC');
            } else {
                $data->query->orderBy('#state,#code');
            }

            // Филтър по групи на артикула
            if (!empty($rec->groupId)) {
                plg_ExpandInput::applyExtendedInputSearch('cat_Products', $data->query, $rec->groupId, 'productId');
            }
            $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $productState = cat_Products::fetchField($rec->productId, 'state');
        $row->ROW_ATTR['class'] = "state-{$productState}";
    }
}