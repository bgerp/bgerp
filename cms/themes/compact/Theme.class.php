<?php

/**
 * Тема за фирмен сайт, продуктов каталог и обслужване на бизнес клиенти.
 *
 * @title Компактни панели
 * @package cms
 * @copyright 2026 - 2026 Experta EOOD
 */
class cms_themes_compact_Theme extends cms_themes_cards_Theme
{
    protected $defaultMenuPosition = 'below';


    public $oldClassName = 'cms_themes_b2b_Theme';


    protected $desktopBannerSize = '1920×180';


    protected $defaultColors = array(
        'titleColor' => '#ffffff',
        'headerColor' => '#182332',
        'baseColor' => '#182332',
        'activeColor' => '#2459c4',
    );


    public $tplDir = 'cms/themes/compact/tpl';


    public $defaultBanner = 'cms/themes/compact/img/b2b-banner.svg';


    /** Общите компоненти и поведението остават в търговската тема. */
    public $assets = array(
        'page' => array('css' => array('cms/themes/compact/css/B2B.css'), 'body' => 'b2b-theme'),
        'shop' => array('css' => array('cms/themes/compact/css/B2BShop.css')),
        'checkout' => array('css' => array('cms/themes/compact/css/B2BShop.css')),
    );
}
