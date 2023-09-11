<?php


/**
 * Прокси на 'bgerp_Notifications' за визуализиране на партньорските известия
 *
 * @category  bgerp
 * @package   colab
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.12
 */
class colab_Notifications extends core_Manager
{
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Известия на партньори';


    /**
     * Кой може да вижда партньорските нотификации
     */
    public $canShow = 'partner';


    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    public $loadList = 'cms_ExternalWrapper';


    /**
     * Екшън за визуализиране на партньорските нотификации
     */
    function act_Show()
    {
        self::requireRightFor('show');
        Mode::setPermanent('loadNotificationCount', bgerp_Notifications::getOpenCnt());

        $Portal = cls::get('bgerp_Portal');
        $recArr = $Portal->getRecsForUser(null, true, 'team', 'bgerp_drivers_Notifications');

        $cu = core_Users::getCurrent();
        $rec = $recArr[key($recArr)];

        $rData = new stdClass();
        $res = $Portal->getResForBlock($rec, $rData, $cu);
        $divId = $Portal->getPortalId($rec->originIdCalc);

        $tpl = new core_ET("<div id='{$divId}'>[#BLOCK#]</div>");
        $tpl->append($res, 'BLOCK');
        $tpl = $this->renderWrapping($tpl);

        core_Ajax::subscribe($tpl, array($this, 'refreshNotificationList'), 'refreshNotificationList', 5000);
        Mode::set('currentExternalTab', 'colab_Notifications');

        return $tpl;
    }

    /**
     * Рефреш на формата, ако потребителя се е логнал
     */
    public function act_refreshNotificationList()
    {
        if (Request::get('ajax_mode')) {
            $res = array();

            $notificationCnt = bgerp_Notifications::getOpenCnt();
            $lastCnt = Mode::get('loadNotificationCount');
            if($notificationCnt != $lastCnt){
                $cu = core_Users::getCurrent('id', false);

                if ($cu) {
                    $obj = new stdClass();
                    $obj->func = 'reload';

                    $res[] = $obj;
                }
            }

            return $res;
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * Забранява изтриването на вече използвани сметки
     *
     * @param core_Mvc      $mvc
     * @param string        $requiredRoles
     * @param string        $action
     * @param stdClass|NULL $rec
     * @param int|NULL      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'show'){
            if(!core_Packs::isInstalled('colab') || empty($userId) || !core_Users::isContractor($userId)){
                $requiredRoles = 'no_one';
            }
        }
    }
}