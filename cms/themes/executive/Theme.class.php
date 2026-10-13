<?php

/**
 * Представителна бизнес тема с топла палитра и архитектурна визия.
 *
 * @title Executive CMS тема
 * @package cms
 */
class cms_themes_executive_Theme extends cms_themes_b2b_Theme
{
    public $oldClassName = null;


    public $tplDir = 'cms/themes/executive/tpl';


    public $defaultBanner = 'cms/themes/executive/img/banner.svg';


    public $assets = array(
        'page' => array('css' => array('cms/themes/executive/css/Executive.css'), 'body' => 'executive-theme'),
        'shop' => array('css' => array('cms/themes/executive/css/ExecutiveShop.css')),
        'checkout' => array('css' => array('cms/themes/executive/css/ExecutiveShop.css')),
    );
}
