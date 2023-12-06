<?php


/**
 * Клас 'n18_plg_Version' - за добавяне на версия от наредба 18
 *
 *
 * @category  bgplus
 * @package   n18
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class n18_plg_Version extends core_Plugin
{
    /**
     * Изпълнява се след подготовката на листовия изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListTitle($mvc, &$res, $data)
    {
        setIfNot($data->_infoTitlebgERPName, 'bgERP-N18');
    }
}
