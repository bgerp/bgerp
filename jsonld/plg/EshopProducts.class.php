<?php


/**
 * Добавя JSON-LD възлите на публична е-магазин продуктова страница
 */
class jsonld_plg_EshopProducts extends core_Plugin
{
    /**
     * След рендиране на продукта в онлайн магазина
     */
    public static function on_AfterRenderProduct(
        $mvc,
        &$res,
        $data
    ) {
        if (!Mode::is('wrapper', 'cms_page_External')) {
            return;
        }

        jsonld_Aggregator::addFromProvider(
            'jsonld_adapters_EshopProduct',
            $data
        );
    }
}
