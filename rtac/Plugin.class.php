<?php


/**
 * Плъгин за autocomplete в ричтекста
 *
 * @category  vendors
 * @package   rtac
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rtac_Plugin extends core_Plugin
{
    /**
     * Шаблон за намиране на потребителите
     */
    public static $pattern = "/\B(?'pre'@)(?'nick'(\w|\.)+)/";
    
    
    /**
     * Масив с всички потребители, споделени в
     *
     * @param string $text
     *
     * @return array
     */
    public static function getNicksArr($text)
    {
        preg_match_all(static::$pattern, $text, $matches);
        
        $nickArr = array();
        
        if (!$matches['nick']) {
            
            return $nickArr;
        }
        
        // Масив с никовете на всички потребители
        $userArr = core_Users::getUsersArr();
        
        $userArr = array_change_key_case($userArr, CASE_LOWER);
        
        // Обхождаме всички открити никове и, ако има такива потребители добавяме в масива
        foreach ((array) $matches['nick'] as $nick) {
            if (!$nick) {
                continue;
            }
            $nick = strtolower($nick);
            
            if (!$userArr[$nick]) {
                continue;
            }
            $nickArr[$nick] = $nick;
        }
        
        return $nickArr;
    }
    
    
    /**
     *
     * Изпълнява се преди рендирането на input
     *
     * @param core_Mvc $invoker
     * @param core_Et  $tpl
     * @param string   $name
     * @param string   $value
     * @param array    $attr
     */
    public function on_BeforeRenderInput(&$mvc, &$ret, $name, $value, &$attr = array())
    {
        // Задаваме уникално id
        ht::setUniqId($attr);
    }
    
    
    /**
     *
     * Изпълнява се след рендирането на input
     *
     * @param core_Mvc $invoker
     * @param core_Et  $tpl
     * @param string   $name
     * @param string   $value
     * @param array    $attr
     */
    public function on_AfterRenderInput(&$mvc, &$tpl, $name, $value, $attr = array())
    {
        $conf = core_Packs::getConfig('rtac');
        
        // Интанция на избрания клас
        $inst = cls::get($conf->RTAC_AUTOCOMPLETE_CLASS);
        
        // Зареждаме необходимите пакети
        $inst->loadPacks($tpl);
        
        // id на ричтекста
        list($id) = explode(' ', $attr['id']);
        $id = trim($id);
        
        // Ако са подадени роли до които може да се споделя
        if (! ($userRolesForShare = $mvc->params['userRolesForShare'])) {
            $userRolesForShare = $conf->RTAC_DEFAUL_USER_ROLES_FOR_SHARE;
        }
        
        // Масив с потребителите
        $userRolesForShare = str_replace('|', ',', $userRolesForShare);
        $userRolesForShareArr = arr::make($userRolesForShare);
        
        // Обект за данните
        $tpl->appendOnce('var rtacObj = {};', 'SCRIPTS');
        
        // Ако потребителя име права да споделе
        if (core_Users::haveRole($userRolesForShareArr)) {
            
            // Ако са подадени роли до които може да се споделя
            if (! ($shareUsersRoles = $mvc->params['shareUsersRoles'])) {
                $shareUsersRoles = $conf->RTAC_DEFAUL_SHARE_USER_ROLES;
            }
            $shareUsersRoles = str_replace('|', ',', $shareUsersRoles);
            
            // Обекти за данните
            $tpl->appendOnce('rtacObj.shareUsersURL = {};', 'SCRIPTS');
            $tpl->appendOnce('rtacObj.shareUserRoles = {};', 'SCRIPTS');
            $tpl->appendOnce('rtacObj.sharedUsers = {};', 'SCRIPTS');
            
            // Добавяме потребителите, до които ще се споделя
            $tpl->appendOnce("rtacObj.shareUserRoles.{$id} = '{$shareUsersRoles}';", 'SCRIPTS');
            
            // Фунцкията, която ще приеме управелението след извикване на екшъна, в която ще се добавят потребителите
            $tpl->appendOnce("\n function render_sharedUsers(data){rtacObj.sharedUsers[data.id] = data.users;}", 'SCRIPTS');
            
            // Локално URL
            $localUrl = toUrl(array(get_called_class(), 'getUsers'), 'local');
            
            // Ескейпваме
            $localUrl = urlencode($localUrl);
            $tpl->appendOnce("rtacObj.shareUsersURL.{$id} = '{$localUrl}';", 'SCRIPTS');
            
            // Стартираме autocomplete-a за добавяне на потребител
            $inst->runAutocompleteUsers($tpl, $id);
        }
    }
    
    
    /**
     * Връща потребителите и имената им по AJAX
     */
    public function act_GetUsers()
    {
        // Ако заявката е по ajax
        if (Request::get('ajax_mode')) {
            
            // id на ричтекста
            $id = Request::get('rtid');
            
            // Началото на ника на потребителя
            $term = Request::get('term');
            
            // Роли на потребителите
            $roles = Request::get('roles');
            $roles = str_replace('|', ',', $roles);
            
            $conf = core_Packs::getConfig('rtac');
            
            // Лимит на показване
            $limit = $conf->RTAC_MAX_SHOW_COUNT;
            
            // Масив с потребителите
            $usersArr = core_Users::getUsersArr($roles, $term, $limit);
            $i = 0;
            $usersArrRes = array();
            
            // Добавяме потребителите в нов масив
            foreach ((array) $usersArr as $key => $users) {
                $usersArrRes[$i]['nick'] = $key;
                $usersArrRes[$i]['names'] = $users;
                $i++;
            }
            
            // Добавяме резултата
            $resObj = new stdClass();
            $resObj->func = 'sharedUsers';
            $resObj->arg = array('id' => $id, 'users' => $usersArrRes);
            
            return array($resObj);
        }
    }
}
