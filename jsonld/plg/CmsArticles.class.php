<?php


/**
 * Добавя JSON-LD възел за нормална публична CMS статия
 */
class jsonld_plg_CmsArticles extends core_Plugin
{
    /**
     * След приключване на публичния Article екшън
     */
    public static function on_AfterAction($mvc, &$res, $action)
    {
        if (strtolower($action) !== 'article'
            || !Mode::is('wrapper', 'cms_page_External')
            || !$res instanceof core_ET) {
            return;
        }

        $id = Request::get('id', 'int');

        if (!$id) {
            return;
        }

        $rec = $mvc->fetch($id);

        if (!$rec || $rec->state != 'active' || empty($rec->body)) {
            return;
        }

        $provider = cls::getInterface(
            'jsonld_ProviderIntf',
            'jsonld_adapters_CmsArticle'
        );
        $nodes = $provider->getJsonLdNodes(
            (object) array('rec' => $rec)
        );

        jsonld_Aggregator::addArray($nodes);
    }
}
