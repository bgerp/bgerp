<?php


/**
 * Инсталация на пакета за JSON-LD структурирани данни
 */
class jsonld_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';


    /**
     * Описание на пакета
     */
    public $info = 'Поддръжка на JSON-LD структурирани данни';


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        $html .= core_Composer::install(
            'spatie/schema-org',
            '3.9.0'
        );

        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin(
            'JSON-LD за продукти в онлайн магазина',
            'jsonld_plg_EshopProducts',
            'eshop_Products',
            'private'
        );
        $html .= $Plugins->installPlugin(
            'JSON-LD във външни страници',
            'jsonld_plg_External',
            'cms_page_External',
            'private'
        );
        $html .= $Plugins->installPlugin(
            'JSON-LD за публични CMS статии',
            'jsonld_plg_CmsArticles',
            'cms_Articles',
            'private'
        );

        return $html;
    }
}
