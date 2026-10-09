<?php

/**
 * Тема за фирмен сайт, продуктов каталог и обслужване на бизнес клиенти.
 *
 * @title B2B CMS тема
 * @package cms
 */
class cms_B2BTheme extends cms_CommerceTheme
{
    public $tplDir = 'cms/tpl/b2b';


    public $defaultBanner = 'cms/img/b2b-banner.svg';


    /** Общите компоненти и поведението остават в търговската тема. */
    public $assets = array(
        'page' => array('css' => array('cms/css/B2B.css'), 'body' => 'b2b-theme'),
        'shop' => array('css' => array('cms/css/B2BShop.css')),
        'checkout' => array('css' => array('cms/css/B2BShop.css')),
    );
}
