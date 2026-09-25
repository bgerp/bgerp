<?php


/**
 * Филтриране на артикули по стойностите на параметрите им (@see cat_products_ParamIndex)
 *
 * Общата логика за външната и вътрешната част - кои артикули се филтрират, решава извикващият
 * с базовата заявка по индекса. Всичко се агрегира в MySQL, в PHP идват само различните стойности.
 * Изборът е параметър => слъг => слъг, а в URL-то е четим: tsvyat-p12.cherven-1a2b3c.sin-4d5e6f_dalzhina-p15.10-5
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
class cat_products_ParamFilter
{
    /**
     * До колко различни числа параметърът е с отделни стойности, при автоматичния режим
     */
    public static $maxValuesWithoutRanges = 12;


    /**
     * На колко диапазона се делят числата
     */
    public static $rangesCnt = 5;


    /**
     * Филтрируемите параметри по реда им
     *
     * @param bool $onlyPublic - само показваните във външни документи
     *
     * @return array - ид => запис
     */
    public static function getParams($onlyPublic = false)
    {
        $params = array();
        foreach (cat_products_ParamIndex::getFilterableParams() as $pRec) {
            if (($pRec->state ?? null) != 'active') continue;
            if ($onlyPublic && ($pRec->showInPublicDocuments ?? null) != 'yes') continue;

            $params[$pRec->id] = $pRec;
        }

        uasort($params, function ($a, $b) {
            $aOrder = $a->order ?? PHP_INT_MAX;
            $bOrder = $b->order ?? PHP_INT_MAX;
            if ($aOrder != $bOrder) {

                return ($aOrder < $bOrder) ? -1 : 1;
            }

            return strnatcasecmp($a->name ?? '', $b->name ?? '');
        });

        return $params;
    }


    /**
     * Езикът на текстовите стойности - текущият, а за неиндексиран език английският
     *
     * @return string
     */
    public static function getLang()
    {
        $lg = core_Lg::getCurrent();

        return array_key_exists($lg, core_Lg::getLangs()) ? $lg : 'en';
    }


    /**
     * Базова заявка по индекса - общите редове и тези на езика; извикващият добавя кои артикули
     *
     * Числото е текстът му от базата (valueNumText), за да съвпадат групирането, слъгът и условието -
     * MariaDB връща double с 16 значещи цифри
     *
     * @param string $lg
     *
     * @return core_Query
     */
    public static function getIndexQuery($lg)
    {
        $query = cat_products_ParamIndex::getQuery();
        $query->where(array("#lg = '' OR #lg = '[#1#]'", $lg));
        $query->XPR('valueNumText', 'varchar', 'CAST(#valueNum AS CHAR)');

        return $query;
    }


    /**
     * Подготвя филтъра - стойности и бройки при избора
     *
     * Кои обекти се филтрират, решава извикващият: той прилага избора (@see applySelection) към своята заявка
     *
     * @param core_Query   $baseQuery  - базовата заявка по индекса (@see getIndexQuery)
     * @param array        $params     - параметрите (@see getParams)
     * @param array        $selected   - параметър => избрани слъгове (@see parseSelection)
     * @param string       $lg         - езикът (@see getLang)
     * @param string       $countField - какво се брои (артикул, е-артикул...)
     * @param string|array $joinFields - EXT полета от JOIN-а на базовата заявка
     * @param core_Query   $countQuery - по-тясна заявка за бройките, ако стойностите са от по-широк кръг
     *
     * @return stdClass - params, selected, lg, values, counts и times (секунди по фази)
     */
    public static function prepare($baseQuery, $params, $selected, $lg, $countField = 'productId', $joinFields = array(), $countQuery = null)
    {
        $filter = (object) array('params' => $params, 'selected' => $selected, 'lg' => $lg, 'times' => array());

        $start = microtime(true);
        core_Debug::startTimer('PARAM_FILTER_values');
        $filter->values = self::groupRanges($params, self::getValues($baseQuery, $params, $joinFields));
        core_Debug::stopTimer('PARAM_FILTER_values');
        $filter->times['values'] = microtime(true) - $start;

        $start = microtime(true);
        core_Debug::startTimer('PARAM_FILTER_counts');
        $filter->counts = self::countValues($countQuery ?? $baseQuery, $params, $filter->values, $selected, $lg, $countField, $joinFields);
        core_Debug::stopTimer('PARAM_FILTER_counts');
        $filter->times['counts'] = microtime(true) - $start;

        return $filter;
    }


    /**
     * Какво да се покаже от подготвения филтър, независимо от вида - навигация, форма...
     *
     * Параметър с една стойност не филтрира нищо и се пропуска, освен ако има избор по него.
     * Избраната стойност, която вече я няма, остава, за да може да се махне
     *
     * @param stdClass $filter - @see prepare
     *
     * @return array - параметър => обект с caption, isOpen и items: слъг => обект с caption, cnt, isChecked, isDisabled
     */
    public static function getDisplayParams($filter)
    {
        $res = array();
        foreach ($filter->params as $paramId => $pRec) {
            $paramValues = $filter->values[$paramId] ?? array();
            $selected = $filter->selected[$paramId] ?? array();
            if (countR($paramValues) < 2 && !countR($selected)) continue;

            $items = array();
            foreach (self::getSortedValues($pRec, $paramValues) as $slug => $verbal) {
                $cnt = $filter->counts[$paramId][$slug] ?? 0;
                $isChecked = isset($selected[$slug]);
                $items[$slug] = (object) array('caption' => $verbal, 'cnt' => $cnt, 'isChecked' => $isChecked, 'isDisabled' => (!$cnt && !$isChecked));
            }
            foreach (array_diff_key($selected, $paramValues) as $slug) {
                $range = self::parseRangeSlug($slug);
                $caption = $range ? self::getRangeCaption($pRec, $range[0], $range[1]) : type_Varchar::escape(self::getSlugCaption($slug));
                $items[$slug] = (object) array('caption' => $caption, 'cnt' => 0, 'isChecked' => true, 'isDisabled' => false);
            }

            // Като в списъците - с групата и суфикса, иначе еднакво наречените не се различават
            $caption = str::mbUcfirst(cat_Params::getVerbal($pRec, 'typeExt'));
            $res[$paramId] = (object) array('caption' => $caption, 'isOpen' => countR($selected) > 0, 'items' => $items);
        }

        return $res;
    }


    /**
     * Различните стойности на параметрите - изписванията на една стойност („Котка“, „КотКа“)
     * са една отметка и се показва най-честото
     *
     * @param core_Query   $baseQuery  - базовата заявка (@see getIndexQuery)
     * @param array        $params     - параметрите
     * @param string|array $joinFields - EXT полета от JOIN-а (core_Query прави JOIN ... ON само ако са в show)
     *
     * @return array - параметър => слъг => ред с valueKey, valueNum, valueVerbal и productId за вербализиране
     */
    public static function getValues($baseQuery, $params, $joinFields = array())
    {
        $joinFields = arr::make($joinFields);
        $query = clone $baseQuery;
        $query->in('paramId', array_keys($params));
        $query->XPR('rowsCnt', 'int', 'COUNT(#id)');
        $query->XPR('anyProductId', 'int', 'MIN(#productId)');
        $query->groupBy(implode(',', array_merge(array('paramId', 'valueKey', 'valueNumText', 'valueVerbal'), $joinFields)));
        $query->orderBy('rowsCnt', 'DESC');
        $query->show(implode(',', array_merge(array('paramId', 'valueKey', 'valueNumText', 'valueVerbal', 'rowsCnt', 'anyProductId'), $joinFields)));

        $values = array();
        while ($rec = $query->fetch()) {
            $rec->valueNum = $rec->valueNumText;
            $slug = self::getValueSlug($rec);
            if (!isset($values[$rec->paramId][$slug])) {
                $rec->productId = $rec->anyProductId;
                $values[$rec->paramId][$slug] = $rec;
            }
        }

        return $values;
    }


    /**
     * Броят обекти по стойност - за всеки параметър при избора по останалите, в една заявка
     *
     * @param core_Query   $baseQuery  - базовата заявка
     * @param array        $params     - параметрите
     * @param array        $values     - известните стойности (@see getValues)
     * @param array        $selected   - параметър => избрани слъгове
     * @param string       $lg         - езикът
     * @param string       $countField - какво се брои (артикул, е-артикул...)
     * @param string|array $joinFields - EXT полета от JOIN-а
     *
     * @return array - параметър => слъг => брой
     */
    public static function countValues($baseQuery, $params, $values, $selected, $lg, $countField = 'productId', $joinFields = array())
    {
        // Редът се брои, ако артикулът му изпълнява избора по всички параметри без собствения си
        $conds = array();
        foreach ($selected as $paramId => $slugs) {
            $conds[] = '(#paramId = ' . (int) $paramId . ' OR ' . self::getValueCondition($paramId, $values[$paramId] ?? array(), $slugs, $lg) . ')';
        }
        $countExpr = countR($conds) ? 'IF(' . implode(' AND ', $conds) . ", #{$countField}, NULL)" : "#{$countField}";

        // Числата на параметрите с диапазони се групират по диапазон, а не по стойност
        $rangeCases = array();
        foreach ($values as $paramId => $paramValues) {
            if (!self::isRanged($paramValues)) continue;

            $when = array();
            foreach ($paramValues as $slug => $rRec) {
                $when[] = 'WHEN ' . self::getRangeSql('#valueNum', $rRec->from, $rRec->to) . " THEN '{$slug}'";
            }
            $rangeCases[] = 'WHEN #paramId = ' . (int) $paramId . ' THEN (CASE ' . implode(' ', $when) . ' END)';
        }

        $joinFields = arr::make($joinFields);
        $query = clone $baseQuery;
        $query->in('paramId', array_keys($params));
        $query->XPR('filterCnt', 'int', "COUNT(DISTINCT {$countExpr})");
        // Псевдонимът valueNumText не може да се ползва в друг израз от същия SELECT
        $numText = 'CAST(#valueNum AS CHAR)';
        $query->XPR('valueGroup', 'varchar', countR($rangeCases) ? ('CASE ' . implode(' ', $rangeCases) . " ELSE {$numText} END") : $numText);
        $query->groupBy(implode(',', array_merge(array('paramId', 'valueKey', 'valueGroup'), $joinFields)));
        $query->show(implode(',', array_merge(array('paramId', 'valueKey', 'valueGroup', 'filterCnt'), $joinFields)));

        $counts = array();
        while ($rec = $query->fetch()) {
            if (self::isRanged($values[$rec->paramId] ?? array())) {
                $slug = $rec->valueGroup;
            } else {
                $rec->valueNum = $rec->valueGroup;
                $slug = self::getValueSlug($rec);
            }
            if (isset($slug)) {
                $counts[$rec->paramId][$slug] = (int) $rec->filterCnt;
            }
        }

        return $counts;
    }


    /**
     * Условие „артикулът има някоя от избраните стойности на параметъра“ за заявка с поле productId
     *
     * Подзаявката е с физическите имена, защото може да е по същата таблица като основната
     *
     * @param int    $paramId
     * @param array  $paramValues - слъг => ред от индекса
     * @param array  $slugs       - избраните слъгове
     * @param string $lg
     *
     * @return string
     */
    public static function getValueCondition($paramId, $paramValues, $slugs, $lg)
    {
        $Index = cls::get('cat_products_ParamIndex');
        $col = function ($name) {
            return '`' . str::phpToMysqlName($name) . '`';
        };

        $keys = $nums = $valueConds = array();
        foreach ($slugs as $slug) {
            $rec = $paramValues[$slug] ?? null;

            // Диапазонът се разчита и от слъга, ако вече го няма сред стойностите
            $range = (is_object($rec) && !empty($rec->isRange)) ? array($rec->from, $rec->to) : (is_object($rec) ? null : self::parseRangeSlug($slug));
            if ($range) {
                $valueConds[] = self::getRangeSql($col('valueNum'), $range[0], $range[1]);
            } elseif (isset($rec->valueKey)) {
                $keys[] = "'" . $Index->db->escape($rec->valueKey) . "'";
            } elseif (is_object($rec)) {
                $nums[] = "'" . $Index->db->escape($rec->valueNum) . "'";
            }
        }

        // Липсващата стойност не намира нищо, вместо да махне ограничението
        if (!countR($keys) && !countR($nums) && !countR($valueConds)) {

            return '1 = 0';
        }

        if (countR($keys)) {
            $valueConds[] = $col('valueKey') . ' IN (' . implode(',', $keys) . ')';
        }
        if (countR($nums)) {
            $valueConds[] = 'CAST(' . $col('valueNum') . ' AS CHAR) IN (' . implode(',', $nums) . ')';
        }

        return "#productId IN (SELECT {$col('productId')} FROM `{$Index->dbTableName}` WHERE {$col('paramId')} = " . (int) $paramId
            . " AND {$col('lg')} IN ('', '" . $Index->db->escape($lg) . "') AND (" . implode(' OR ', $valueConds) . '))';
    }


    /**
     * Добавя към заявката условията за целия избор
     *
     * @param core_Query $query    - заявка с поле productId
     * @param array      $values   - известните стойности
     * @param array      $selected - параметър => избрани слъгове
     * @param string     $lg
     *
     * @return void
     */
    public static function applySelection($query, $values, $selected, $lg)
    {
        foreach ($selected as $paramId => $slugs) {
            $query->where(self::getValueCondition($paramId, $values[$paramId] ?? array(), $slugs, $lg));
        }
    }


    /**
     * Числата на параметрите с много различни стойности (или с изрично зададени диапазони) стават диапазони
     *
     * @param array $params
     * @param array $values - @see getValues
     *
     * @return array
     */
    public static function groupRanges($params, $values)
    {
        foreach ($values as $paramId => $paramValues) {
            $pRec = $params[$paramId] ?? null;
            if (!is_object($pRec) || !cat_Params::canBeRanged($pRec)) continue;

            $mode = $pRec->filterMode ?? 'auto';
            if ($mode == 'values' || ($mode != 'ranges' && countR($paramValues) <= self::$maxValuesWithoutRanges)) continue;

            $ranges = self::makeRanges($paramValues);
            if (countR($ranges) >= 2) {
                $values[$paramId] = $ranges;
            }
        }

        return $values;
    }


    /**
     * Диапазони с приблизително еднакъв брой редове и граници, закръглени до хубави числа
     *
     * Интервалите са [от, до) - първият без долна, последният без горна граница
     *
     * @param array $paramValues - слъг => ред с valueNum и rowsCnt
     *
     * @return array - слъг => обект с isRange, from, to и productId
     */
    protected static function makeRanges($paramValues)
    {
        $nums = array();
        $total = 0;
        $productId = null;
        foreach ($paramValues as $rec) {
            if (isset($rec->valueKey) || !is_numeric($rec->valueNum)) continue;

            $cnt = max(1, (int) ($rec->rowsCnt ?? 1));
            $nums[] = array((float) $rec->valueNum, $cnt);
            $total += $cnt;
            $productId = $productId ?? ($rec->productId ?? null);
        }
        if (countR($nums) < 2) {

            return array();
        }
        usort($nums, function ($a, $b) {
            return $a[0] <=> $b[0];
        });

        // Границата е между две съседни стойности, когато се натрупа поредната част от редовете
        $bounds = array();
        $step = $total / self::$rangesCnt;
        $cum = 0;
        $part = 1;
        $last = countR($nums) - 1;
        foreach ($nums as $i => $num) {
            $cum += $num[1];
            if ($i < $last && $part < self::$rangesCnt && $cum >= $part * $step) {
                $bounds[] = self::getNiceNumber($num[0], $nums[$i + 1][0]);
                while ($part < self::$rangesCnt && $cum >= $part * $step) {
                    $part++;
                }
            }
        }
        $bounds = array_values(array_unique($bounds, SORT_REGULAR));

        $res = array();
        $from = null;
        foreach (array_merge($bounds, array(null)) as $to) {
            $rec = (object) array('isRange' => true, 'from' => $from, 'to' => $to, 'productId' => $productId, 'valueKey' => null, 'valueNum' => null);
            $res[self::getRangeSlug($from, $to)] = $rec;
            $from = $to;
        }

        return $res;
    }


    /**
     * Най-кръглото число в (от, до] - 10, 20, 50, 100..., за да са границите четими
     *
     * @param float $low
     * @param float $high
     *
     * @return float
     */
    protected static function getNiceNumber($low, $high)
    {
        $maxAbs = max(abs($low), abs($high), 1e-9);
        for ($p = (int) floor(log10($maxAbs)) + 1; $p >= -9; $p--) {
            foreach (array(1, 0.5, 0.2) as $mult) {
                $step = $mult * pow(10, $p);
                $candidate = (floor($low / $step) + 1) * $step;
                if ($candidate > $low && $candidate <= $high) {

                    return round($candidate, max(0, 1 - $p));
                }
            }
        }

        return $high;
    }


    /**
     * Дали стойностите на параметъра са групирани в диапазони
     *
     * @param array $paramValues
     *
     * @return bool
     */
    protected static function isRanged($paramValues)
    {
        $first = reset($paramValues);

        return is_object($first) && !empty($first->isRange);
    }


    /**
     * SQL условие за диапазон [от, до)
     *
     * @param string     $field - поле или колона
     * @param float|null $from
     * @param float|null $to
     *
     * @return string
     */
    protected static function getRangeSql($field, $from, $to)
    {
        $conds = array();
        if (isset($from)) {
            $conds[] = "{$field} >= " . sprintf('%.17g', $from);
        }
        if (isset($to)) {
            $conds[] = "{$field} < " . sprintf('%.17g', $to);
        }

        return '(' . implode(' AND ', $conds) . ')';
    }


    /**
     * Надписът на диапазон - „до 10 см“, „10 – 20 см“, „от 50 см“
     *
     * @param stdClass   $pRec      - параметърът
     * @param float|null $from
     * @param float|null $to
     * @param int|null   $productId - артикул за вербализирането
     *
     * @return string
     */
    public static function getRangeCaption($pRec, $from, $to, $productId = null)
    {
        $Driver = cat_Params::getDriver($pRec);
        $productClassId = cat_Products::getClassId();
        $toVerbal = function ($num) use ($Driver, $pRec, $productClassId, $productId) {
            $iRec = (object) array('valueNum' => $num, 'valueKey' => null, 'valueVerbal' => null);

            return $Driver ? $Driver->getIndexVerbal($pRec, $productClassId, $productId, $iRec) : type_Varchar::escape($num);
        };
        $suffix = !empty($pRec->suffix) ? ' ' . tr($pRec->suffix) : '';

        if (!isset($from)) {

            return tr('до||under') . ' ' . $toVerbal($to) . $suffix;
        }
        if (!isset($to)) {

            return tr('от||from') . ' ' . $toVerbal($from) . $suffix;
        }

        return $toVerbal($from) . ' – ' . $toVerbal($to) . $suffix;
    }


    /**
     * Слъгът на диапазон: r10to20, rto10, r50to (10.5 -> 10-5, -3 -> m3)
     *
     * @param float|null $from
     * @param float|null $to
     *
     * @return string
     */
    public static function getRangeSlug($from, $to)
    {
        $toSlug = function ($num) {
            return isset($num) ? str_replace(array('-', '+', '.'), array('m', '', '-'), sprintf('%.15g', $num)) : '';
        };

        return 'r' . $toSlug($from) . 'to' . $toSlug($to);
    }


    /**
     * Границите на диапазон от слъга му
     *
     * @param string $slug
     *
     * @return array|null - [от, до] или null, ако не е диапазон
     */
    public static function parseRangeSlug($slug)
    {
        if (!preg_match('/^r([0-9me-]*)to([0-9me-]*)$/', (string) $slug, $matches)) {

            return null;
        }

        $bounds = array();
        foreach (array($matches[1], $matches[2]) as $part) {
            $num = str_replace(array('-', 'm'), array('.', '-'), $part);
            if (strlen($part) && !is_numeric($num)) {

                return null;
            }
            $bounds[] = strlen($part) ? (float) $num : null;
        }

        return (isset($bounds[0]) || isset($bounds[1])) ? $bounds : null;
    }


    /**
     * Вербалните стойности на параметъра, подредени за показване
     *
     * @param stdClass $pRec        - параметърът
     * @param array    $paramValues - слъг => ред от индекса
     *
     * @return array - слъг => вербална стойност
     */
    public static function getSortedValues($pRec, $paramValues)
    {
        $Driver = cat_Params::getDriver($pRec);
        $suffix = !empty($pRec->suffix) ? ' ' . tr($pRec->suffix) : '';
        $productClassId = cat_Products::getClassId();

        $sortKeys = $res = array();
        foreach ($paramValues as $slug => $iRec) {
            if (!empty($iRec->isRange)) {
                $res[$slug] = self::getRangeCaption($pRec, $iRec->from, $iRec->to, $iRec->productId);
                $sortKeys[$slug] = $iRec->from ?? -INF;
                continue;
            }
            $isNum = !isset($iRec->valueKey);
            $verbal = $Driver ? $Driver->getIndexVerbal($pRec, $productClassId, $iRec->productId, $iRec) : type_Varchar::escape($iRec->valueKey ?? $iRec->valueNum);
            $res[$slug] = $isNum ? $verbal . $suffix : $verbal;
            $sortKeys[$slug] = $isNum ? $iRec->valueNum : html_entity_decode(strip_tags((string) $verbal), ENT_QUOTES, 'UTF-8');
        }

        uksort($res, function ($a, $b) use ($sortKeys) {
            $aKey = $sortKeys[$a];
            $bKey = $sortKeys[$b];
            if (is_string($aKey) || is_string($bKey)) {

                return strnatcasecmp((string) $aKey, (string) $bKey);
            }

            return $aKey <=> $bKey;
        });

        return $res;
    }


    /**
     * Изборът от URL-то, само по известните параметри - липсващата стойност остава и не намира нищо
     *
     * @param string|null $urlValue
     * @param array       $params
     *
     * @return array - параметър => слъг => слъг
     */
    public static function parseSelection($urlValue, $params)
    {
        $res = array();
        foreach (explode('_', (string) $urlValue) as $part) {
            $slugs = explode('.', $part);

            // Името на параметъра е само за четимост, важи ид-то след „p“
            $paramSlug = array_shift($slugs);
            if (!preg_match('/(?:^|-)p(\d+)$/', $paramSlug, $matches) || !isset($params[(int) $matches[1]])) continue;

            foreach ($slugs as $slug) {
                if (strlen($slug)) {
                    $res[(int) $matches[1]][$slug] = $slug;
                }
            }
        }

        return $res;
    }


    /**
     * Изборът като стойност за URL-то
     *
     * @param array $selected - параметър => слъг => слъг
     * @param array $params   - параметрите
     *
     * @return string
     */
    public static function buildUrlValue($selected, $params)
    {
        ksort($selected);
        $parts = array();
        foreach ($selected as $paramId => $slugs) {
            if (!countR($slugs) || !isset($params[$paramId])) continue;

            $paramSlug = strtolower(str::canonize($params[$paramId]->name ?? ''));
            $paramSlug = (strlen($paramSlug) ? "{$paramSlug}-" : '') . "p{$paramId}";
            $parts[] = $paramSlug . '.' . implode('.', $slugs);
        }

        return implode('_', $parts);
    }


    /**
     * Изборът с добавена или махната стойност
     *
     * @param array  $selected
     * @param int    $paramId
     * @param string $slug
     *
     * @return array
     */
    public static function toggle($selected, $paramId, $slug)
    {
        if (isset($selected[$paramId][$slug])) {
            unset($selected[$paramId][$slug]);
        } else {
            $selected[$paramId][$slug] = $slug;
        }

        return $selected;
    }


    /**
     * Слъгът на индексирана стойност - четим текст и къс хеш на самата стойност, за да не се сливат различни
     *
     * @param stdClass $iRec - ред от индекса
     *
     * @return string
     */
    public static function getValueSlug($iRec)
    {
        if (isset($iRec->valueKey)) {
            $slug = strtolower(str::canonize($iRec->valueKey));
            $hash = substr(md5($iRec->valueKey), 0, 6);

            return strlen($slug) ? "{$slug}-{$hash}" : $hash;
        }

        // Числото е текстът му от базата, без хеш: 10.5 -> 10-5, -3 -> m3, 1e20 -> 1e20
        return str_replace(array('-', '+', '.'), array('m', '', '-'), (string) $iRec->valueNum);
    }


    /**
     * Четимата част на слъга - за избрана стойност, която вече я няма
     *
     * @param string $slug
     *
     * @return string
     */
    public static function getSlugCaption($slug)
    {
        $caption = preg_replace('/-[0-9a-f]{6}$/', '', $slug);

        return str_replace('-', ' ', $caption);
    }
}
