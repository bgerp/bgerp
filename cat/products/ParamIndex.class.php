<?php


/**
 * Индекс на параметрите на артикулите за филтриране
 *
 * Събира стойностите на филтрируемите параметри от cat_Products::getParams(),
 * независимо дали са записани в cat_products_Params или се изчисляват от драйвера.
 * Ползваните в е-артикули се пишат и в отделна таблица (@see cat_products_EshopParamIndex)
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_products_ParamIndex extends cat_products_ProtoParamIndex
{
    /**
     * Заглавие
     */
    public $title = 'Индекс на параметрите на артикулите';


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

        // Незатворените артикули от групите, които имат параметрите
        $gQuery = cat_GroupParams::getQuery();
        $gQuery->in('paramId', $paramIds);
        $gQuery->show('groupId');
        $gQuery->groupBy('groupId');
        cat_products_ParamIndexState::markByGroups(arr::extractValuesFromArray($gQuery->fetchAll(), 'groupId'));

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
        if ((!is_int($productId) && !(is_string($productId) && ctype_digit($productId))) || (int) $productId <= 0) {

            return 'error';
        }

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
        $inEshop = self::isUsedInEshop($productId) ? 'yes' : 'no';
        $eshopChanged = (($stateRec->inEshop ?? 'no') != $inEshop);
        self::stopTimer('fetch', $start);
        $rows = array();
        $error = null;

        // Маркирането по време на обработката я връща към „Чакащ“ (@see finishProcessing)
        $stateRec = cat_products_ParamIndexState::startProcessing($productId, $stateRec);

        // Затвореният артикул остава с последните стойности, освен ако е маркиран принудително,
        // редовете му са изтрити отвън (нулиран хеш), влязъл/излязъл е от е-магазина или не е индексиран и се ползва там
        if ($productRec && $productRec->state == 'closed' && ($stateRec->forced ?? null) != 'yes' && !$eshopChanged) {
            $isIndexed = !empty($stateRec->indexedOn);
            $isInvalidated = $isIndexed && !isset($stateRec->hash);
            if (($isIndexed && !$isInvalidated) || (!$isIndexed && $inEshop != 'yes')) {
                cat_products_ParamIndexState::finishProcessing($productId, 'ok');

                return 'skipped';
            }
        }

        if ($productRec && $productRec->state != 'rejected') {
            $filterable = self::getFilterableParams();
            if (countR($filterable)) {
                try {
                    $rows = self::getIndexRows($productRec->id, $filterable);
                } catch (core_exception_Redirect $e) {
                    throw $e;
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

        // Записите се подменят само при промяна, а в е-магазина - и при влизане/излизане от него
        $hash = md5(serialize($rows));
        $writeMain = (($stateRec->hash ?? null) !== $hash);
        $writeEshop = $eshopChanged || ($writeMain && $inEshop == 'yes');
        if ($writeMain || $writeEshop) {
            $start = self::startTimer('write');

            // Без хеш прекъсната подмяна се пренаписва при следващото индексиране
            $stateRec->hash = null;
            cat_products_ParamIndexState::save($stateRec, 'hash');
            if ($writeMain) {
                self::replaceRows($productId, $rows);
            }
            if ($writeEshop) {
                cat_products_EshopParamIndex::replaceRows($productId, ($inEshop == 'yes') ? $rows : array());
            }
            self::stopTimer('write', $start);
            self::$stats['written'] = (self::$stats['written'] ?? 0) + 1;
        }

        // Хешът се записва само ако междувременно артикулът не е маркиран или инвалидиран
        $start = self::startTimer('state');
        $fields = array('forced' => 'no', 'hash' => $hash, 'inEshop' => $inEshop, 'rowsCnt' => countR($rows), 'indexedOn' => dt::now(), 'lastError' => null);
        cat_products_ParamIndexState::finishProcessing($productId, 'ok', $fields);
        self::stopTimer('state', $start);

        return 'ok';
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

            // Еднаквите по ключ стойности (напр. записи с едно име) са един ред - иначе синхронизацията ги трупа
            foreach ($values as $iRow) {
                $iRow->productId = $productId;
                $iRow->paramId = $paramId;
                $key = arr::makeUniqueIndex($iRow, self::$rowKeyFields);
                if (!isset($rows[$key])) {
                    $rows[$key] = $iRow;
                }
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
            $query->where("#filterable = 'yes' AND #state != 'rejected'");
            self::$filterableParams = $query->fetchAll();
        }

        return self::$filterableParams;
    }


    /**
     * Артикули, в които някой от параметрите има търсената стойност
     *
     * Чете се от индекса. Кандидатите без актуален индекс и неиндексираните параметри се проверяват с getParams()
     *
     * @param array      $paramIds   - параметри
     * @param string     $value      - търсена стойност (текстът - без значение от регистъра)
     * @param string     $match      - contains, exact, gt, gte, lt, lte, between (число или дата) или any (попълнен)
     * @param array|null $productIds - кандидати, null за всички индексирани артикули
     * @param string     $valueTo    - горната граница при between
     *
     * @return array ['products' => [ид на артикул => [ид на параметър => стойност]], 'notIndexed' => [ид на параметър], 'pending' => брой]
     */
    public static function searchProducts($paramIds, $value, $match = 'contains', $productIds = null, $valueTo = null)
    {
        // Четат се последно изчислените стойности - без преизчисляване и запис на артикулите
        Mode::push('doNotCalculate', true);
        try {

            return self::doSearchProducts($paramIds, $value, $match, $productIds, $valueTo);
        } finally {
            Mode::pop('doNotCalculate');
        }
    }


    /**
     * Търсенето на артикули по стойност на параметри (@see searchProducts)
     *
     * @param array      $paramIds
     * @param string     $value
     * @param string     $match
     * @param array|null $productIds
     * @param string     $valueTo
     *
     * @return array
     */
    protected static function doSearchProducts($paramIds, $value, $match, $productIds, $valueTo)
    {
        $res = array('products' => array(), 'notIndexed' => array(), 'pending' => 0);
        $cond = self::getSearchCondition($value, $match, $valueTo);
        $paramIds = array_unique(arr::makeIds($paramIds));
        if (!$cond || !countR($paramIds)) {

            return $res;
        }

        $filterable = self::getFilterableParams();
        $indexed = array_values(array_filter($paramIds, function ($paramId) use ($filterable) { return isset($filterable[$paramId]); }));
        $res['notIndexed'] = array_values(array_diff($paramIds, $indexed));

        // Кандидатите без актуален индекс се проверяват изцяло, а останалите - само за неиндексираните параметри
        $direct = $fresh = array();
        if (isset($productIds)) {
            $productIds = array_unique(arr::makeIds($productIds));
            if (!countR($productIds)) {

                return $res;
            }
            $sQuery = cat_products_ParamIndexState::getQuery();
            $sQuery->in('productId', $productIds);
            $sQuery->where("#status = 'ok' AND #indexedOn IS NOT NULL");
            $sQuery->show('productId');
            while ($sRec = $sQuery->fetch()) {
                $fresh[$sRec->productId] = true;
            }
            foreach ($productIds as $productId) {
                if (!isset($fresh[$productId])) {
                    $direct[$productId] = $paramIds;
                } elseif (countR($res['notIndexed'])) {
                    $direct[$productId] = $res['notIndexed'];
                }
            }
            $res['pending'] = countR($productIds) - countR($fresh);
        } else {
            $res['pending'] = cat_products_ParamIndexState::count("#status != 'ok'");
        }

        // Както във филтъра: различните стойности от индекса, сравнени в PHP, а артикулите - по точните стойности
        if (countR($indexed) && (!isset($productIds) || countR($fresh))) {
            $params = array_intersect_key($filterable, array_flip($indexed));
            $baseQuery = cat_products_ParamFilter::getIndexQuery(cat_products_ParamFilter::getLang());
            if (isset($productIds)) {
                $baseQuery->in('productId', array_keys($fresh));
            }

            // Ключовете и числата се избират като във филтъра (@see cat_products_ParamFilter::getExactValueConds())
            $Index = cls::get('cat_products_ParamIndex');
            $col = function ($name) {
                return "#{$name}";
            };
            $conds = $verbals = array();
            foreach (cat_products_ParamFilter::getValues($baseQuery, $params) as $paramId => $paramValues) {
                $Driver = cat_Params::getDriver($params[$paramId]);
                $keys = $nums = array();
                foreach ($paramValues as $vRec) {
                    if (!isset($vRec->valueKey) && !isset($vRec->valueNum)) continue;

                    $verbal = self::matchIndexRow($params[$paramId], $Driver, $vRec->productId, $vRec, $cond);
                    if (!isset($verbal)) continue;

                    if (isset($vRec->valueKey)) {
                        $keys[] = $vRec->valueKey;
                        $verbals[$paramId]["k{$vRec->valueKey}"] = $verbal;
                    } else {
                        $nums[] = $vRec->valueNum;
                        $verbals[$paramId]["n{$vRec->valueNum}"] = $verbal;
                    }
                }
                $valueConds = cat_products_ParamFilter::getExactValueConds($keys, $nums, $col, $Index->db);
                if (countR($valueConds)) {
                    $conds[] = '(#paramId = ' . (int) $paramId . ' AND (' . implode(' OR ', $valueConds) . '))';
                }
            }

            if (countR($conds)) {
                $query = clone $baseQuery;
                $query->where(implode(' OR ', $conds));
                $query->show('productId,paramId,valueKey,valueNumText');
                while ($iRec = $query->fetch()) {
                    $key = isset($iRec->valueKey) ? "k{$iRec->valueKey}" : "n{$iRec->valueNumText}";
                    $res['products'][$iRec->productId][$iRec->paramId] = $verbals[$iRec->paramId][$key] ?? ($iRec->valueKey ?? $iRec->valueNumText);
                }
            }
        }

        foreach ($direct as $productId => $params) {
            foreach (self::matchProductParams($productId, $params, $cond) as $paramId => $verbal) {
                $res['products'][$productId][$paramId] = $verbal;
            }
        }

        return $res;
    }


    /**
     * Кои от параметрите на артикула имат търсената стойност - според getParams()
     *
     * Стойността се разбива на редовете, които драйверът дава за индекса, за да се сравнява като в него
     *
     * @param int    $productId
     * @param array  $paramIds
     * @param array  $cond      - @see getSearchCondition
     *
     * @return array - ид на параметър => стойност
     */
    protected static function matchProductParams($productId, $paramIds, $cond)
    {
        $res = array();
        $params = cat_Products::getParams($productId);
        if (!is_array($params)) {

            return $res;
        }

        $productClassId = cat_Products::getClassId();
        $langs = array(cat_products_ParamFilter::getLang());
        foreach ($paramIds as $paramId) {
            $value = $params[$paramId] ?? null;
            if (!isset($value) || is_array($value) || is_object($value)) continue;

            $pRec = cat_Params::fetch($paramId);
            $Driver = is_object($pRec) ? cat_Params::getDriver($pRec) : null;
            $rows = ($Driver && cls::existsMethod($Driver, 'getIndexValues')) ? $Driver->getIndexValues($pRec, $productClassId, $productId, $value, $langs) : array();

            // Типовете без индекс (многоредов текст, формула) се сравняват по цялата стойност
            if (!countR($rows)) {
                $rows = array((object) array('valueKey' => (string) $value, 'valueVerbal' => null, 'valueNum' => null));
            }
            foreach ($rows as $iRec) {
                $verbal = self::matchIndexRow($pRec, $Driver, $productId, $iRec, $cond);
                if (isset($verbal)) {
                    $res[$paramId] = $verbal;
                    break;
                }
            }
        }

        return $res;
    }


    /**
     * Условието на търсенето: текст с малки букви или числови граници (датата - като timestamp)
     *
     * @param string $value
     * @param string $match
     * @param string $valueTo
     *
     * @return array|null - match, needle, from, to; null при невалидна стойност
     */
    public static function getSearchCondition($value, $match, $valueTo = null)
    {
        $value = trim((string) $value);
        switch ($match) {
            case 'any':

                return array('match' => 'any');
            case 'gt':
            case 'gte':
            case 'lt':
            case 'lte':
            case 'between':
                $from = self::toSearchNumber($value);
                $to = ($match == 'between') ? self::toSearchNumber($valueTo) : null;
                if (!isset($from) || ($match == 'between' && !isset($to))) {

                    return null;
                }

                return array('match' => $match, 'from' => $from, 'to' => $to);
            default:

                return strlen($value) ? array('match' => ($match == 'exact') ? 'exact' : 'contains', 'needle' => mb_strtolower($value)) : null;
        }
    }


    /**
     * Число от търсената стойност: с точка или запетая, а дата - като timestamp, както я пази индексът
     *
     * @param mixed $value
     *
     * @return float|null
     */
    protected static function toSearchNumber($value)
    {
        $value = str_replace(',', '.', trim((string) $value));
        if (is_numeric($value)) {

            return (float) $value;
        }
        $time = strlen($value) ? strtotime($value) : false;

        return ($time === false) ? null : (float) $time;
    }


    /**
     * Изписването на стойност от индекса, ако отговаря на условието
     *
     * @param stdClass|null                 $pRec      - параметърът
     * @param cond_type_abstract_Proto|null $Driver    - драйверът му
     * @param int                           $productId
     * @param stdClass                      $iRec      - ред от индекса
     * @param array                         $cond      - @see getSearchCondition
     *
     * @return string|null
     */
    protected static function matchIndexRow($pRec, $Driver, $productId, $iRec, $cond)
    {
        Mode::push('text', 'plain');
        try {
            $verbal = $Driver ? $Driver->getIndexVerbal($pRec, cat_Products::getClassId(), $productId, $iRec) : ($iRec->valueKey ?? $iRec->valueNum);
        } finally {
            Mode::pop('text');
        }
        $verbal = trim(html_entity_decode(strip_tags((string) $verbal), ENT_QUOTES, 'UTF-8'));

        if ($cond['match'] == 'any') {

            return strlen($verbal) ? $verbal : (string) ($iRec->valueKey ?? $iRec->valueNum);
        }

        // Числата се сравняват по стойността, текстът без значение от регистъра
        if (isset($cond['from'])) {
            $num = $iRec->valueNum ?? null;
            if (!isset($num) && is_numeric(str_replace(',', '.', (string) ($iRec->valueKey ?? '')))) {
                $num = str_replace(',', '.', $iRec->valueKey);
            }
            if (!isset($num)) {

                return null;
            }
            $num = (float) $num;
            $ok = array('gt' => $num > $cond['from'], 'gte' => $num >= $cond['from'], 'lt' => $num < $cond['from'], 'lte' => $num <= $cond['from'],
                'between' => $num >= $cond['from'] && $num <= ($cond['to'] ?? $cond['from']));

            return $ok[$cond['match']] ? $verbal : null;
        }

        // Артикулът като стойност се изписва с кода си: [123] Име
        $texts = isset($iRec->valueKey) ? array($verbal, $iRec->valueKey, preg_replace('/^\[[^\]]*\]\s*/u', '', $verbal)) : array($verbal);

        return self::matchesValue($cond['needle'], $cond['match'], $texts) ? $verbal : null;
    }


    /**
     * Дали някой от текстовете съдържа или е равен на търсената стойност, без значение от регистъра
     *
     * @param string $needle - с малки букви
     * @param string $match  - contains или exact
     * @param array  $texts
     *
     * @return bool
     */
    protected static function matchesValue($needle, $match, $texts)
    {
        foreach ($texts as $text) {
            $text = mb_strtolower(trim((string) $text));
            if ($match == 'exact' ? ($text === $needle) : (mb_strpos($text, $needle) !== false)) {

                return true;
            }
        }

        return false;
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
     * Прави филтрируеми параметрите, чийто тип по подразбиране е филтрируем
     *
     * @return void
     */
    public static function setDefaultFilterableParams()
    {
        $Params = cls::get('cat_Params');
        $changed = array();
        $query = cat_Params::getQuery();
        $query->where("#state != 'rejected' AND #filterable != 'yes'");
        while ($rec = $query->fetch()) {
            if (cat_Params::getDefaultFilterable($rec) != 'yes') continue;

            // Артикулите се маркират накуп след цикъла
            $rec->filterable = 'yes';
            $rec->_skipParamIndex = true;
            $Params->save($rec, 'filterable');
            $changed[$rec->id] = $rec->id;
        }

        if (countR($changed)) {
            self::markDirtyByParams($changed);
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

        $cond = '#paramId IN (' . implode(',', array_map('intval', $paramIds)) . ')';
        self::delete($cond);
        cat_products_EshopParamIndex::delete($cond);
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
            $query->where('#productId > 0');
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

        $start = microtime(true);
        $eshop = cat_products_ParamIndexState::markEshopChanged();
        $eshopTime = microtime(true) - $start;

        $msg = "Нови артикули: {$added} (" . round($missingTime, 2) . " s), остарели: {$stale} (" . round($staleTime, 2) . " s), влезли/излезли от е-магазина: {$eshop} (" . round($eshopTime, 2) . ' s)';
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
}
