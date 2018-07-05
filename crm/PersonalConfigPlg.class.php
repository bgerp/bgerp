<?php


/**
 * При опит за вземане на стойност на константа използва настройките на потребителя и ги заменя с тях
 *
 * @category  bgerp
 * @package   crm
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class crm_PersonalConfigPlg extends core_Plugin
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
        $currUserId = core_Users::getCurrent();
        
        if (!$currUserId || ($currUserId <= 0)) {
            
            return ;
        }
        
        $key = crm_Profiles::getSettingsKey();
        
        $valsArr = core_Settings::fetchKey($key, $currUserId);
        
        if (isset($valsArr[$name])) {
            $value = $valsArr[$name];
        }
    }
}
