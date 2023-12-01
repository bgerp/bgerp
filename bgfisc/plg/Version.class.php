<?php


/**
 * Клас 'bgfisc_plg_Version' - за добавяне на версия от наредба 18
 *
 *
 * @category  bgerp
 * @package   bgfisc
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgfisc_plg_Version extends core_Plugin
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'n18_plg_Version';


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
