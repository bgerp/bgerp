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
                try {
                    if (wtime_OnSiteEntries::addEntry(crm_Profiles::getPersonByUser($userRec->id), dt::now(), 'in', '', core_Users::getClassId())) {
                        core_Statuses::newStatus('|Начало на работна сесия за служителя|* ' . core_Users::prepareUserNames($userRec->names));
                    }
                } catch (core_exception_Expect $e) { }
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

                    $tpl = wtime_plugins_AfterLogin::getPopupTpl(null, false);

                    Mode::set('wrapper', 'page_Empty');

                    return false;
                }
            }
        }
    }


    /**
     * Връща шаблон за попъп прозорец, който се показва след логване или преди излизане от системата
     *
     * @param null|integer $uId
     * @param boolean $isIn
     * @return core_Et
     */
    public static function getPopupTpl($uId = null, $isIn = true)
    {
        if (!isset($uId)) {
            $uId = core_Users::getCurrent();
        }

        $tpl = getTplFromFile('wtime/tpl/AfterLoginPopup.shtml');
        $tpl->replace(core_Users::prepareUserNames(core_Users::fetchField($uId, 'names')), 'USER_NAME');

        $type = ($isIn) ? 'in' : 'out';

        $tpl->replace(toUrl(array('wtime_OnSiteEntries', 'skipPopup', $uId, 'ret_url' => true, 'type' => $type)), 'SKIP_URL');
        $tpl->replace(toUrl(array('wtime_OnSiteEntries', 'confirmPopup', $uId, 'ret_url' => true, 'type' => $type)), 'CONFIRM_URL');
        if ($isIn) {
            $tpl->replace(tr('Потвърждавате ли начало на работна сесия за служителя'), 'TEXT');
        } else {
            $tpl->replace(tr('Потвърждавате ли край на работната сесия за служителя'), 'TEXT');
        }

        return $tpl;
    }
}
