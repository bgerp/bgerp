<?php

/**
 * Тема за фирмен сайт, продуктов каталог и обслужване на бизнес клиенти.
 *
 * @title B2B CMS тема
 * @package cms
 */
class cms_themes_b2b_Theme extends cms_themes_commerce_Theme
{
    public $oldClassName = 'cms_B2BTheme';


    public $tplDir = 'cms/themes/b2b/tpl';


    public $defaultBanner = 'cms/themes/b2b/img/b2b-banner.svg';


    /** Общите компоненти и поведението остават в търговската тема. */
    public $assets = array(
        'page' => array('css' => array('cms/themes/b2b/css/B2B.css'), 'body' => 'b2b-theme'),
        'shop' => array('css' => array('cms/themes/b2b/css/B2BShop.css')),
        'checkout' => array('css' => array('cms/themes/b2b/css/B2BShop.css')),
    );
}
