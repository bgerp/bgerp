<?php


/**
 * Интерфейс на пакета за персонализиране
 *
 * @category  bgerp
 * @package   custom
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @deprecated
 */
class custom_SettingsIntf
{
    /**
     * Кой може да модифицира настройките за персонализация
     */
    public $canModify;
    
    
    /**
     * Кой може да модифицира по-подразбиране за всички
     */
    public $canModifydefault;
    
    
    /**
     * Подготвя формата за персонализация
     *
     * @param core_Form $form
     */
    public function prepareCustomizationForm(&$form)
    {
        return $this->class->prepareCustomizationForm($form);
    }
    
    
    /**
     * Проверява формата за персонализация
     *
     * @param core_Form $form
     */
    public function checkCustomizationForm(&$form)
    {
        return $this->class->checkCustomizationForm($form);
    }
}
