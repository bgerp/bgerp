<?php


/**
 * Плъгин за прихващане на първото логване на потребител в системата
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_plg_FirstLogin extends core_Plugin
{
    /**
     * Прихващаме всяко логване в системата
     */
    function on_AfterLogin($mvc, $userRec)
    {
        if(!$userRec->lastLoginTime && haveRole('admin') && core_Users::count('1=1') == 1) {


            //Зарежда данните за "Моята фирма"
            $html .= crm_Companies::loadData();

            $html .= email_Accounts::loadData();

            $mvc->log($html);
        }

    }
}