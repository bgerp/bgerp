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
        if (empty($mvc->scaleWeightFieldName)) {

            return;
        }

        setIfNot($formName, $mvc->className . '-EditForm');

        wscales_Helper::appendJs($res, $mvc->scaleWeightFieldName, $mvc->scaleLiveWeightFieldName, $formName);
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
        if (empty($mvc->scaleWeightFieldName)) {

            return;
        }

        // Без поле за тегло във формата няма къде да се пише прочетеното от везната
        if (empty($data->form->getField($mvc->scaleWeightFieldName, false))) {
            $mvc->scaleWeightFieldName = null;

            return;
        }

        wscales_Helper::allowCrossOrigin();
    }
}
