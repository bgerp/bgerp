<?php


/**
 * Коледна тема за публичен каталог и онлайн магазин, наследник на търговската
 *
 * @title Коледна CMS тема
 *
 * @category  bgerp
 * @package   cms
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cms_themes_christmas_Theme extends cms_CommerceTheme
{
    /**
     * Коледната палитра и украсата се добавят върху стиловете на търговската тема
     */
    public $assets = array(
        'page' => array(
            'css' => array('cms/themes/christmas/christmas.css'),
            'body' => 'christmas-theme',
        ),
    );


    /**
     * Банер по подразбиране
     */
    public $defaultBanner = 'cms/themes/christmas/img/banner.svg';


    /**
     * Цветовете на футъра и бисквитките, ако не са избрани в настройките
     */
    protected $defaultColors = array(
        'baseColor' => '#0d3b2e',
        'activeColor' => '#f5c84c',
        'headerColor' => '#0d3b2e',
    );


    public function prepareWrapper($tpl)
    {
        if (!is_object($this->innerForm ?? null)) {
            $this->innerForm = new stdClass();
        }
        foreach ($this->defaultColors as $field => $color) {
            if (empty($this->innerForm->{$field})) {
                $this->innerForm->{$field} = $color;
            }
        }

        parent::prepareWrapper($tpl);
    }
}
