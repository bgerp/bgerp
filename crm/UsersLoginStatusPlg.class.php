<?php


/**
 * Прихваща извикването на getUrlForLoginLogStatus в core_Users
 * Връща URL към сингъла на профила
 *
 * @category  bgerp
 * @package   crm
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.12
 */
class crm_UsersLoginStatusPlg extends core_Plugin
{
    /**
     * Прихваща извикването на getUrlForLoginLogStatus в core_Users
     * Връща URL към сингъла на профила
     *
     * @param core_Mvc $mvc
     * @param array    $resArr
     * @param int      $userId
     */
    public function on_AfterGetUrlForLoginLogStatus($mvc, &$resArr, $userId = null)
    {
        // Ако е определено, няма да се променя
        if ($resArr) {
            
            return ;
        }
        
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        $profileRec = crm_Profiles::fetch("#userId = {$userId}");
        
        // Ако има права за сингъла
        if (crm_Profiles::haveRightFor('single', $profileRec)) {
            $resArr = crm_Profiles::getUrl($userId);
        }
    }
    
    
    public static function on_AfterPrepareListRows($mvc, $res, $data)
    {
        foreach ($data->rows as $id => &$row) {
            $row->title = "<div style='font-size:1.2em;margin-bottom:3px;'>" . crm_Profiles::createLink($id) . '</div>';
            $row->title .= "<div style='color:#666;margin-bottom:3px;margin-left:3px;'>" . $row->names . '</div>';
            $row->title .= "<div style='margin-left:3px;'>" . $row->email . '</div>';
            
            // Добавяме меню
            $row->_rowTools->addLink('Профил', crm_Profiles::getUrl($id), 'ef_icon=img/16/user-profile.png');
        }
    }
    
    
    /**
     * След изтриване на запис
     */
    protected static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
        // След изтриване на потребителя, да се изтрие и профила
        foreach ($query->getDeletedRecs() as $rec) {
            $inst = cls::get('crm_Profiles');
            foreach ($inst->fields as $fName => $fKey) {
                if ($fKey->kind != 'FLD') {
                    unset($inst->fields[$fName]);
                }
            }
            crm_Profiles::delete(array("#userId = '[#1#]'", $rec->id));
        }
    }
}
    
