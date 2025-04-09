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
    public static $pattern = "/\B(?'pre'@)(?'nick'(\w|\.)+(\w){1})/";
    
    
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

            $userIdsStr = '';
            $shareUsersRoles = str_replace('|', ',', $shareUsersRoles);
            $threadId = null;
            $folderId = Request::get('folderId');
            if (!$folderId && ($originId = Request::get('originId'))) {
                $oRec = doc_Containers::fetch($originId);
                $folderId = $oRec->folderId;
                $threadId = $oRec->threadId;
            }

            if (!$folderId && $threadId = Request::get('threadId')) {
                $tRec = doc_Threads::fetch($threadId);
                $folderId = $tRec->folderId;
                $threadId = $tRec->id;
            }

            if (!$folderId && ($rId = Request::get('id')) && ($ctr = Request::get('Ctr'))) {
                if (cls::load($ctr, true)) {
                    $ctr = cls::get($ctr);
                    if ($ctr instanceof core_Manager) {
                        $cRec = $ctr->fetch($rId);
                        if ($cRec && $cRec->folderId) {
                            $folderId = $cRec->folderId;
                            $threadId = $cRec->threadId;
                        }
                    }
                }
            }

            if ($folderId && core_Packs::isInstalled('colab')) {
                $contractorIds = colab_FolderToPartners::getContractorsInFolder($folderId);
                if (!empty($contractorIds)) {
                    foreach ($contractorIds as $cId) {
                        if ($threadId && !colab_Threads::haveRightFor('single', doc_Threads::fetch($threadId), $cId)) {
                            unset($contractorIds[$cId]);
                        }
                    }
                    if (!empty($contractorIds)) {
                        $userIdsStr = implode(',', $contractorIds);
                    }
                }
            }

            // Обекти за данните
            $tpl->appendOnce('rtacObj.shareUsersURL = {};', 'SCRIPTS');
            $tpl->appendOnce('rtacObj.shareUserRoles = {};', 'SCRIPTS');
            $tpl->appendOnce('rtacObj.sharedUsers = {};', 'SCRIPTS');
            $tpl->appendOnce('rtacObj.shareUsersIds = {};', 'SCRIPTS');
            $tpl->appendOnce("rtacObj.shareUsersIds.{$id} = '{$userIdsStr}';", 'SCRIPTS');

            // Добавяме потребителите, до които ще се споделя
            $tpl->appendOnce("rtacObj.shareUserRoles.{$id} = '{$shareUsersRoles}';", 'SCRIPTS');

            // Фунцкията, която ще приеме управелението след извикване на екшъна, в която ще се добавят потребителите
            $tpl->appendOnce("\n function render_sharedUsers(data){rtacObj.sharedUsers[data.id] = data.users;}", 'SCRIPTS');
            
            // Локално URL
            $localUrl = toUrl(array(get_called_class(), 'getUsers'), 'local');
            
            // Ескейпваме
            $localUrl = urlencode($localUrl);
            $tpl->appendOnce("rtacObj.shareUsersURL.{$id} = '{$localUrl}';", 'SCRIPTS');

            setIfNot($maxShowCnt, $mvc->params['maxOptionsShowCount'], rtac_Setup::get('MAX_SHOW_COUNT'));

            // Стартираме autocomplete-a за добавяне на потребител
            $inst->runAutocompleteUsers($tpl, $id, $maxShowCnt);
        }
    }
    
    
    /**
     * Връща потребителите и имената им по AJAX
     */
    public function act_GetUsers()
    {
        // Ако заявката е по ajax
        if (Request::get('ajax_mode')) {
            if (haveRole('powerUser')) {

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

                if ($users = Request::get('users')) {
                    $users = explode(',', $users);
                    foreach ((array) $users as $uId) {
                        $uRec = core_Users::fetch($uId);
                        $usersArr[$uRec->nick] = core_Users::prepareUserNames($uRec->names);
                    }
                }

                // Добавяме потребителите в нов масив
                foreach ((array) $usersArr as $key => $users) {
                    if ($term) {
                        if (mb_stripos($key, $term) !== 0) {
                            if (mb_stripos($users, $term) !== 0) {
                                if (mb_stripos($users, ' ' . $term) === false) {
                                    continue;
                                }
                            }
                        }
                    }
                    $usersArrRes[$i]['nick'] = $key;
                    $usersArrRes[$i]['names'] = $users;
                    $i++;
                }

                if ($limit) {
                    $usersArrRes = array_slice($usersArrRes, 0, $limit);
                }

                // Добавяме резултата
                $resObj = new stdClass();
                $resObj->func = 'sharedUsers';
                $resObj->arg = array('id' => $id, 'users' => $usersArrRes);

                return array($resObj);
            } else {

                return array();
            }
        }
    }
}
