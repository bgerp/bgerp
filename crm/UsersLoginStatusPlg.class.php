<?php


/**
 * Прихваща извикването на getUrlForLoginLogStatus в core_Users
 * Връща URL към сингъла на профила
 *
 * @category  bgerp
 * @package   crm
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.12
 */
class crm_UsersLoginStatusPlg extends core_Plugin
{
    
    
    /**
     * Прихваща извикването на getUrlForLoginLogStatus в core_Users
     * Връща URL към сингъла на профила
     * 
     * @param core_Mvc $mvc
     * @param array $resArr
     * @param integer $userId
     */
    function on_AfterGetUrlForLoginLogStatus($mvc, &$resArr, $userId=NULL)
    {
        // Ако е определено, няма да се променя
        if ($resArr) return ;
        
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        $profileRec = crm_Profiles::fetch("#userId = {$userId}");
        
        // Ако има права за сингъла
        if (crm_Profiles::haveRightFor('single', $profileRec)) {
            $resArr = crm_Profiles::getUrl($userId);
        }
        
    }
}
