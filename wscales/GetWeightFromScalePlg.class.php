<?php


/**
 * Клас 'wscales_GetWeightFromScale' - плъгин взимащ теглото от електронна везна
 *
 *
 * @category  bgerp
 * @package   wscales
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class wscales_GetWeightFromScalePlg extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setIfNot($mvc->scaleWeightFieldName, 'weight');
        setIfNot($mvc->scaleLiveWeightFieldName, 'liveWeight');
    }
    
    
    /**
     * След рендиране на формата в кустом терминал
     *
     * @param core_Manager $mvc
     * @param core_ET|null $tpl
     * @param core_Form|null $form
     */
    public static function on_AfterRenderInTerminal(core_Manager $mvc, &$tpl = null, $form = null)
    {
        self::insertJsIfNeeded($tpl, $mvc);
    }
    
    
    /**
     * Добавяне на скрипта в терминала при нужда
     *
     * @param mixed $res
     * @param core_Manager $mvc
     * @param string $formName
     */
    private static function insertJsIfNeeded(&$res, $mvc, $formName = null)
    {
        if ($mvc->scaleWeightFieldName) {
            $aDivecesArr = peripheral_Devices::getDevices('wscales_intf_Scales');
            if (!empty($aDivecesArr)) {
                $lRec = reset($aDivecesArr);
                setIfNot($formName, $mvc->className . '-EditForm');
                
                jquery_Jquery::enable($res);
                $interface = core_Cls::getInterface('wscales_intf_Scales', $lRec->driverClass);
                
                $lRec->_weight = $mvc->scaleWeightFieldName;
                $lRec->_liveWeight = $mvc->scaleLiveWeightFieldName;
                $lRec->_formIdName = '#' . $formName;
                
                $js = $interface->getJs($lRec);
                
                $res->appendOnce($js, 'SCRIPTS');
            }
        }
    }
    
    
    /**
     * Изпълнява се след опаковане на съдаржанието от мениджъра
     *
     * @param core_Mvc       $mvc
     * @param string|core_ET $res
     * @param string|core_ET $tpl
     * @param stdClass       $data
     *
     * @return bool
     */
    public static function on_AfterRenderWrapping(core_Manager $mvc, &$res, &$tpl = null, $data = null)
    {
        self::insertJsIfNeeded($res, $mvc);
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        if ($mvc->scaleWeightFieldName) {
            if ($data->form->fields[$mvc->scaleWeightFieldName]) {
                $aDivecesArr = peripheral_Devices::getDevices('wscales_intf_Scales');
                if (!empty($aDivecesArr)) {
                    $lRec = reset($aDivecesArr);
                    if ($lRec->hostName != 'localhost') {
                        header('Access-Control-Allow-Origin: *');
                        header('Vary: Origin');
                    }
                }
            } else {
                $mvc->scaleWeightFieldName = null;
            }
        }
    }
}
