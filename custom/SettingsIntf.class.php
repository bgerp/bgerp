<?php



/**
 * Интерфейс на пакета за персонализиране
 *
 * @category  bgerp
 * @package   custom
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class custom_SettingsIntf
{
    
    
    /**
     * Кой може да модифицира настройките за персонализация
     */
    var $canModify;
    
    
    /**
     * Подготвя формата за персонализация
     * 
     * @param core_Form $form
     */
    function prepareCustomizationForm(&$form)
    {
        return $this->class->prepareCustomizationForm($form);
    }
    
    
    /**
     * Проверява формата за персонализация
     * 
     * @param core_Form $form
     */
    function checkCustomizationForm(&$form)
    {
        return $this->class->checkCustomizationForm($form);
    }
}
