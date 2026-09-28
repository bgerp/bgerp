<?php


/**
 * Филтър на е-артикулите в групата по стойностите на параметрите им
 *
 * Тук е само специфичното за магазина - опциите на е-артикулите, скритите опаковки и показването;
 * общата логика е в cat_products_ParamFilter
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
     * Параметър в URL-то с избраните категории при търсенето
     */
    const GROUP_URL_VAR = 'pc';


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

        // Стойностите са и от подгрупите, въпреки че без избор се показват само е-артикулите на групата
        $allRecs = $data->recs + ($data->subgroupRecs ?? array());
        if (!countR($allRecs) || !self::isEnabled()) return;

        $isSearch = ($data->groupId == eshop_Groups::SEARCH_SYSTEM_ID);
        $start = self::startTimer('params');
        $params = cat_products_ParamFilter::getParams(true);
        self::stopTimer('params', $start);
        if (!countR($params) && !$isSearch) return;

        $selected = cat_products_ParamFilter::parseSelection(Request::get(self::URL_VAR, 'varchar'), $params);
        $lg = cat_products_ParamFilter::getLang();

        // При търсенето изборът на категория стеснява е-артикулите, по които се броят параметрите
        $selectedGroups = $isSearch ? self::parseGroups(Request::get(self::GROUP_URL_VAR, 'varchar')) : array();
        $recs = $allRecs;
        if (countR($selectedGroups)) {
            $recs = array_filter($allRecs, function ($rec) use ($selectedGroups) {
                return isset($selectedGroups[$rec->groupId ?? null]);
            });
            $data->recs = $recs;
        }

        // Опциите, скрити в публичния изглед заради опаковките, не участват
        $start = self::startTimer('details');
        $hiddenDetailIds = self::getHiddenDetailIds($allRecs);
        self::stopTimer('details', $start);
        $base = self::makeBase($recs, $hiddenDetailIds, $lg);
        $allBase = self::makeBase($allRecs, $hiddenDetailIds, $lg);

        // Стойностите са от всички намерени, а бройките - от избраните категории; броят се е-артикулите, не опциите им
        if (countR($params)) {
            $filter = cat_products_ParamFilter::prepare(self::getIndexQuery($allBase), $params, $selected, $lg, 'eshopProductId', 'detailState', self::getIndexQuery($base));
            foreach ($filter->times as $name => $time) {
                self::$stats[$name] = (self::$stats[$name] ?? 0) + $time;
            }
        } else {
            $filter = (object) array('params' => array(), 'selected' => array(), 'lg' => $lg, 'values' => array(), 'counts' => array(), 'times' => array());
        }

        $start = self::startTimer('match');
        $eshopCnt = countR($allRecs);
        if ($isSearch) {
            $filter->groups = self::countGroups($allRecs, $selectedGroups, countR($selected) ? self::getMatchingEshopIds($allBase, $filter->values, $selected) : null);
        }
        if (countR($selected)) {
            $data->recs = array_intersect_key($recs, self::getMatchingEshopIds($base, $filter->values, $selected));
        }
        self::stopTimer('match', $start);

        self::$stats['info'] = "е-артикули {$eshopCnt} → " . countR($data->recs) . ', скрити опции ' . countR($hiddenDetailIds) . ', стойности ' . array_sum(array_map('countR', $filter->values)) . ', избрани параметри ' . countR($selected) . ', категории ' . countR($selectedGroups);
        $data->paramFilter = $filter;
    }


    /**
     * Кръгът от е-артикули за заявките
     *
     * @param array  $eshopRecs
     * @param array  $hiddenDetailIds
     * @param string $lg
     *
     * @return stdClass
     */
    protected static function makeBase($eshopRecs, $hiddenDetailIds, $lg)
    {
        // Празният кръг не трябва да стане „без ограничение“
        $eshopIds = countR($eshopRecs) ? array_keys($eshopRecs) : array(0);

        return (object) array('eshopIds' => $eshopIds, 'hiddenDetailIds' => $hiddenDetailIds, 'lg' => $lg);
    }


    /**
     * Бройките по категория (основната група на е-артикула) при избора по параметри
     *
     * @param array      $eshopRecs - намерените е-артикули
     * @param array      $selected  - избраните групи
     * @param array|null $matched   - е-артикулите, отговарящи на параметрите, или null без избор
     *
     * @return stdClass - selected, counts (група => брой) и names (група => име)
     */
    protected static function countGroups($eshopRecs, $selected, $matched)
    {
        $counts = array();
        foreach ($eshopRecs as $id => $rec) {
            if (empty($rec->groupId)) continue;

            $counts[$rec->groupId] = ($counts[$rec->groupId] ?? 0) + ((!isset($matched) || isset($matched[$id])) ? 1 : 0);
        }
        $counts += array_fill_keys(array_keys($selected), 0);

        $names = array();
        if (countR($counts)) {
            $gQuery = eshop_Groups::getQuery();
            $gQuery->in('id', array_keys($counts));
            $gQuery->show('id,name');
            while ($gRec = $gQuery->fetch()) {
                $names[$gRec->id] = eshop_Groups::getVerbal($gRec, 'name');
            }
        }

        return (object) array('selected' => $selected, 'counts' => $counts, 'names' => $names);
    }


    /**
     * Избраните категории от URL-то: slug-4.slug-3 - важи числото накрая
     *
     * @param string|null $urlValue
     *
     * @return array - група => група
     */
    protected static function parseGroups($urlValue)
    {
        $res = array();
        foreach (explode('.', (string) $urlValue) as $part) {
            if (preg_match('/(?:^|-)(\d+)$/', $part, $matches)) {
                $res[(int) $matches[1]] = (int) $matches[1];
            }
        }

        return $res;
    }


    /**
     * URL-то на текущата страница с добавена или махната категория, от първата страница
     *
     * @param stdClass $groups  - @see countGroups
     * @param int      $groupId
     *
     * @return array
     */
    protected static function getGroupToggleUrl($groups, $groupId)
    {
        $selected = $groups->selected;
        if (isset($selected[$groupId])) {
            unset($selected[$groupId]);
        } else {
            $selected[$groupId] = $groupId;
        }

        $parts = array();
        foreach ($selected as $id) {
            $slug = strtolower(str::canonize(html_entity_decode(strip_tags((string) ($groups->names[$id] ?? '')), ENT_QUOTES, 'UTF-8')));
            $parts[] = (strlen($slug) ? "{$slug}-" : '') . $id;
        }

        $url = getCurrentUrl();
        unset($url['P']);
        if (countR($parts)) {
            $url[self::GROUP_URL_VAR] = implode('.', $parts);
        } else {
            unset($url[self::GROUP_URL_VAR]);
        }

        return $url;
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
     * по detailState, което е еднакво за всички редове
     *
     * @param stdClass $base - е-артикули, скрити опции и език
     *
     * @return core_Query
     */
    protected static function getIndexQuery($base)
    {
        $onCond = array('onCond' => '#eshop_ProductDetails.productId = #productId', 'join' => 'INNER');
        $query = cat_products_ParamFilter::getIndexQuery($base->lg);
        $query->EXT('eshopProductId', 'eshop_ProductDetails', array('externalName' => 'eshopProductId') + $onCond);
        $query->EXT('detailId', 'eshop_ProductDetails', array('externalName' => 'id') + $onCond);
        $query->EXT('detailState', 'eshop_ProductDetails', array('externalName' => 'state') + $onCond);
        $query->in('eshopProductId', $base->eshopIds);
        $query->where("#detailState = 'active'");
        if (countR($base->hiddenDetailIds)) {
            $query->notIn('detailId', $base->hiddenDetailIds);
        }

        return $query;
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
        cat_products_ParamFilter::applySelection($query, $values, $selected, $base->lg);
        $query->groupBy('eshopProductId');
        $query->show('eshopProductId');

        $res = array();
        while ($rec = $query->fetch()) {
            $res[$rec->eshopProductId] = $rec->eshopProductId;
        }

        return $res;
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

        $blocks = self::renderGroupsBlock($filter->groups ?? null);
        foreach (cat_products_ParamFilter::getDisplayParams($filter) as $paramId => $param) {
            $items = array();
            foreach ($param->items as $slug => $item) {
                $class = 'eshop-param-filter-value' . ($item->isChecked ? ' checked' : '');
                $caption = "<span class='eshop-param-check'></span>{$item->caption} <span class='eshop-param-count'>({$item->cnt})</span>";
                if ($item->isDisabled) {
                    $items[$slug] = "<span class='{$class} disabled'>{$caption}</span>";
                } else {
                    $items[$slug] = ht::createLink($caption, self::getToggleUrl($filter, $paramId, $slug), false, array('class' => $class, 'rel' => 'nofollow'));
                }
            }

            // Стойностите над лимита се скриват, освен ако някоя от тях е избрана
            $visible = array_slice($items, 0, self::$maxVisibleValues, true);
            $more = array_slice($items, self::$maxVisibleValues, null, true);
            $html = implode('', $visible);
            if (countR($more)) {
                $open = countR(array_intersect_key($more, $filter->selected[$paramId] ?? array())) ? ' open' : '';
                $html .= "<details class='eshop-param-filter-more'{$open}><summary>" . tr('още||more') . '</summary>' . implode('', $more) . '</details>';
            }

            $open = $param->isOpen ? ' open' : '';
            $blocks .= "<details class='eshop-param-filter-param'{$open}><summary>{$param->caption}</summary><div class='eshop-param-filter-values'>{$html}</div></details>";
        }

        if (!strlen($blocks)) {

            return $tpl;
        }

        $tpl = new core_ET("<div class='eshop-param-filter'><div class='eshop-param-filter-title'>[#TITLE#] [#CLEAR#]</div>[#PARAMS#]</div>");
        $tpl->replace(tr('Филтри||Filters'), 'TITLE');
        $tpl->replace($blocks, 'PARAMS');
        if (countR($filter->selected) || countR($filter->groups->selected ?? array())) {
            $clearUrl = getCurrentUrl();
            unset($clearUrl[self::URL_VAR], $clearUrl[self::GROUP_URL_VAR], $clearUrl['P']);
            $tpl->replace(ht::createLink(tr('изчисти||clear'), $clearUrl, false, array('class' => 'eshop-param-filter-clear', 'rel' => 'nofollow')), 'CLEAR');
        }

        return $tpl;
    }


    /**
     * Секцията „Категория“ при търсенето
     *
     * @param stdClass|null $groups - @see countGroups
     *
     * @return string
     */
    protected static function renderGroupsBlock($groups)
    {
        if (!is_object($groups) || (countR($groups->counts) < 2 && !countR($groups->selected))) {

            return '';
        }

        // Първо с най-много намерени
        $counts = $groups->counts;
        arsort($counts);
        $html = '';
        foreach ($counts as $groupId => $cnt) {
            $isChecked = isset($groups->selected[$groupId]);
            $class = 'eshop-param-filter-value' . ($isChecked ? ' checked' : '');
            $caption = "<span class='eshop-param-check'></span>" . ($groups->names[$groupId] ?? $groupId) . " <span class='eshop-param-count'>({$cnt})</span>";
            if (!$cnt && !$isChecked) {
                $html .= "<span class='{$class} disabled'>{$caption}</span>";
            } else {
                $html .= ht::createLink($caption, self::getGroupToggleUrl($groups, $groupId), false, array('class' => $class, 'rel' => 'nofollow'));
            }
        }

        return "<details class='eshop-param-filter-param' open><summary>" . tr('Категория||Category') . "</summary><div class='eshop-param-filter-values'>{$html}</div></details>";
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
        $selected = cat_products_ParamFilter::toggle($filter->selected, $paramId, $slug);

        $url = getCurrentUrl();
        unset($url['P']);
        $urlValue = cat_products_ParamFilter::buildUrlValue($selected, $filter->params);
        if (strlen($urlValue)) {
            $url[self::URL_VAR] = $urlValue;
        } else {
            unset($url[self::URL_VAR]);
        }

        return $url;
    }
}
