<?php


/**
 * JSON-LD за публичната CMS статия
 *
 * Добавя jsonld_ProviderIntf към cms_Articles със стандартна реализация - WebPage възел с пътечка.
 */
class jsonld_plg_CmsArticles extends core_Plugin
{
    /**
     * Добавя интерфейса за JSON-LD доставчик
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->declareInterface('jsonld_ProviderIntf');
    }


    /**
     * След определяне на показаната публична статия
     */
    public static function on_AfterPrepareArticle($mvc, $rec)
    {
        jsonld_Aggregator::addFromProvider(
            $mvc,
            (object) array('rec' => $rec)
        );
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
     * Връща JSON-LD възлите за подготвените данни
     *
     * @param stdClass $data
     *
     * @return jsonld_Node[]
     */
    protected static function getNodes($data)
    {
        if (empty($data->rec)
            || !$data->rec instanceof stdClass
            || $data->rec->state != 'active') {
            return array();
        }

        $rec = $data->rec;
        $properties = jsonld_Helper::getArticleProperties(
            $rec,
            self::getCanonicalUrl($rec),
            self::getLanguage($rec)
        );

        if (!isset($properties)) {
            return array();
        }

        $datePublished = jsonld_Helper::getIsoDate($rec->createdOn ?? null);

        if ($datePublished !== null) {
            $properties['datePublished'] = $datePublished;
        }

        // Авторът е фирмата - служебният потребител не е предназначен за публиката
        if (isset($properties['publisher'])) {
            $properties['author'] = $properties['publisher'];
        }

        // CMS статиите са страници на сайта ("За нас", "Контакти"), а не публикации
        $properties = array('name' => $properties['headline']) + $properties;
        $properties['text'] = $properties['articleBody'];
        unset($properties['articleBody'], $properties['mainEntityOfPage']);

        $breadcrumbs = self::getBreadcrumbs($rec);

        if (isset($breadcrumbs)) {
            $properties['breadcrumb'] = $breadcrumbs;
        }

        return array(
            new jsonld_Node(
                'WebPage',
                $properties,
                null,
                'cms-article-' . $rec->id
            ),
        );
    }


    /**
     * Връща пътя до статията по менюто - меню, родителските статии по нивото и самата статия
     *
     * @param stdClass $rec
     *
     * @return jsonld_Node|null
     */
    protected static function getBreadcrumbs($rec)
    {
        $items = array(array(
            'name' => cms_Content::getVerbal($rec->menuId, 'menu'),
            'url' => cms_Content::getContentUrl($rec->menuId),
        ));

        // Нивото е XXXYYYZZZ - родителите са XXX000000 и XXXYYY000
        $level = (string) ($rec->level ?? '');
        $parentLevels = array();
        if (strlen($level) == 9) {
            if (substr($level, 3, 3) != '000') {
                $parentLevels[] = substr($level, 0, 3) . '000000';
            }
            if (substr($level, 6, 3) != '000') {
                $parentLevels[] = substr($level, 0, 6) . '000';
            }
        }

        foreach ($parentLevels as $parentLevel) {
            $parentRec = cms_Articles::fetch(array("#menuId = '[#1#]' AND #level = '[#2#]' AND #state = 'active'", $rec->menuId, $parentLevel));

            // Родител без текст е само заглавие в навигацията, без страница
            if ($parentRec && trim((string) $parentRec->body) !== '') {
                $items[] = array(
                    'name' => cms_Articles::getVerbal($parentRec, 'title'),
                    'url' => cms_Articles::getUrl($parentRec, true),
                );
            }
        }

        $items[] = array('name' => cms_Articles::getVerbal($rec, 'title'));

        return jsonld_Helper::getBreadcrumbList($items, 'cms-breadcrumb-' . $rec->id);
    }


    /**
     * Връща каноничния абсолютен URL на статията
     *
     * @param stdClass $rec
     *
     * @return string|null
     */
    protected static function getCanonicalUrl($rec)
    {
        $url = cms_Articles::getUrl($rec, true);

        if (empty($url)) {
            return null;
        }

        return toUrl($url, 'absolute');
    }


    /**
     * Връща езика на публичния домейн на статията
     *
     * @param stdClass $rec
     *
     * @return string|null
     */
    protected static function getLanguage($rec)
    {
        $domainId = cms_Content::fetchField($rec->menuId, 'domainId');

        if (!$domainId) {
            return null;
        }

        return cms_Domains::fetchField($domainId, 'lang');
    }
}
