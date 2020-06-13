<?php


/**
 * 
 *
 * @category  bgerp
 * @package   borsa
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class borsa_Plugin extends core_Plugin
{
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     *
     * @return bool|null
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if (borsa_Companies::haveRightFor('add') && !borsa_Companies::fetch(array("#companyId = '[#1#]'", $data->rec->id))) {
            $data->toolbar->addBtn('Борса', toUrl(array('borsa_Companies', 'add', 'companyId' => $data->rec->id, 'ret_url' => true)), 'ef_icon=img/16/extract_foreground_objects.png, title = Добавяне към борса, row=2');
        }
    }
}
