<?php


/**
 * Плъгин за autocomplete в ричтекста
 *
 * @category  vendors
 * @package   rtac
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class rtac_Plugin extends core_Plugin
{
    
    
    /**
     * Шаблон за намиране на потребителите
     */
    static $pattern = "/\B@(?'nick'(\w|\.)*)/";
    
    
    /**
     * Масив с всички потребители, споделени в 
     * 
     * @param string $text
     * 
     * @return array
     */
    static function getNicksArr($text)
    {
        preg_match_all(static::$pattern, $text, $matches);
        
        if (!$matches['nick']) return ;
        
        // Масив с никовете на всички потребители
        $userArr = core_Users::getUsersArr();
        
        // Обхождаме всички открити никове и проверяваме дали има такива потребители
        foreach ((array)$matches['nick'] as $nick) {
            $nick = strtolower($nick);
            if (!$userArr[$nick]) continue;
            $nickArr[$nick] = $nick;
        }
        
        return $nickArr;
    }
    
    
    /**
     * 
     * Изпълнява се преди рендирането на input
     * 
     * @param core_Mvc $invoker
     * @param core_Et $tpl
     * @param string $name
     * @param string $value
     * @param array $attr
     */
    function on_BeforeRenderInput(&$mvc, &$ret, $name, $value, &$attr = array())
    {
        // Задаваме уникално id
        ht::setUniqId($attr);
    }
    
    
    /**
     * 
     * Изпълнява се след рендирането на input
     * 
     * @param core_Mvc $invoker
     * @param core_Et $tpl
     * @param string $name
     * @param string $value
     * @param array $attr
     */
    function on_AfterRenderInput(&$mvc, &$tpl, $name, $value, $attr = array())
    {
        $conf = core_Packs::getConfig('rtac');
        
        // Интанция на избрания клас
        $inst = cls::get($conf->RTAC_AUTOCOMPLETE_CLASS);
        
        // Зареждаме необходимите пакети
    	$inst->loadPacks($tpl);
    	
    	list($id) = explode(' ', $attr['id']);
    	
    	$id = trim($id);
    	
        // Ако са подадени роли до които може да се споделя
        if (!($userRolesForShare = $mvc->params['userRolesForShare'])) {
            $userRolesForShare = $conf->RTAC_DEFAUL_USER_ROLES_FOR_SHARE;
        }
        
        // Ако потребителя име права да споделе
        $userRolesForShare = str_replace("|", ",", $userRolesForShare);
        $userRolesForShareArr = arr::make($userRolesForShare);
        if (core_Users::haveRole($userRolesForShareArr)) {
        
            // Ако са подадени роли до които може да се споделя
            if (!($shareUsersRoles = $mvc->params['shareUsersRoles'])) {
                $shareUsersRoles = $conf->RTAC_DEFAUL_SHARE_USER_ROLES;
            }
            
            $shareUsersRoles = str_replace("|", ",", $shareUsersRoles);
            $shareUsersRolesArr = arr::make($shareUsersRoles);
            
            // Добавяме масива с потребителите в JS
            $usersArr = core_Users::getUsersArr($shareUsersRolesArr);
            
            // Добавяме потребителите, до които ще се споделя
            $tpl->appendOnce("sharedUsersObj = {};", 'SCRIPTS');
            $tpl->appendOnce("sharedUsersObj.{$id} = " . json_encode($usersArr) . ";", 'SCRIPTS');
            
            // Стартираме autocomplete-a за добавяне на потребител
            $inst->runAutocompleteUsers($tpl, $id);
        }
    }
}
