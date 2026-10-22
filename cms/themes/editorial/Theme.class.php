<?php

/**
 * Бордо тема с редакционна типография и тъмночервена навигация.
 *
 * @title Редакционно оформление
 * @category cms
 * @package cms
 * @author nevena <nevena@experta.bg>
 * @copyright 2026 - 2026 Experta EOOD
 * @license GPL 3
 * @since 2026-10-19
 */
class cms_themes_editorial_Theme extends cms_themes_cards_Theme
{
    protected $defaultMenuPosition = 'below';


    public $oldClassName = 'cms_themes_bordeaux_Theme';


    protected $desktopBannerSize = '1400×240';


    protected $defaultColors = array(
        'titleColor' => '#fff8ef',
        'headerColor' => '#651d2b',
        'baseColor' => '#481622',
        'activeColor' => '#7d2639',
    );


    public $tplDir = 'cms/themes/editorial/tpl';


    public $defaultBanner = 'cms/themes/editorial/img/banner.svg';


    /** Версиран адрес, за да се обновява банерът и при промяна на SVG файла. */
    public function prepareWrapper($tpl)
    {
        parent::prepareWrapper($tpl);
        $palette = $this->innerForm->colorPalette ?? 'default';
        $banner = $this->defaultBanner;
        if (in_array($palette, array('navy', 'teal', 'purple', 'sky', 'sand'))) {
            $banner = 'cms/themes/editorial/img/banner-' . $palette . '.svg';
        }
        $tpl->append('body.bordeaux-theme { --editorial-banner: url(' . sbf($banner) . '); }', 'STYLES');
    }


    public $assets = array(
        'page' => array('css' => array('cms/themes/editorial/css/Bordeaux.css'), 'body' => 'bordeaux-theme'),
        'shop' => array('css' => array('cms/themes/editorial/css/BordeauxShop.css')),
        'checkout' => array('css' => array('cms/themes/editorial/css/BordeauxShop.css')),
    );
}
