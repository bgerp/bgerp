<?php

/**
 * Представителна бизнес тема с топла палитра и архитектурна визия.
 *
 * @title Центриран хедър и изчистени секции
 * @package cms
 * @copyright 2026 - 2026 Experta EOOD
 */
class cms_themes_centered_Theme extends cms_themes_compact_Theme
{
    protected $defaultMenuPosition = 'above';


    public $oldClassName = 'cms_themes_executive_Theme';


    protected $desktopBannerSize = '1920×220';


    protected $defaultColors = array(
        'titleColor' => '#292c2b',
        'headerColor' => '#f6f2ec',
        'baseColor' => '#232927',
        'activeColor' => '#92502e',
    );


    public $tplDir = 'cms/themes/centered/tpl';


    public $defaultBanner = 'cms/themes/centered/img/banner.svg';


    public $assets = array(
        'page' => array('css' => array('cms/themes/centered/css/Executive.css'), 'body' => 'executive-theme'),
        'shop' => array('css' => array('cms/themes/centered/css/ExecutiveShop.css')),
        'checkout' => array('css' => array('cms/themes/centered/css/ExecutiveShop.css')),
    );
}
