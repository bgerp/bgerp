<?php

/**
 * Бордо тема с редакционна типография и тъмночервена навигация.
 *
 * @title Бордо CMS тема
 * @category cms
 * @package cms
 * @author nevena <nevena@experta.bg>
 * @copyright 2026 - 2026 Experta EOOD
 * @license GPL 3
 * @since 2026-10-19
 */
class cms_themes_bordeaux_Theme extends cms_themes_commerce_Theme
{
    public $oldClassName = null;


    protected $desktopBannerSize = '1400×240';


    protected $defaultColors = array(
        'titleColor' => '#fff8ef',
        'headerColor' => '#651d2b',
        'baseColor' => '#481622',
        'activeColor' => '#7d2639',
    );


    public $tplDir = 'cms/themes/bordeaux/tpl';


    public $defaultBanner = 'cms/themes/bordeaux/img/banner.svg';


    public $assets = array(
        'page' => array('css' => array('cms/themes/bordeaux/css/Bordeaux.css'), 'body' => 'bordeaux-theme'),
        'shop' => array('css' => array('cms/themes/bordeaux/css/BordeauxShop.css')),
        'checkout' => array('css' => array('cms/themes/bordeaux/css/BordeauxShop.css')),
    );
}
