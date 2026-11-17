<?php

/**
 * Светла тема с разделен хедър и отворено оформление на съдържанието.
 *
 * @title Студио - простор и геометрия
 * @category cms
 * @package cms
 * @author nevena <nevena@experta.bg>
 * @copyright 2026 - 2026 Experta EOOD
 * @license GPL 3
 * @since 2026-11-17
 */
class cms_themes_studio_Theme extends cms_themes_cards_Theme
{
    public $oldClassName = null;


    protected $defaultMenuPosition = 'above';


    protected $desktopBannerSize = '1400×360';


    protected $defaultColors = array(
        'titleColor' => '#202b25',
        'headerColor' => '#f1f4ed',
        'baseColor' => '#202b25',
        'activeColor' => '#326348',
    );


    public $tplDir = 'cms/themes/studio/tpl';


    public $defaultBanner = 'cms/themes/studio/img/banner.svg';


    public $assets = array(
        'page' => array('css' => array('cms/themes/studio/css/Studio.css'), 'body' => 'studio-theme'),
        'blog' => array('css' => array('cms/themes/studio/css/Content.css')),
        'article' => array('css' => array('cms/themes/studio/css/Content.css')),
        'shop' => array('css' => array('cms/themes/studio/css/Shop.css')),
        'checkout' => array('css' => array('cms/themes/studio/css/Shop.css')),
    );
}
