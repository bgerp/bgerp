<?php


/**
 * JSON-LD за каталога и групите в онлайн магазина
 *
 * Добавя jsonld_ProviderIntf към eshop_Groups - CollectionPage с пътечка и ItemList
 * с видимите на страницата подгрупи и е-артикули. Цени и Product възли няма -
 * те са само на страницата на е-артикула.
 *
 * @category  bgerp
 * @package   jsonld
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class jsonld_plg_EshopGroups extends core_Plugin
{
    /**
     * Добавя интерфейса за JSON-LD доставчик
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->declareInterface('jsonld_ProviderIntf');
    }


    /**
     * След рендиране на страницата на група
     */
    public static function on_AfterRenderGroup($mvc, &$res, $data)
    {
        // Търсенето, любимите и последно поръчаните не са истински групи
        if (!Mode::is('wrapper', 'cms_page_External') || ($data->groupId ?? 0) <= 0 || empty($data->rec)) {
            return;
        }

        // При избор по параметри е филтриран списък - каноничният е на цялата група
        $isFiltered = !empty($data->products->paramFilter->selected);

        jsonld_Aggregator::addFromProvider($mvc, (object) array(
            'menuId' => $data->rec->menuId,
            'groupRec' => $data->rec,
            'groups' => $isFiltered ? array() : self::getVisibleGroups($data->recs ?? array()),
            'products' => $isFiltered ? array() : self::getVisibleProducts(array($data->products ?? null)),
        ));
    }


    /**
     * След рендиране на каталога с плочките на групите (с навигация)
     */
    public static function on_AfterRenderAllGroups($mvc, &$res, $data)
    {
        // Подгрупите на група се рендират със същия метод - тях ги добавя on_AfterRenderGroup
        if (!Mode::is('wrapper', 'cms_page_External') || isset($data->groupId) || empty($data->menuId)) {
            return;
        }

        jsonld_Aggregator::addFromProvider($mvc, (object) array(
            'menuId' => $data->menuId,
            'groups' => self::getVisibleGroups($data->recs ?? array()),
        ));
    }


    /**
     * Стандартна реализация на jsonld_ProviderIntf::getJsonLdNodes()
     *
     * Класът или друг плъгин може да подаде свой резултат преди нея.
     */
    public static function on_AfterGetJsonLdNodes($mvc, &$res, $data)
    {
        if (isset($res)) {
            return;
        }

        $res = self::getNodes($data);
    }


    /**
     * Връща видимите е-артикули от подготвените списъци, както са подредени на страницата
     *
     * @param array $lists масив от данни на eshop_Products::prepareGroupList()
     *
     * @return array
     */
    public static function getVisibleProducts($lists)
    {
        $res = array();

        foreach ($lists as $list) {
            if (!is_object($list)) {
                continue;
            }

            // Любимите и последно поръчаните са лични за посетителя
            if (in_array($list->groupId ?? null, array(eshop_Favourites::FAVOURITE_SYSTEM_GROUP_ID, eshop_Carts::LAST_SALES_SYSTEM_ID))) {
                continue;
            }

            // Редовете са само за текущата страница на странирането
            foreach ((array) ($list->rows ?? array()) as $id => $row) {
                $pRec = $list->recs[$id] ?? null;

                // Скритите без цени се виждат само от debug потребители
                if (!$pRec || isset($res[$id]) || (($row->CLASS ?? '') == 'eshopHiddenRow')) {
                    continue;
                }

                $res[$id] = array(
                    'name' => tr(eshop_Products::getDisplayTitle($pRec)),
                    'url' => eshop_Products::getUrl($pRec, true),
                );
            }
        }

        return array_values($res);
    }


    /**
     * Връща пътечката до групата - меню и групите по веригата на родителите
     *
     * @param stdClass $groupRec
     *
     * @return array масив от array('name' => ..., 'url' => ...)
     */
    public static function getBreadcrumbItems($groupRec)
    {
        $groups = array();
        $used = array();

        for ($gRec = $groupRec; $gRec && empty($used[$gRec->id]); $gRec = eshop_Groups::fetch($gRec->saoParentId ?? null)) {
            $used[$gRec->id] = true;
            array_unshift($groups, array(
                'name' => eshop_Groups::getVerbal($gRec, 'name'),
                'url' => eshop_Groups::getUrl($gRec, true),
            ));

            if (empty($gRec->saoParentId)) {
                break;
            }
        }

        return array_merge(
            array(array(
                'name' => cms_Content::getVerbal($groupRec->menuId, 'menu'),
                'url' => cms_Content::getContentUrl($groupRec->menuId),
            )),
            $groups
        );
    }


    /**
     * Връща JSON-LD възлите за подготвените данни
     *
     * @param stdClass $data
     *
     * @return jsonld_Node[]
     */
    protected static function getNodes($data)
    {
        $groupRec = $data->groupRec ?? null;
        $properties = isset($groupRec) ? self::getGroupProperties($groupRec) : self::getCatalogProperties($data->menuId ?? null);

        if (!isset($properties)) {
            return array();
        }

        $itemList = self::getItemList(array_merge($data->groups ?? array(), $data->products ?? array()));

        if (isset($itemList)) {
            $properties['mainEntity'] = $itemList;
        }

        $key = isset($groupRec) ? 'eshop-group-' . $groupRec->id : 'eshop-catalog-' . $data->menuId;

        return array(new jsonld_Node('CollectionPage', $properties, null, $key));
    }


    /**
     * Връща свойствата на страницата на група - както са в нейното SEO
     *
     * @param stdClass $rec
     *
     * @return array|null
     */
    protected static function getGroupProperties($rec)
    {
        $name = jsonld_Helper::getCleanText(eshop_Groups::getVerbal($rec, 'name'));

        if ($name === null) {
            return null;
        }

        $properties = array(
            'name' => $name,
            'url' => toUrl(eshop_Groups::getUrl($rec, true), 'absolute'),
        );

        // Като meta description - цялото тяло слива таблиците (напр. цени) в неразбираем ред
        $description = jsonld_Helper::getCleanText($rec->seoDescription ?? null);

        if ($description === null && !empty($rec->info)) {
            $description = jsonld_Helper::getCleanText(cms_Content::getSeoDescription($rec->info));
        }

        if (isset($description)) {
            $properties['description'] = $description;
        }

        $imageUrl = jsonld_Helper::getImageUrl(!empty($rec->image) ? $rec->image : ($rec->icon ?? null));

        if (isset($imageUrl)) {
            $properties['image'] = $imageUrl;
        }

        // Последното ниво е текущата страница - без адрес
        $items = self::getBreadcrumbItems($rec);
        unset($items[countR($items) - 1]['url']);
        $breadcrumbs = jsonld_Helper::getBreadcrumbList($items, 'eshop-group-breadcrumb-' . $rec->id);

        if (isset($breadcrumbs)) {
            $properties['breadcrumb'] = $breadcrumbs;
        }

        return $properties;
    }


    /**
     * Връща свойствата на каталога на менюто - заглавието е видимото H1
     *
     * @param int|null $menuId
     *
     * @return array|null
     */
    protected static function getCatalogProperties($menuId)
    {
        $menuRec = cms_Content::fetch($menuId);
        $name = jsonld_Helper::getCleanText($menuRec->title ?? null);

        if ($name === null) {
            return null;
        }

        return array(
            'name' => $name,
            'url' => toUrl(cls::get('eshop_Groups')->getUrlByMenuId($menuId), 'absolute'),
        );
    }


    /**
     * Връща ItemList от видимите на страницата връзки
     *
     * @param array $items масив от array('name' => ..., 'url' => ..., 'image' => ...)
     *
     * @return jsonld_Node|null
     */
    protected static function getItemList($items)
    {
        $elements = array();

        foreach ($items as $item) {
            $name = jsonld_Helper::getCleanText($item['name'] ?? null);
            $url = is_array($item['url'] ?? null) ? toUrl($item['url'], 'absolute') : null;

            if ($name === null || empty($url)) {
                continue;
            }

            $properties = array(
                'position' => countR($elements) + 1,
                'name' => $name,
                'url' => $url,
            );

            // Само видимото на плочката - описанието е на страницата на групата
            $imageUrl = jsonld_Helper::getImageUrl($item['image'] ?? null);

            if (isset($imageUrl)) {
                $properties['image'] = $imageUrl;
            }

            $elements[] = new jsonld_Node('ListItem', $properties);
        }

        if (!countR($elements)) {
            return null;
        }

        return new jsonld_Node('ItemList', array(
            'numberOfItems' => countR($elements),
            'itemListElement' => $elements,
        ));
    }


    /**
     * Връща групите, които се виждат като плочки - renderAllGroups_() пропуска тези без икона
     *
     * @param array $recs
     *
     * @return array
     */
    protected static function getVisibleGroups($recs)
    {
        $res = array();

        foreach ((array) $recs as $rec) {
            if (empty($rec->icon)) {
                continue;
            }

            // Без менюто, през което е показана - адресът да е каноничният
            $gRec = clone $rec;
            unset($gRec->altMenuId);

            $res[] = array(
                'name' => eshop_Groups::getVerbal($gRec, 'name'),
                'url' => eshop_Groups::getUrl($gRec, true),
                'image' => $rec->icon,
            );
        }

        return $res;
    }
}
