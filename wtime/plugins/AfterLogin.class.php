<?php


/**
 * Плъгин за прихващане на логването на потребител в системата
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class wtime_plugins_AfterLogin extends core_Plugin
{


    /**
     * Прихващаме всяко логване в системата
     */
    public function on_AfterLogin($mvc, $userRec, $inputs, $refresh)
    {
        // Ако не се логва, а се рефрешва потребителя
        if ($refresh) {
            
            return ;
        }

        if (wtime_OnSiteEntries::haveRightFor('trackonline', null, $userRec->id)) {
            $lastState = wtime_OnSiteEntries::getLastState(crm_Profiles::getPersonByUser(core_Users::getCurrent()), dt::addDays(-1));
            if (!$lastState || $lastState->type != 'in') {
                Mode::setPermanent('trackonline', 'afterLogin');
            }
        }
    }


    /**
     * Прихващаме всяко логване в системата
     */
    public function on_BeforeLogout($mvc, &$tpl, $cu)
    {
        expect($cu);

        if (wtime_OnSiteEntries::haveRightFor('trackonline', null, $cu)) {
            $lastState = wtime_OnSiteEntries::getLastState(crm_Profiles::getPersonByUser($cu), dt::addDays(-1));
            if ($lastState && $lastState->type != 'out') {
                if (Mode::get('trackonline') != 'skipPopupOut') {
                    Mode::setPermanent('trackonline', 'beforeLogout');

                    $tpl = wtime_plugins_AfterLoginPopup::getPopupTpl(null, false);

                    Mode::set('wrapper', 'page_Empty');

                    return false;
                }
            }
        }
    }
}
