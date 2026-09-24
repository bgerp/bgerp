<?php


/**
 * Състояние на индекса на параметрите по артикули
 *
 * @see cat_products_ParamIndex
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
class cat_products_ParamIndexState extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Състояние на индекса на параметрите';


    /**
     * Единично заглавие
     */
    public $singleTitle = 'Състояние на индекса';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'cat_Wrapper, plg_Sorting, plg_RowTools2, plg_Select, plg_Created';


    /**
     * Действия с избраните
     */
    public $doWithSelected = 'markdirty=Маркирай за обновяване';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId,driver=Драйвер,status,forced,rowsCnt,indexedOn,hash,lastError,createdOn';


    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'forced,lastError';


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
     * Кой може да преиндексира
     */
    public $canReindex = 'debug';


    /**
     * Кой може да изчисти индекса и да пусне наново миграциите
     */
    public $canReset = 'debug';


    /**
     * Кой може да маркира избраните за обновяване
     */
    public $canMarkdirty = 'debug';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        $this->FLD('status', 'enum(dirty=За обновяване,processing=Обработва се,ok=Актуален,error=Грешка)', 'caption=Статус,notNull,value=dirty');
        $this->FLD('forced', 'enum(no=Не,yes=Да)', 'caption=Принудително,notNull,value=no');
        $this->FLD('processingOn', 'datetime(format=smartTime)', 'caption=Начало на обработката,input=none,column=none');
        $this->FLD('rowsCnt', 'int', 'caption=Редове');
        $this->FLD('indexedOn', 'datetime(format=smartTime)', 'caption=Индексиран');
        $this->FLD('hash', 'varchar(32)', 'caption=Хеш');
        $this->FLD('lastError', 'varchar(255)', 'caption=Грешка');

        $this->setDbUnique('productId');
        $this->setDbIndex('status,indexedOn');
    }


    /**
     * Маркира артикулите за обновяване
     *
     * @param array $productIds
     *
     * @return void
     */
    public static function markDirty($productIds)
    {
        $recs = array();
        foreach ($productIds as $productId) {
            if (!empty($productId)) {
                $recs[] = (object) array('productId' => $productId, 'status' => 'dirty');
            }
        }

        if (countR($recs)) {
            cls::get(get_called_class())->saveArray($recs, 'productId,status');
        }
    }


    /**
     * Маркира артикулите за обновяване и нулира хеша им, за да се пренапишат редовете
     *
     * @param array $productIds
     *
     * @return void
     */
    public static function invalidate($productIds)
    {
        $recs = array();
        foreach ($productIds as $productId) {
            if (!empty($productId)) {
                $recs[] = (object) array('productId' => $productId, 'status' => 'dirty', 'hash' => null);
            }
        }

        if (countR($recs)) {
            cls::get(get_called_class())->saveArray($recs, 'productId,status,hash');
        }
    }


    /**
     * Отбелязва, че артикулът се обработва
     *
     * @param int           $productId
     * @param stdClass|null $rec - текущото състояние, ако има
     *
     * @return stdClass - състоянието
     */
    public static function startProcessing($productId, $rec)
    {
        if (!$rec) {
            $rec = (object) array('productId' => $productId);
        }
        $rec->status = 'processing';
        $rec->processingOn = dt::now();
        self::save($rec, empty($rec->id) ? null : 'status,processingOn');

        return $rec;
    }


    /**
     * Приключва обработката, освен ако междувременно артикулът е маркиран наново
     *
     * Условният UPDATE е нарочно директен - записът през модела би презаписал новата маркировка,
     * а заедно със статуса се пишат и хешът и метаданните, за да не се върне остарял хеш
     *
     * @param int    $productId
     * @param string $status    - ok или error
     * @param array  $fields    - други полета за запис: поле => стойност
     *
     * @return void
     */
    public static function finishProcessing($productId, $status, $fields = array())
    {
        $me = cls::get(get_called_class());
        $statusCol = str::phpToMysqlName('status');
        $productIdCol = str::phpToMysqlName('productId');

        $set = array();
        foreach (array('status' => $status) + $fields as $field => $value) {
            $col = str::phpToMysqlName($field);
            $set[] = "`{$col}` = " . (isset($value) ? "'" . $me->db->escape($value) . "'" : 'NULL');
        }

        $me->db->query("UPDATE `{$me->dbTableName}` SET " . implode(', ', $set) . " WHERE `{$productIdCol}` = " . (int) $productId . " AND `{$statusCol}` = 'processing'");
        $me->dbTableUpdated();
    }


    /**
     * Маркира артикулите за индексиране, независимо от състоянието им
     *
     * @param array $productIds
     *
     * @return void
     */
    public static function markForced($productIds)
    {
        $recs = array();
        foreach ($productIds as $productId) {
            if (!empty($productId)) {
                $recs[] = (object) array('productId' => $productId, 'status' => 'dirty', 'forced' => 'yes');
            }
        }

        if (countR($recs)) {
            cls::get(get_called_class())->saveArray($recs, 'productId,status,forced');
        }
    }


    /**
     * Маркира всички незатворени артикули с драйвер, различен от универсалния
     *
     * @return int - брой засегнати записи
     */
    public static function markDirtyByDriver()
    {
        $generalClassId = cat_GeneralProductDriver::getClassId();

        return self::markProducts("p.#innerClass != {$generalClassId}", true);
    }


    /**
     * Добавя незатворените артикули, които още нямат запис, и затворените, ползвани в е-магазина
     *
     * @return int - брой добавени
     */
    public static function markMissing()
    {
        return self::markProducts('s.#id IS NULL', false, true);
    }


    /**
     * Маркира за обновяване незатворените артикули, индексирани преди датата, тези с грешка и прекъснатите
     *
     * @param string $before
     *
     * @return int - брой маркирани
     */
    public static function markStale($before)
    {
        $me = cls::get(get_called_class());
        $Products = cls::get('cat_Products');
        $productIdCol = str::phpToMysqlName('productId');
        $status = str::phpToMysqlName('status');
        $indexedOn = str::phpToMysqlName('indexedOn');
        $productState = str::phpToMysqlName('state');
        $before = $me->db->escape($before);

        // Затворените си остават с последно индексираните стойности
        $me->db->query("UPDATE `{$me->dbTableName}` s JOIN `{$Products->dbTableName}` p ON p.`id` = s.`{$productIdCol}`
                        SET s.`{$status}` = 'dirty'
                        WHERE p.`{$productState}` != 'rejected' AND (s.`{$status}` IN ('error', 'processing')
                           OR (p.`{$productState}` != 'closed' AND s.`{$status}` = 'ok' AND (s.`{$indexedOn}` IS NULL OR s.`{$indexedOn}` < '{$before}')))");
        $res = $me->db->affectedRows();
        $me->dbTableUpdated();

        return $res;
    }


    /**
     * Маркира незатворените и неоттеглени артикули по условие с една заявка
     *
     * Масово е с директен SQL, защото артикулите може да са стотици хиляди
     *
     * @param string $where            - условие с p.#поле за артикула и s.#поле за състоянието
     * @param bool   $update           - да се обнови ли статусът на вече съществуващите
     * @param bool   $withEshopClosed  - да се включат ли и затворените, ползвани в е-магазина
     *
     * @return int - брой засегнати записи
     */
    protected static function markProducts($where, $update, $withEshopClosed = false)
    {
        $me = cls::get(get_called_class());
        $Products = cls::get('cat_Products');
        $productIdCol = str::phpToMysqlName('productId');
        $status = str::phpToMysqlName('status');

        $where = preg_replace_callback('/\b([ps])\.#([a-zA-Z0-9]+)/', function ($m) {
            return "{$m[1]}.`" . str::phpToMysqlName($m[2]) . '`';
        }, $where);
        $productState = str::phpToMysqlName('state');

        $stateCond = "p.`{$productState}` NOT IN ('closed', 'rejected')";
        if ($withEshopClosed && core_Packs::isInstalled('eshop')) {
            $Details = cls::get('eshop_ProductDetails');
            $stateCond = "({$stateCond} OR (p.`{$productState}` = 'closed' AND p.`id` IN (SELECT `{$productIdCol}` FROM `{$Details->dbTableName}`)))";
        }

        $sql = ($update ? 'INSERT' : 'INSERT IGNORE') . " INTO `{$me->dbTableName}` (`{$productIdCol}`, `{$status}`)
                SELECT p.`id`, 'dirty' FROM `{$Products->dbTableName}` p
                LEFT JOIN `{$me->dbTableName}` s ON s.`{$productIdCol}` = p.`id`
                WHERE {$stateCond} AND {$where}";
        if ($update) {
            $sql .= " ON DUPLICATE KEY UPDATE `{$status}` = 'dirty'";
        }

        $me->db->query($sql);
        $res = $me->db->affectedRows();
        $me->dbTableUpdated();

        return $res;
    }


    /**
     * Изчистване на индекса и повторно пускане на миграциите му
     */
    public function act_Reset()
    {
        $this->requireRightFor('reset');

        cat_products_ParamIndex::truncate();
        self::truncate();

        cat_products_ParamIndex::setEshopParamsFilterable();
        self::markMissing();
        $this->logWrite('Изчистване на индекса на параметрите');

        followRetUrl(array($this, 'list'), '|Индексът е изчистен, маркирани артикули|*: ' . self::count());
    }


    /**
     * Добавя бутона за изчистване на индекса
     *
     * @param core_Toolbar $toolbar
     *
     * @return void
     */
    public static function addResetBtn($toolbar)
    {
        if (cls::get(get_called_class())->haveRightFor('reset')) {
            $toolbar->addBtn('Изчисти индекса', array(get_called_class(), 'reset', 'ret_url' => true), 'ef_icon=img/16/recycle.png,title=Изчистване на двете таблици на индекса и повторно пускане на миграциите му,warning=Наистина ли желаете индексът да се изчисти и да се изгради наново|*?');
        }
    }


    /**
     * Маркиране на избраните артикули за обновяване
     */
    public function act_Markdirty()
    {
        $this->requireRightFor('markdirty');

        $productIds = array();
        foreach (arr::make(Request::get('Selected', 'varchar')) as $id) {
            $rec = is_numeric($id) ? $this->fetch($id) : null;
            if ($rec && $this->haveRightFor('markdirty', $rec)) {
                $productIds[$rec->productId] = $rec->productId;
            }
        }

        self::markDirty($productIds);
        $this->logWrite('Маркиране на избраните артикули за индексиране');

        followRetUrl(array($this, 'list'), '|Маркирани за обновяване|*: ' . countR($productIds));
    }


    /**
     * Обработка на маркираните веднага, вместо да се чака крона
     */
    public function act_Process()
    {
        $this->requireRightFor('reindex');

        core_App::setTimeLimit(120);
        $res = cat_products_ParamIndex::processDirty(60);
        if ($res === false) {
            followRetUrl(null, '|Индексирането вече работи в друг процес|*!', 'warning');
        }
        $this->logWrite('Ръчно индексиране на маркираните артикули');

        followRetUrl(null, '|*' . cat_products_ParamIndex::logStats($res));
    }


    /**
     * Преиндексиране на един артикул
     */
    public function act_Reindex()
    {
        $this->requireRightFor('reindex');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));

        // Ръчното преиндексиране обхваща и затворените артикули
        self::markForced(array($rec->productId));
        $status = cat_products_ParamIndex::reindex($rec->productId);
        $this->logWrite('Преиндексиране на артикул', $id);

        if ($status == 'locked') {
            followRetUrl(null, '|Артикулът се индексира в друг процес|*!', 'warning');
        }

        $msg = ($status == 'ok') ? '|Артикулът е преиндексиран' : '|Грешка при преиндексиране|*!';
        followRetUrl(null, $msg, ($status == 'ok') ? 'notice' : 'error');
    }


    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->FLD('product', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,withClosed,allowEmpty)', 'caption=Артикул,silent');
        $data->listFilter->FLD('statusFilter', 'enum(all=Всички,dirty=За обновяване,processing=Обработва се,ok=Актуален,error=Грешка)', 'caption=Статус,silent');
        $data->listFilter->showFields = 'product,statusFilter';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input(null, 'silent');
        // Първо изискващите внимание
        $data->query->XPR('orderByStatus', 'int', "(CASE #status WHEN 'error' THEN 1 WHEN 'dirty' THEN 2 WHEN 'processing' THEN 3 ELSE 4 END)");
        $data->query->orderBy('orderByStatus', 'ASC');
        $data->query->orderBy('indexedOn,id', 'DESC');

        $filterRec = $data->listFilter->rec;
        if (!empty($filterRec->product)) {
            $data->query->where(array("#productId = [#1#]", $filterRec->product));
        }
        if (!empty($filterRec->statusFilter) && $filterRec->statusFilter != 'all') {
            $data->query->where(array("#status = '[#1#]'", $filterRec->statusFilter));
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        // Вече маркираните няма какво да се маркират
        if ($action == 'markdirty' && isset($rec) && ($rec->status ?? null) == 'dirty') {
            $requiredRoles = 'no_one';
        }
    }


    /**
     * След подготовка на тулбара на списъчния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if ($mvc->haveRightFor('reindex')) {
            $data->toolbar->addBtn('Обработи сега', array($mvc, 'process', 'ret_url' => true), 'ef_icon=img/16/arrow_refresh.png,title=Индексиране на маркираните артикули веднага');
        }

        self::addResetBtn($data->toolbar);
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->productId = cat_Products::getHyperlink($rec->productId, true);

        $driverClass = cat_Products::fetchField($rec->productId, 'innerClass');
        if (!empty($driverClass) && cls::load($driverClass, true)) {
            $row->driver = tr(cls::getTitle(cls::getClassName($driverClass)));
        }

        $statusClass = array('dirty' => 'quiet', 'processing' => 'quiet', 'ok' => 'green', 'error' => 'red');
        $row->status = "<span class='{$statusClass[$rec->status]}'>{$row->status}</span>";

        // Показва се само принудителното маркиране
        if ($rec->forced != 'yes') {
            unset($row->forced);
        }

        if (!empty($rec->hash)) {
            $row->hash = "<span class='quiet' title='{$rec->hash}'>" . substr($rec->hash, 0, 8) . '</span>';
        }

        if (!empty($rec->rowsCnt) && cat_products_ParamIndex::haveRightFor('list')) {
            $row->rowsCnt = ht::createLink($row->rowsCnt, array('cat_products_ParamIndex', 'list', 'product' => $rec->productId));
        }
    }


    /**
     * След подготовка на редовете на списъчния изглед
     */
    protected static function on_AfterPrepareListRows($mvc, &$data)
    {
        if (!countR($data->rows) || !$mvc->haveRightFor('reindex')) {

            return;
        }

        foreach ($data->rows as $id => $row) {
            if (isset($row->_rowTools)) {
                $row->_rowTools->addLink('Преиндексирай', array($mvc, 'reindex', $id, 'ret_url' => true), 'ef_icon=img/16/arrow_refresh.png,title=Преиндексиране на артикула');
            }
        }
    }
}
