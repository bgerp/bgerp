<?php


/**
 * Индекс на параметрите на артикулите за филтриране
 *
 * Събира стойностите на филтрируемите параметри от cat_Products::getParams(),
 * независимо дали са записани в cat_products_Params или се изчисляват от драйвера
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
class cat_products_ParamIndex extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Индекс на параметрите на артикулите';


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
     * Колко артикула да се индексират на shutdown, останалите ги поема крона
     */
    public static $maxOnShutdown = 50;


    /**
     * След колко дни индексът на артикула се обновява наново от нощния крон
     */
    public static $staleDays = 7;


    /**
     * Артикули, маркирани за индексиране в текущия хит
     */
    protected static $dirty = array();


    /**
     * Дали в момента се индексира (записите на артикула тогава не го маркират отново)
     */
    protected static $isReindexing = false;


    /**
     * Кеш на филтрируемите параметри
     */
    protected static $filterableParams;


    /**
     * Над колко секунди индексирането на артикул се записва в лога като бавно
     */
    public static $slowProductSecs = 1;


    /**
     * След колко секунди „Обработва се“ се смята за заседнало от умрял процес
     */
    public static $stuckProcessingSecs = 600;


    /**
     * Натрупани времена по фази в текущия хит, за лога
     */
    protected static $stats = array();


    /**
     * Бавните артикули в текущия хит - ид => секунди
     */
    protected static $slowProducts = array();


    /**
     * Описание на модела
     */
    public function description()
    {
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
     * Маркира артикули за обновяване на индекса
     *
     * @param int|array $productIds
     *
     * @return void
     */
    public static function markDirty($productIds)
    {
        if (self::$isReindexing) {

            return;
        }

        $newIds = array();
        $productIds = is_array($productIds) ? $productIds : array($productIds);
        foreach ($productIds as $productId) {
            if (!empty($productId) && !isset(self::$dirty[$productId])) {
                self::$dirty[$productId] = $productId;
                $newIds[$productId] = $productId;
            }
        }

        if (countR($newIds)) {

            // Сингълтънът е нужен, за да се извика on_Shutdown
            cls::get(get_called_class());
            cat_products_ParamIndexState::markDirty($newIds);
        }
    }


    /**
     * Маркира за обновяване артикулите, които може да имат някой от параметрите
     *
     * @param int|array $paramIds
     *
     * @return void
     */
    public static function markDirtyByParams($paramIds)
    {
        $paramIds = is_array($paramIds) ? $paramIds : array($paramIds);
        self::$filterableParams = null;
        if (!countR($paramIds)) {

            return;
        }

        // Незатворените артикули с тези параметри, записани директно
        $pQuery = cat_products_Params::getQuery();
        $pQuery->EXT('productState', 'cat_Products', 'externalName=state,externalKey=productId');
        $pQuery->where('#classId = ' . cat_Products::getClassId());
        $pQuery->where("#productState NOT IN ('closed', 'rejected')");
        $pQuery->in('paramId', $paramIds);
        $pQuery->show('productId');
        $pQuery->groupBy('productId');
        cat_products_ParamIndexState::markDirty(arr::extractValuesFromArray($pQuery->fetchAll(), 'productId'));

        // Незатворените артикули, които вече имат индексирани стойности на параметрите
        $iQuery = self::getQuery();
        $iQuery->EXT('productState', 'cat_Products', 'externalName=state,externalKey=productId');
        $iQuery->where("#productState NOT IN ('closed', 'rejected')");
        $iQuery->in('paramId', $paramIds);
        $iQuery->show('productId');
        $iQuery->groupBy('productId');
        cat_products_ParamIndexState::markDirty(arr::extractValuesFromArray($iQuery->fetchAll(), 'productId'));

        // При другите драйвери не се знае предварително кой артикул има параметъра
        cat_products_ParamIndexState::markDirtyByDriver();
    }


    /**
     * Обновява индекса на един артикул
     *
     * @param int $productId
     *
     * @return string - ok, error, skipped за затворен или locked, ако се индексира от друг процес
     */
    public static function reindex($productId)
    {
        // Изтриването и записът на редовете не бива да се застъпват между процесите
        $start = self::startTimer('lock');
        $lockKey = "cat_products_ParamIndex::reindex{$productId}";
        $obtained = core_Locks::obtain($lockKey, 60, 5, 5);
        self::stopTimer('lock', $start);
        if (!$obtained) {

            return 'locked';
        }

        $start = self::startTimer('total');
        $phases = self::$stats;
        try {
            $status = self::doReindex($productId);
        } finally {
            core_Locks::release($lockKey);
        }
        $time = self::stopTimer('total', $start);
        self::$stats['cnt'] = (self::$stats['cnt'] ?? 0) + 1;

        if ($time >= self::$slowProductSecs) {
            self::$slowProducts[$productId] = $time;
            $details = array();
            foreach (array('fetch', 'getParams', 'values', 'write', 'state') as $name) {
                $details[] = "{$name} " . round((self::$stats[$name] ?? 0) - ($phases[$name] ?? 0), 3);
            }
            self::logInfo("Бавно индексиране на артикул #{$productId}: " . round($time, 3) . ' s (' . implode(', ', $details) . ')');
        }

        return $status;
    }


    /**
     * Обновява индекса на един артикул, след като е заключен
     *
     * @param int $productId
     *
     * @return string - ok, error или skipped
     */
    protected static function doReindex($productId)
    {
        $start = self::startTimer('fetch');
        $productRec = cat_Products::fetch($productId, 'id,state', false);
        $stateRec = cat_products_ParamIndexState::fetch("#productId = {$productId}");
        self::stopTimer('fetch', $start);
        $rows = array();
        $error = null;

        // Маркирането по време на обработката я връща към „Чакащ“ (@see finishProcessing)
        $stateRec = cat_products_ParamIndexState::startProcessing($productId, $stateRec);

        // Затвореният артикул остава с последните стойности, освен ако е маркиран принудително,
        // редовете му са изтрити отвън (нулиран хеш) или не е индексиран и се ползва в е-магазина
        if ($productRec && $productRec->state == 'closed' && ($stateRec->forced ?? null) != 'yes') {
            $isIndexed = !empty($stateRec->indexedOn);
            $isInvalidated = $isIndexed && !isset($stateRec->hash);
            if (($isIndexed && !$isInvalidated) || (!$isIndexed && !self::isUsedInEshop($productId))) {
                cat_products_ParamIndexState::finishProcessing($productId, 'ok');

                return 'skipped';
            }
        }

        if ($productRec && $productRec->state != 'rejected') {
            $filterable = self::getFilterableParams();
            if (countR($filterable)) {
                try {
                    $rows = self::getIndexRows($productRec->id, $filterable);
                } catch (Throwable $e) {
                    $error = $e->getMessage();
                    reportException($e);
                }
            }
        }

        if (isset($error)) {
            cat_products_ParamIndexState::finishProcessing($productId, 'error', array('indexedOn' => dt::now(), 'lastError' => mb_substr($error, 0, 255)));
            cat_Products::logErr('Грешка при индексиране на параметрите', $productId);

            return 'error';
        }

        // Записите се подменят само при промяна
        $hash = md5(serialize($rows));
        if (($stateRec->hash ?? null) !== $hash) {
            $start = self::startTimer('write');
            self::replaceRows($productId, $rows, $stateRec);
            self::stopTimer('write', $start);
            self::$stats['written'] = (self::$stats['written'] ?? 0) + 1;
        }

        // Хешът се записва само ако междувременно артикулът не е маркиран или инвалидиран
        $start = self::startTimer('state');
        $fields = array('forced' => 'no', 'hash' => $hash, 'rowsCnt' => countR($rows), 'indexedOn' => dt::now(), 'lastError' => null);
        cat_products_ParamIndexState::finishProcessing($productId, 'ok', $fields);
        self::stopTimer('state', $start);

        return 'ok';
    }


    /**
     * Подменя редовете на артикула, без да остава момент, в който той липсва от индекса
     *
     * @param int      $productId - ид на артикул
     * @param array    $rows      - новите редове
     * @param stdClass $stateRec  - състоянието на артикула
     *
     * @return void
     */
    protected static function replaceRows($productId, $rows, $stateRec)
    {
        // Без хеш прекъсната подмяна се пренаписва при следващото индексиране
        $stateRec->hash = null;
        cat_products_ParamIndexState::save($stateRec, 'hash');

        $query = self::getQuery();
        $query->where("#productId = {$productId}");
        $query->XPR('maxId', 'int', 'MAX(#id)');
        $query->show('maxId');
        $maxOldId = $query->fetch()->maxId ?? null;

        // Първо новите, после старите - MyISAM няма транзакции
        if (countR($rows)) {
            cls::get(get_called_class())->saveArray($rows, 'productId,paramId,lg,valueNum,valueKey,valueVerbal,valueId');
        }
        if (!empty($maxOldId)) {
            self::delete("#productId = {$productId} AND #id <= {$maxOldId}");
        }
    }


    /**
     * Подготвя редовете за индекса на артикула
     *
     * @param int   $productId  - ид на артикул
     * @param array $filterable - филтрируемите параметри
     *
     * @return array
     */
    protected static function getIndexRows($productId, $filterable)
    {
        // Чете се последно изчисленото, без преизчисляване и запис на артикула
        self::$isReindexing = true;
        Mode::push('doNotCalculate', true);
        $start = self::startTimer('getParams');
        try {
            $params = cat_Products::getParams($productId);
        } finally {
            self::stopTimer('getParams', $start);
            Mode::pop('doNotCalculate');
            self::$isReindexing = false;
        }

        $rows = array();
        if (!is_array($params)) {

            return $rows;
        }

        $start = self::startTimer('values');
        $langs = array_keys(core_Lg::getLangs());
        $productClassId = cat_Products::getClassId();
        foreach ($params as $paramId => $value) {
            if (!isset($filterable[$paramId]) || is_array($value) || is_object($value)) continue;

            $pRec = $filterable[$paramId];
            $Driver = cat_Params::getDriver($pRec);
            if (!$Driver || !cls::existsMethod($Driver, 'getIndexValues')) continue;

            $values = $Driver->getIndexValues($pRec, $productClassId, $productId, $value, $langs);
            foreach ($values as $iRow) {
                $iRow->productId = $productId;
                $iRow->paramId = $paramId;
                $rows[] = $iRow;
            }
        }
        self::stopTimer('values', $start);

        return $rows;
    }


    /**
     * Дали артикулът участва в е-артикул
     *
     * @param int $productId
     *
     * @return bool
     */
    protected static function isUsedInEshop($productId)
    {
        if (!core_Packs::isInstalled('eshop')) {

            return false;
        }

        return (bool) eshop_ProductDetails::fetchField("#productId = {$productId}", 'id');
    }


    /**
     * Филтрируемите параметри
     *
     * @return array
     */
    public static function getFilterableParams()
    {
        if (!isset(self::$filterableParams)) {
            $query = cat_Params::getQuery();
            $query->where("#filterable IN ('internal', 'yes') AND #state != 'rejected'");
            self::$filterableParams = $query->fetchAll();
        }

        return self::$filterableParams;
    }


    /**
     * Прави филтрируеми параметрите, които се показват в е-магазина
     *
     * @return void
     */
    public static function setEshopParamsFilterable()
    {
        if (!core_Packs::isInstalled('eshop')) {

            return;
        }

        $paramIds = array();
        foreach (array('eshop_Settings', 'eshop_Groups', 'eshop_Products') as $class) {
            $query = cls::get($class)->getQuery();
            $query->show('showParams,showListParams');
            while ($rec = $query->fetch()) {
                $paramIds += keylist::toArray($rec->showParams ?? null) + keylist::toArray($rec->showListParams ?? null);
            }
        }

        if (!countR($paramIds)) return;

        $Params = cls::get('cat_Params');
        $query = cat_Params::getQuery();
        $query->in('id', $paramIds);
        $query->where("#state != 'rejected' AND #filterable != 'yes'");
        while ($rec = $query->fetch()) {
            if (!cat_Params::canBeFilterable($rec)) continue;

            // Артикулите се маркират отделно, накуп
            $rec->filterable = 'yes';
            $rec->_skipParamIndex = true;
            $Params->save($rec, 'filterable');
        }
        self::$filterableParams = null;
    }


    /**
     * Изтрива от индекса стойностите на параметрите
     *
     * @param int|array $paramIds
     *
     * @return void
     */
    public static function removeParams($paramIds)
    {
        $paramIds = is_array($paramIds) ? $paramIds : array($paramIds);
        self::$filterableParams = null;
        if (!countR($paramIds)) {

            return;
        }

        // Хешът на засегнатите се нулира, иначе при повторно включване изтритите редове не се връщат
        $query = self::getQuery();
        $query->in('paramId', $paramIds);
        $query->show('productId');
        $query->groupBy('productId');
        cat_products_ParamIndexState::invalidate(arr::extractValuesFromArray($query->fetchAll(), 'productId'));

        self::delete('#paramId IN (' . implode(',', array_map('intval', $paramIds)) . ')');
    }


    /**
     * Обработва маркираните артикули до изтичане на времето
     *
     * @param int      $timeLimit - секунди
     * @param int|null $maxCnt    - максимален брой артикули
     *
     * @return array|false - брой обработени по статус или false, ако обработката вече работи
     */
    public static function processDirty($timeLimit, $maxCnt = null)
    {
        $lockKey = 'cat_products_ParamIndex::processDirty';
        if (!core_Locks::obtain($lockKey, $timeLimit + 60, 0, 0)) {

            return false;
        }

        try {
            $res = self::doProcessDirty($timeLimit, $maxCnt);
        } finally {
            core_Locks::release($lockKey);
        }

        return $res;
    }


    /**
     * Обработва маркираните артикули, след като обработката е заключена
     *
     * @param int      $timeLimit - секунди
     * @param int|null $maxCnt    - максимален брой артикули
     *
     * @return array - брой обработени по статус
     */
    protected static function doProcessDirty($timeLimit, $maxCnt = null)
    {
        $end = time() + $timeLimit;
        $res = array('ok' => 0, 'error' => 0, 'locked' => 0, 'skipped' => 0);
        $seen = array();

        // На порции, за да не се зареждат наведнъж стотиците хиляди маркирани
        while (time() < $end && (!isset($maxCnt) || countR($seen) < $maxCnt)) {
            $start = self::startTimer('select');
            // Заседналите в обработка (умрял процес) се поемат наново
            $stuckBefore = dt::addSecs(-1 * self::$stuckProcessingSecs);
            $query = cat_products_ParamIndexState::getQuery();
            $query->where(array("(#status = 'dirty' OR (#status = 'processing' AND #processingOn < '[#1#]'))", $stuckBefore));
            $query->orderBy('indexedOn', 'ASC');
            $query->show('productId');
            $query->limit(isset($maxCnt) ? min(1000, $maxCnt - countR($seen)) : 1000);
            $productIds = arr::extractValuesFromArray($query->fetchAll(), 'productId');
            self::stopTimer('select', $start);

            // Заключените от друг процес остават маркирани - не се въртим по тях
            $productIds = array_diff_key($productIds, $seen);
            if (!countR($productIds)) break;

            foreach ($productIds as $productId) {
                if (time() >= $end) break;

                $seen[$productId] = true;
                $status = self::reindex($productId);
                $res[$status]++;
            }
        }

        return $res;
    }


    /**
     * Обновяване на индекса на маркираните артикули
     */
    public function cron_UpdateDirty()
    {
        $res = self::processDirty(180);
        if ($res === false) {

            return 'Индексирането вече работи';
        }

        return self::logStats($res);
    }


    /**
     * Записва в лога резултата и времената от обработката
     *
     * @param array $res - брой обработени по статус
     *
     * @return string - текстът на записа
     */
    public static function logStats($res)
    {
        $cnt = self::$stats['cnt'] ?? 0;
        $msg = "Индексирани: {$res['ok']}, грешки: {$res['error']}, пропуснати затворени: {$res['skipped']}, заключени: {$res['locked']}";
        if (!$cnt) {

            return $msg;
        }

        $total = self::$stats['total'] ?? 0;
        $msg .= '; общо ' . round($total, 2) . ' s, средно ' . round($total / $cnt * 1000, 1) . ' ms/артикул';
        $msg .= ', пренаписани: ' . (self::$stats['written'] ?? 0);
        foreach (array('select', 'lock', 'fetch', 'getParams', 'values', 'write', 'state') as $name) {
            $msg .= "; {$name} " . round(self::$stats[$name] ?? 0, 2) . ' s';
        }
        if (countR(self::$slowProducts)) {
            arsort(self::$slowProducts);
            $slow = array();
            foreach (array_slice(self::$slowProducts, 0, 5, true) as $productId => $time) {
                $slow[] = "#{$productId} " . round($time, 2) . ' s';
            }
            $msg .= '; бавни (' . countR(self::$slowProducts) . '): ' . implode(', ', $slow);
        }

        self::logInfo($msg);

        return $msg;
    }


    /**
     * Пуска таймер за фаза от индексирането
     *
     * @param string $name
     *
     * @return float - началото
     */
    protected static function startTimer($name)
    {
        core_Debug::startTimer("PARAM_INDEX_{$name}");

        return microtime(true);
    }


    /**
     * Спира таймера и натрупва времето във фазата
     *
     * @param string $name
     * @param float  $start
     *
     * @return float - изминалите секунди
     */
    protected static function stopTimer($name, $start)
    {
        core_Debug::stopTimer("PARAM_INDEX_{$name}");
        $time = microtime(true) - $start;
        self::$stats[$name] = (self::$stats[$name] ?? 0) + $time;

        return $time;
    }


    /**
     * Маркиране на остарелия индекс и на неиндексираните артикули
     */
    public function cron_MarkStale()
    {
        $start = microtime(true);
        $added = cat_products_ParamIndexState::markMissing();
        $missingTime = microtime(true) - $start;

        $start = microtime(true);
        $stale = cat_products_ParamIndexState::markStale(dt::addDays(-1 * self::$staleDays));
        $staleTime = microtime(true) - $start;

        $msg = "Нови артикули: {$added} (" . round($missingTime, 2) . " s), остарели: {$stale} (" . round($staleTime, 2) . ' s)';
        self::logInfo($msg);

        return $msg;
    }


    /**
     * Индексиране на маркираните в хита артикули
     */
    public static function on_Shutdown($mvc)
    {
        if (!countR(self::$dirty)) {

            return;
        }

        $productIds = array_slice(self::$dirty, 0, self::$maxOnShutdown, true);
        self::$dirty = array();
        foreach ($productIds as $productId) {
            self::reindex($productId);
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
        $data->listFilter->setOptions('param', array('' => '') + cat_Params::makeArray4Select('typeExt', "#filterable IN ('internal', 'yes')"));
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
