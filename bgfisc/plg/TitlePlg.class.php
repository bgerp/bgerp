<?php


/**
 * Добавяне на Н18 към името на приложението
 *
 * @category  bgerp
 * @package   bgfisc
 *
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgfisc_plg_TitlePlg extends core_Plugin
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'n18_plg_TitlePlg';


    /**
     * Променя стойността на константата.
     * Използва стойността от настройките на профила
     *
     * @param core_ObjectConfiguration $mvc
     * @param string                   $value
     * @param string                   $name
     */
    public function on_BeforeGetConfConst($mvc, &$value, $name)
    {
        if ($name == 'EF_APP_TITLE' && ((EF_APP_TITLE == 'bgERP' && !$mvc->_data[$name]) || $mvc->_data[$name] == 'bgERP')) {
            $value = EF_APP_TITLE .  '-N18';
        }
    }
}
