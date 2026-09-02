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
        $provider = cls::getInterface(
            'jsonld_ProviderIntf',
            'jsonld_adapters_EshopProduct'
        );
        $nodes = $provider->getJsonLdNodes($data);

        jsonld_Aggregator::addArray($nodes);
    }
}
