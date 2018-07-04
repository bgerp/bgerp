<?php
/**
 * Клас 'boilerplate_Plugin'
 *
 * Шаблон за bgerp плъгин към наследник на core_Mvc
 *
 *
 * @category  bgerp
 * @package   [име на пакет]
 * @author    [Име на автора] <[имейл на автора]>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo      Текстовете в [правоъгълни скоби] да се заменят със съотв. стойности
 */
class boilerplate_Plugin extends core_Plugin
{
    /**
     * @TODO описание
     *
     * След потготовка на формата за добавяне / редактиране.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
    }
    
    
    /**
     * @TODO описание
     *
     * След зареждане на формата за добавяне / редактиране.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
    }
    
    
    /**
     * @TODO описание
     *
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    }
}
