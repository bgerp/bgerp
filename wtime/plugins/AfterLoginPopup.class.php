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
class wtime_plugins_AfterLoginPopup extends core_Plugin
{

    /**
     *
     * @param $invoker
     * @return void
     */
    public static function on_Output(&$invoker)
    {
        // Ако трябва да се показва попъп прозорец след логване
        if (Mode::get('trackonline') == 'afterLogin') {
            $tpl = self::getPopupTpl();

            $invoker->appendOnce($tpl);
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
