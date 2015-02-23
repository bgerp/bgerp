<?php



/**
 * Интерфейс на пакета за персонализиране
 *
 * @category  ef
 * @package   core
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class core_SettingsIntf
{
    
    
    /**
     * Може ли текущия потребител да пороменя сетингите на посочения потребител/роля?
     * 
     * @param string $key
     * @param integer $userOrRole
     */
    function canModifySettings($key, $userOrRole=NULL)
    {
        
        return $this->class->canModifySettings($key, $userOrRole);
    }
    
    
    
    /**
     * Подготвя формата за настройки
     * 
     * @param core_Form $form
     */
    function prepareSettingsForm(&$form)
    {
        
        return $this->class->prepareSettingsForm($form);
    }
    
    
    /**
     * Проверява формата за настройки
     * 
     * @param core_Form $form
     */
    function checkSettingsForm(&$form)
    {
        
        return $this->class->checkSettingsForm($form);
    }
}
