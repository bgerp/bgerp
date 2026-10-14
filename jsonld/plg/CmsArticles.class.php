<?php


/**
 * Добавя JSON-LD възел за нормална публична CMS статия
 */
class jsonld_plg_CmsArticles extends core_Plugin
{
    /**
     * След определяне на показаната публична статия
     */
    public static function on_AfterPrepareArticle($mvc, $rec)
    {
        jsonld_Aggregator::addFromProvider(
            'jsonld_adapters_CmsArticle',
            (object) array('rec' => $rec)
        );
    }
}
