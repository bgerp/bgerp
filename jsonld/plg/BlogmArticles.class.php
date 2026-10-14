<?php


/**
 * JSON-LD за публичната статия от блога
 *
 * Добавя jsonld_ProviderIntf към blogm_Articles със стандартна реализация - BlogPosting
 * (NewsArticle в режим "Новини") и пътечките до статията.
 */
class jsonld_plg_BlogmArticles extends core_Plugin
{
    /**
     * Добавя интерфейса за JSON-LD доставчик
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->declareInterface('jsonld_ProviderIntf');
    }


    /**
     * След рендиране на публичната статия от блога
     */
    public static function on_AfterRenderArticle($mvc, &$res, $data, $layout)
    {
        if (!Mode::is('wrapper', 'cms_page_External')) {
            return;
        }

        jsonld_Aggregator::addFromProvider($mvc, $data);
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
            toUrl(blogm_Articles::getUrl($rec, true), 'absolute'),
            self::getLanguage($rec)
        );

        if (!isset($properties)) {
            return array();
        }

        // Като в навигацията на блога - датата на публикуване, иначе на създаване
        $publishedOn = !empty($rec->publishedOn) ? $rec->publishedOn : ($rec->createdOn ?? null);
        $datePublished = jsonld_Helper::getIsoDate($publishedOn);

        if ($datePublished !== null) {
            $properties['datePublished'] = $datePublished;

            // Стара статия, публикувана наново - промяната е преди публикуването
            if (!empty($rec->modifiedOn) && $rec->modifiedOn < $publishedOn) {
                unset($properties['dateModified']);
            }
        }

        // Авторът е лице, както се показва; новините го крият във външната част - тогава е фирмата
        $author = null;
        if (blogm_Setup::get('TYPE') != 'news') {
            $author = jsonld_Helper::getCleanText($rec->author ?? null);
        }

        if ($author !== null) {
            $authorUrl = blogm_Articles::getAuthorUrl($rec->author, cls::get('blogm_Articles')->getDefaultMenuId($rec));
            $properties['author'] = new jsonld_Node('Person', array(
                'name' => $author,
                'url' => toUrl($authorUrl, 'absolute'),
            ));
        } elseif (isset($properties['publisher'])) {
            $properties['author'] = $properties['publisher'];
        }

        $sections = self::getSections($rec);

        if (countR($sections)) {
            $properties['articleSection'] = $sections;
        }

        // Броят коментари се вижда, освен ако коментарите са забранени
        if (($rec->commentsMode ?? null) != 'disabled') {
            $properties['commentCount'] = (int) ($rec->commentsCnt ?? 0);
        }

        $type = (blogm_Setup::get('TYPE') == 'news') ? 'NewsArticle' : 'BlogPosting';
        $nodes = array(
            new jsonld_Node(
                $type,
                $properties,
                null,
                'blogm-article-' . $rec->id
            ),
        );

        return array_merge($nodes, self::getBreadcrumbs($rec, $data->menuId ?? null));
    }


    /**
     * Връща пътечките до статията, както ги показва блогът - по една за всяка категория
     *
     * @param stdClass $rec
     * @param int|null $menuId
     *
     * @return jsonld_Node[]
     */
    protected static function getBreadcrumbs($rec, $menuId)
    {
        $maxPath = (int) blogm_Setup::get('ARTICLE_NAVIGATION_MAX_PATH');

        if ($maxPath <= 0) {
            return array();
        }

        if (empty($menuId)) {
            $menuId = cls::get('blogm_Articles')->getDefaultMenuId($rec);
        }

        $tree = cls::get('blogm_Categories')->getNestedTree(keylist::toArray($rec->categories ?? ''));
        $paths = array_slice(self::flattenTree($tree), 0, $maxPath);

        $res = array();
        foreach ($paths as $i => $categoryIds) {
            $items = array();
            foreach ($categoryIds as $categoryId) {
                $items[] = array(
                    'name' => blogm_Categories::getTitleById($categoryId),
                    'url' => array('blogm_Articles', 'browse', 'cMenuId' => $menuId, 'category' => $categoryId),
                );
            }
            $items[] = array('name' => blogm_Articles::getVerbal($rec, 'title'));

            $breadcrumbs = jsonld_Helper::getBreadcrumbList($items, "blogm-breadcrumb-{$rec->id}-{$i}");
            if (isset($breadcrumbs)) {
                $res[] = $breadcrumbs;
            }
        }

        return $res;
    }


    /**
     * Връща пътищата от корена до всяко листо на дървото с категории
     *
     * @param array $tree  [id => деца]
     * @param array $prefix
     *
     * @return array масив от масиви с id-та
     */
    protected static function flattenTree($tree, $prefix = array())
    {
        $res = array();

        foreach ((array) $tree as $id => $children) {
            $path = array_merge($prefix, array($id));
            if (empty($children)) {
                $res[] = $path;
            } else {
                $res = array_merge($res, self::flattenTree($children, $path));
            }
        }

        return $res;
    }


    /**
     * Връща имената на категориите на статията
     *
     * @param stdClass $rec
     *
     * @return array
     */
    protected static function getSections($rec)
    {
        $res = array();

        foreach (keylist::toArray($rec->categories ?? '') as $categoryId) {
            $title = jsonld_Helper::getCleanText(
                blogm_Categories::getVerbal($categoryId, 'title')
            );

            if ($title !== null) {
                $res[] = $title;
            }
        }

        return $res;
    }


    /**
     * Връща езика на домейна по първата категория, както го определя блогът
     *
     * @param stdClass $rec
     *
     * @return string|null
     */
    protected static function getLanguage($rec)
    {
        $categoryId = key(keylist::toArray($rec->categories ?? ''));
        $domainId = $categoryId ? blogm_Categories::fetchField($categoryId, 'domainId') : null;

        return $domainId ? cms_Domains::fetchField($domainId, 'lang') : null;
    }
}
