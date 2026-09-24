<?php


/**
 * Филтър на е-артикулите в групата по стойностите на параметрите им
 *
 * Стойностите идват от индекса на параметрите (@see cat_products_ParamIndex), а в URL-то
 * изборът е четим: pf=tsvyat-p12.cherven-1a2b3c.sin-4d5e6f_dalzhina-p15.10-5
 *
 * @category  bgerp
 * @package   eshop
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class eshop_ParamFilter
{
    /**
     * Параметър в URL-то с избора
     */
    const URL_VAR = 'pf';


    /**
     * След колко стойности на параметър останалите се скриват под „още“
     */
    public static $maxVisibleValues = 10;


    /**
     * Над колко секунди филтърът се записва в лога като бавен
     */
    public static $slowSecs = 0.5;


    /**
     * Времената по фази и бройките в текущия хит, за лога
     */
    protected static $stats = array();


    /**
     * Дали филтърът е включен за текущия домейн
     *
     * @return bool
     */
    public static function isEnabled()
    {
        $settings = cms_Domains::getSettings();

        return (($settings->paramFilters ?? 'no') == 'yes') && (($settings->showNavigation ?? 'no') == 'yes');
    }


    /**
     * Филтрира е-артикулите на групата по избраните стойности и подготвя филтъра за навигацията
     *
     * @param stdClass $data - данните от eshop_Products::prepareGroupList()
     *
     * @return void
     */
    public static function prepare($data)
    {
        $data->paramFilter = null;
        if (!countR($data->recs) || !self::isEnabled()) return;

        $start = self::startTimer('params');
        $params = self::getParams();
        self::stopTimer('params', $start);
        if (!countR($params)) return;

        // Изборът по неизвестен параметър се пропуска, а липсващата стойност остава и не намира нищо
        $selected = array();
        foreach (self::parseUrlValue(Request::get(self::URL_VAR, 'varchar')) as $paramId => $slugs) {
            if (isset($params[$paramId])) {
                $selected[$paramId] = $slugs;
            }
        }

        $lg = core_Lg::getCurrent();
        if (!array_key_exists($lg, core_Lg::getLangs())) {
            $lg = 'en';
        }
        $base = (object) array('eshopIds' => array_keys($data->recs), 'lg' => $lg);

        // Опциите, скрити в публичния изглед заради опаковките, не участват
        $start = self::startTimer('details');
        $base->hiddenDetailIds = self::getHiddenDetailIds($data->recs);
        self::stopTimer('details', $start);

        // Всичко се агрегира в MySQL - в PHP идват само различните стойности, не редовете на опциите
        $start = self::startTimer('values');
        $values = self::getValues($base, $params);
        self::stopTimer('values', $start);

        $start = self::startTimer('counts');
        $counts = self::countValues($base, $params, $values, $selected);
        self::stopTimer('counts', $start);

        $start = self::startTimer('match');
        $eshopCnt = countR($data->recs);
        if (countR($selected)) {
            $data->recs = array_intersect_key($data->recs, self::getMatchingEshopIds($base, $values, $selected));
        }
        self::stopTimer('match', $start);

        self::$stats['info'] = "е-артикули {$eshopCnt} → " . countR($data->recs) . ', скрити опции ' . countR($base->hiddenDetailIds) . ', стойности ' . array_sum(array_map('countR', $values)) . ', избрани параметри ' . countR($selected);
        $data->paramFilter = (object) array('params' => $params, 'values' => $values, 'counts' => $counts, 'selected' => $selected);
    }


    /**
     * Опциите, чиито опаковки не се показват публично (@see eshop_Products::prepareGroupList())
     *
     * Разрешените опаковки се наследяват като в eshop_Products::getSettingField() - от е-артикула,
     * групата му или домейна, но накуп вместо по заявка за всеки е-артикул
     *
     * @param array $eshopRecs - записите на е-артикулите
     *
     * @return array - ид-та на детайли
     */
    protected static function getHiddenDetailIds($eshopRecs)
    {
        $groupIds = array();
        foreach ($eshopRecs as $eRec) {
            if (empty($eRec->showPacks) && !empty($eRec->groupId)) {
                $groupIds[$eRec->groupId] = $eRec->groupId;
            }
        }

        $groups = array();
        if (countR($groupIds)) {
            $gQuery = eshop_Groups::getQuery();
            $gQuery->in('id', $groupIds);
            $gQuery->show('id,showPacks,menuId');
            $groups = $gQuery->fetchAll();
        }

        $allowedPacks = $packsByMenu = array();
        foreach ($eshopRecs as $eshopId => $eRec) {
            $gRec = $groups[$eRec->groupId ?? null] ?? null;
            if (!empty($eRec->showPacks)) {
                $allowed = keylist::toArray($eRec->showPacks);
            } elseif (!empty($gRec->showPacks)) {
                $allowed = keylist::toArray($gRec->showPacks);
            } else {
                $menuId = $gRec->menuId ?? null;
                if (!array_key_exists($menuId, $packsByMenu)) {
                    $packsByMenu[$menuId] = array();
                    if (!empty($menuId)) {
                        $settings = cms_Domains::getSettings(cms_Content::fetchField($menuId, 'domainId'));
                        $packsByMenu[$menuId] = keylist::toArray($settings->showPacks ?? null);
                    }
                }
                $allowed = $packsByMenu[$menuId];
            }

            if (countR($allowed)) {
                $allowedPacks[$eshopId] = $allowed;
            }
        }

        $res = array();
        if (!countR($allowedPacks)) {

            return $res;
        }

        $dQuery = eshop_ProductDetails::getQuery();
        $dQuery->where("#state = 'active'");
        $dQuery->in('eshopProductId', array_keys($allowedPacks));
        $dQuery->show('id,eshopProductId,packagings');
        while ($dRec = $dQuery->fetch()) {
            if (!countR(array_intersect_key(keylist::toArray($dRec->packagings), $allowedPacks[$dRec->eshopProductId]))) {
                $res[$dRec->id] = $dRec->id;
            }
        }

        return $res;
    }


    /**
     * Заявка по индекса, ограничена до видимите опции на е-артикулите
     *
     * core_Query прави JOIN ... ON само ако EXT поле е в show() - затова детайлът се показва и групира
     * по detailState, което е еднакво за всички редове. Числото е текстът му от базата, за да съвпадат
     * групирането, слъгът и условието (MariaDB връща double с 16 значещи цифри)
     *
     * @param stdClass $base - е-артикули, скрити опции и език
     *
     * @return core_Query
     */
    protected static function getIndexQuery($base)
    {
        $onCond = array('onCond' => '#eshop_ProductDetails.productId = #productId', 'join' => 'INNER');
        $query = cat_products_ParamIndex::getQuery();
        $query->EXT('eshopProductId', 'eshop_ProductDetails', array('externalName' => 'eshopProductId') + $onCond);
        $query->EXT('detailId', 'eshop_ProductDetails', array('externalName' => 'id') + $onCond);
        $query->EXT('detailState', 'eshop_ProductDetails', array('externalName' => 'state') + $onCond);
        $query->in('eshopProductId', $base->eshopIds);
        $query->where("#detailState = 'active'");
        if (countR($base->hiddenDetailIds)) {
            $query->notIn('detailId', $base->hiddenDetailIds);
        }
        $query->where(array("#lg = '' OR #lg = '[#1#]'", $base->lg));
        $query->XPR('valueNumText', 'varchar', 'CAST(#valueNum AS CHAR)');

        return $query;
    }


    /**
     * Различните стойности на параметрите сред опциите - изписванията на една стойност
     * („Котка“, „КотКа“) са една отметка и се показва най-честото
     *
     * @param stdClass $base
     * @param array    $params
     *
     * @return array - параметър => слъг => ред с valueKey, valueNum, valueVerbal и productId за вербализиране
     */
    protected static function getValues($base, $params)
    {
        $query = self::getIndexQuery($base);
        $query->in('paramId', array_keys($params));
        $query->XPR('rowsCnt', 'int', 'COUNT(#id)');
        $query->XPR('anyProductId', 'int', 'MIN(#productId)');
        $query->groupBy('paramId,valueKey,valueNumText,valueVerbal,detailState');
        $query->orderBy('rowsCnt', 'DESC');
        $query->show('paramId,valueKey,valueNumText,valueVerbal,rowsCnt,anyProductId,detailState');

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
     * Броят е-артикули по стойност - за всеки параметър при избора по останалите, в една заявка
     *
     * @param stdClass $base
     * @param array    $params   - параметрите
     * @param array    $values   - известните стойности
     * @param array    $selected - параметър => избрани слъгове
     *
     * @return array - параметър => слъг => брой е-артикули
     */
    protected static function countValues($base, $params, $values, $selected)
    {
        // Редът се брои, ако опцията му изпълнява избора по всички параметри без собствения си
        $conds = array();
        foreach ($selected as $paramId => $slugs) {
            $conds[] = '(#paramId = ' . (int) $paramId . ' OR ' . self::getValueCondition($paramId, $values[$paramId] ?? array(), $slugs, $base->lg) . ')';
        }
        $eshopExpr = countR($conds) ? 'IF(' . implode(' AND ', $conds) . ', #eshopProductId, NULL)' : '#eshopProductId';

        $query = self::getIndexQuery($base);
        $query->in('paramId', array_keys($params));
        $query->XPR('eshopCnt', 'int', "COUNT(DISTINCT {$eshopExpr})");
        $query->groupBy('paramId,valueKey,valueNumText,detailState');
        $query->show('paramId,valueKey,valueNumText,eshopCnt,detailState');

        $counts = array();
        while ($rec = $query->fetch()) {
            $rec->valueNum = $rec->valueNumText;
            $counts[$rec->paramId][self::getValueSlug($rec)] = (int) $rec->eshopCnt;
        }

        return $counts;
    }


    /**
     * Е-артикулите, в които поне една видима опция отговаря на всички избрани параметри
     *
     * @param stdClass $base
     * @param array    $values
     * @param array    $selected
     *
     * @return array - ид => ид
     */
    protected static function getMatchingEshopIds($base, $values, $selected)
    {
        $query = eshop_ProductDetails::getQuery();
        $query->in('eshopProductId', $base->eshopIds);
        $query->where("#state = 'active'");
        if (countR($base->hiddenDetailIds)) {
            $query->notIn('id', $base->hiddenDetailIds);
        }
        foreach ($selected as $paramId => $slugs) {
            $query->where(self::getValueCondition($paramId, $values[$paramId] ?? array(), $slugs, $base->lg));
        }
        $query->groupBy('eshopProductId');
        $query->show('eshopProductId');

        $res = array();
        while ($rec = $query->fetch()) {
            $res[$rec->eshopProductId] = $rec->eshopProductId;
        }

        return $res;
    }


    /**
     * Условие „артикулът има някоя от избраните стойности на параметъра“
     *
     * Подзаявката е с физическите имена, защото е по същата таблица като основната
     *
     * @param int    $paramId
     * @param array  $paramValues - слъг => ред от индекса
     * @param array  $slugs       - избраните слъгове
     * @param string $lg
     *
     * @return string
     */
    protected static function getValueCondition($paramId, $paramValues, $slugs, $lg)
    {
        $Index = cls::get('cat_products_ParamIndex');
        $keys = $nums = array();
        foreach (array_intersect_key($paramValues, $slugs) as $rec) {
            if (isset($rec->valueKey)) {
                $keys[] = "'" . $Index->db->escape($rec->valueKey) . "'";
            } else {
                $nums[] = "'" . $Index->db->escape($rec->valueNum) . "'";
            }
        }

        // Липсващата стойност не намира нищо, вместо да махне ограничението
        if (!countR($keys) && !countR($nums)) {

            return '1 = 0';
        }

        $col = function ($name) {
            return '`' . str::phpToMysqlName($name) . '`';
        };
        $valueConds = array();
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
     * Рендира филтъра за страничната навигация
     *
     * @param stdClass $data - данните от eshop_Products::prepareGroupList()
     *
     * @return core_ET
     */
    public static function renderNavigation($data)
    {
        $start = self::startTimer('render');
        $tpl = self::doRenderNavigation($data);
        self::stopTimer('render', $start);
        self::logStats($data->groupId ?? null);

        return $tpl;
    }


    /**
     * Рендира филтъра, след като е пуснат таймерът
     *
     * @param stdClass $data
     *
     * @return core_ET
     */
    protected static function doRenderNavigation($data)
    {
        $tpl = new core_ET('');
        $filter = $data->paramFilter ?? null;
        if (!is_object($filter)) {

            return $tpl;
        }

        $blocks = '';
        foreach ($filter->params as $paramId => $pRec) {
            $paramValues = $filter->values[$paramId] ?? array();
            $selected = $filter->selected[$paramId] ?? array();

            // Параметър с една стойност не филтрира нищо
            if (countR($paramValues) < 2 && !countR($selected)) continue;

            $items = array();
            foreach (self::getSortedValues($pRec, $paramValues) as $slug => $verbal) {
                $cnt = $filter->counts[$paramId][$slug] ?? 0;
                $isChecked = isset($selected[$slug]);
                $class = 'eshop-param-filter-value' . ($isChecked ? ' checked' : '');
                $caption = "<span class='eshop-param-check'></span>{$verbal} <span class='eshop-param-count'>({$cnt})</span>";
                if (!$cnt && !$isChecked) {
                    $items[$slug] = "<span class='{$class} disabled'>{$caption}</span>";
                } else {
                    $items[$slug] = ht::createLink($caption, self::getToggleUrl($filter, $paramId, $slug), false, array('class' => $class, 'rel' => 'nofollow'));
                }
            }

            // Избраните стойности, които вече ги няма, остават за махане
            foreach (array_diff_key($selected, $paramValues) as $slug) {
                $caption = "<span class='eshop-param-check'></span>" . type_Varchar::escape(self::getSlugCaption($slug)) . " <span class='eshop-param-count'>(0)</span>";
                $items[$slug] = ht::createLink($caption, self::getToggleUrl($filter, $paramId, $slug), false, array('class' => 'eshop-param-filter-value checked', 'rel' => 'nofollow'));
            }

            // Стойностите над лимита се скриват, освен ако някоя от тях е избрана
            $visible = array_slice($items, 0, self::$maxVisibleValues, true);
            $more = array_slice($items, self::$maxVisibleValues, null, true);
            $html = implode('', $visible);
            if (countR($more)) {
                $open = countR(array_intersect_key($more, $selected)) ? ' open' : '';
                $html .= "<details class='eshop-param-filter-more'{$open}><summary>" . tr('още||more') . '</summary>' . implode('', $more) . '</details>';
            }

            $open = countR($selected) ? ' open' : '';
            // Като в списъците - с групата и суфикса, иначе еднакво наречените не се различават
            $caption = str::mbUcfirst(cat_Params::getVerbal($pRec, 'typeExt'));
            $blocks .= "<details class='eshop-param-filter-param'{$open}><summary>{$caption}</summary><div class='eshop-param-filter-values'>{$html}</div></details>";
        }

        if (!strlen($blocks)) {

            return $tpl;
        }

        $tpl = new core_ET("<div class='eshop-param-filter'><div class='eshop-param-filter-title'>[#TITLE#] [#CLEAR#]</div>[#PARAMS#]</div>");
        $tpl->replace(tr('Филтри||Filters'), 'TITLE');
        $tpl->replace($blocks, 'PARAMS');
        if (countR($filter->selected)) {
            $clearUrl = getCurrentUrl();
            unset($clearUrl[self::URL_VAR], $clearUrl['P']);
            $tpl->replace(ht::createLink(tr('изчисти||clear'), $clearUrl, false, array('class' => 'eshop-param-filter-clear', 'rel' => 'nofollow')), 'CLEAR');
        }

        return $tpl;
    }


    /**
     * Записва в лога времената по фази - бавните винаги, бързите само в debug режим
     *
     * @param int|null $groupId
     *
     * @return void
     */
    protected static function logStats($groupId)
    {
        // Без подготовка филтърът е изключен и няма какво да се запише
        if (!isset(self::$stats['params'])) {
            self::$stats = array();

            return;
        }

        $total = 0;
        $phases = array();
        foreach (array('params', 'details', 'values', 'counts', 'match', 'render') as $name) {
            $time = self::$stats[$name] ?? 0;
            $total += $time;
            $phases[] = "{$name} " . round($time, 4);
        }

        $msg = 'Филтър по параметри: ' . round($total, 4) . ' s (' . implode(', ', $phases) . ')';
        if (isset(self::$stats['info'])) {
            $msg .= ', ' . self::$stats['info'];
        }
        self::$stats = array();

        // Бързите се пишат само при диагностика, иначе всяко показване (и от ботове) става запис
        if ($total >= self::$slowSecs) {
            eshop_Groups::logNotice($msg, $groupId);
        } elseif (isDebug()) {
            eshop_Groups::logDebug($msg, $groupId);
        }
    }


    /**
     * Пуска таймер за фаза
     *
     * @param string $name
     *
     * @return float - началото
     */
    protected static function startTimer($name)
    {
        core_Debug::startTimer("ESHOP_PARAM_FILTER_{$name}");

        return microtime(true);
    }


    /**
     * Спира таймера и натрупва времето във фазата
     *
     * @param string $name
     * @param float  $start
     *
     * @return void
     */
    protected static function stopTimer($name, $start)
    {
        core_Debug::stopTimer("ESHOP_PARAM_FILTER_{$name}");
        self::$stats[$name] = (self::$stats[$name] ?? 0) + (microtime(true) - $start);
    }


    /**
     * Филтрируемите параметри, които се показват публично, по реда им
     *
     * @return array
     */
    protected static function getParams()
    {
        $params = array();
        foreach (cat_products_ParamIndex::getFilterableParams() as $pRec) {
            if (($pRec->state ?? null) == 'active' && ($pRec->showInPublicDocuments ?? null) == 'yes') {
                $params[$pRec->id] = $pRec;
            }
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
     * Вербалните стойности на параметъра, подредени за показване
     *
     * @param stdClass $pRec        - параметърът
     * @param array    $paramValues - слъг => ред от индекса
     *
     * @return array - слъг => вербална стойност
     */
    protected static function getSortedValues($pRec, $paramValues)
    {
        $Driver = cat_Params::getDriver($pRec);
        $suffix = !empty($pRec->suffix) ? ' ' . tr($pRec->suffix) : '';
        $productClassId = cat_Products::getClassId();

        $sortKeys = $res = array();
        foreach ($paramValues as $slug => $iRec) {
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
     * URL-то на текущата страница с добавена или махната стойност, от първата страница
     *
     * @param stdClass $filter  - подготвеният филтър
     * @param int      $paramId - параметър
     * @param string   $slug    - слъг на стойността
     *
     * @return array
     */
    protected static function getToggleUrl($filter, $paramId, $slug)
    {
        $selected = $filter->selected;
        if (isset($selected[$paramId][$slug])) {
            unset($selected[$paramId][$slug]);
        } else {
            $selected[$paramId][$slug] = $slug;
        }

        $url = getCurrentUrl();
        unset($url['P']);
        $urlValue = self::buildUrlValue($selected, $filter->params);
        if (strlen($urlValue)) {
            $url[self::URL_VAR] = $urlValue;
        } else {
            unset($url[self::URL_VAR]);
        }

        return $url;
    }


    /**
     * Стойността на избора за URL-то
     *
     * @param array $selected - параметър => слъг => слъг
     * @param array $params   - параметрите
     *
     * @return string
     */
    protected static function buildUrlValue($selected, $params)
    {
        ksort($selected);
        $parts = array();
        foreach ($selected as $paramId => $slugs) {
            if (!countR($slugs) || !isset($params[$paramId])) continue;

            $paramSlug = strtolower(str::canonize($params[$paramId]->name));
            $paramSlug = (strlen($paramSlug) ? "{$paramSlug}-" : '') . "p{$paramId}";
            $parts[] = $paramSlug . '.' . implode('.', $slugs);
        }

        return implode('_', $parts);
    }


    /**
     * Разчита избора от URL-то - името на параметъра е само за четимост, важи ид-то след „p“
     *
     * @param string|null $urlValue
     *
     * @return array - параметър => слъг => слъг
     */
    protected static function parseUrlValue($urlValue)
    {
        $res = array();
        foreach (explode('_', (string) $urlValue) as $part) {
            $slugs = explode('.', $part);
            $paramSlug = array_shift($slugs);
            if (!preg_match('/(?:^|-)p(\d+)$/', $paramSlug, $matches)) continue;

            foreach ($slugs as $slug) {
                if (strlen($slug)) {
                    $res[(int) $matches[1]][$slug] = $slug;
                }
            }
        }

        return $res;
    }


    /**
     * Слъгът на индексирана стойност - четим текст и къс хеш на самата стойност, за да не се сливат различни
     *
     * @param stdClass $iRec - ред от индекса
     *
     * @return string
     */
    protected static function getValueSlug($iRec)
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
     * Четимата част на слъга - за избрана стойност, която вече я няма в групата
     *
     * @param string $slug
     *
     * @return string
     */
    protected static function getSlugCaption($slug)
    {
        $caption = preg_replace('/-[0-9a-f]{6}$/', '', $slug);

        return str_replace('-', ' ', $caption);
    }
}
