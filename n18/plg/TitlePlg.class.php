<?php


/**
 * Добавяне на Н18 към името на приложението
 *
 * @category  bgerp
 * @package   n28
 *
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class n18_plg_TitlePlg extends core_Plugin
{
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
