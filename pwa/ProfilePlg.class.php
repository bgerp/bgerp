<?php


/**
 * Свързване на профила с PWA
 *
 * @package   pwa
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pwa_ProfilePlg extends core_Plugin
{



    /**
     * Поставя бутони за генериране на други банкови документи възоснова
     * на този, само ако документа е "чернова"
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if (core_Users::getCurrent() == $data->rec->userId) {
            $dId = cms_Domains::getCurrent('id', false);
            if ($dId) {
                if (pwa_Settings::canUse($dId) == 'yes') {
                    $dRec = cms_Domains::fetch($dId);
                    if ($dRec && $dRec->publicKey) {
                        $data->toolbar->addFnBtn('Известяване', '', 'class=pwa-push-default button linkWithIcon, id=push-subscription-button, order=14, title=Абониране за получаване на PUSH известия, row=2, ef_icon=img/16/pwa.png');
                    }
                }
            }
        }
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param core_Manager $mvc
     * @param core_ET      $tpl
     * @param stdClass     $data
     */
    public static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        $key = null;
        $dId = cms_Domains::getCurrent('id', false);
        if ($dId) {
            $dRec = cms_Domains::fetch($dId);
            $key = $dRec->publicKey;
        }

        if ($key) {
            $tpl->push('pwa/js/Notifications.js', 'JS');
            $tpl->push('pwa/css/profile.css', 'CSS');
            $tpl->appendOnce("const applicationServerKey = '{$key}';", 'SCRIPTS');
            $pwaSubscriptionUrl = toUrl(array('pwa_PushSubscriptions', 'Subscribe'), 'local');
            $pwaSubscriptionUrl = urlencode($pwaSubscriptionUrl);

            $tpl->appendOnce("const pwaSubscriptionUrl = '{$pwaSubscriptionUrl}';", 'SCRIPTS');

            $pButton = new stdClass();
            $pButton->enabled = (object) array('btnText' => tr('Известяване'), 'btnTitle' => tr('Редактиране на настройките за известията'));
            $pButton->disabled = (object) array('btnText' => tr('Известяване'), 'btnTitle' => tr('Пускане на известията'));
            $pButton->computing = (object) array('btnText' => tr('Изчисляване'), 'btnTitle' => tr('Стартиране на ивзестията'));
            $pButton->incompatible = (object) array('btnText' => tr('Несъвсместимо'), 'btnTitle' => tr('Първо трябва да инсталирате приложението'));
            $pButton->denied = (object) array('btnText' => tr('Известяване'), 'btnTitle' => tr('От настройките на браузъра, трябва да се разреши получаването на известия'));

            $pButton = json_encode($pButton);
            $tpl->appendOnce("const pushButtonVals = JSON.parse('{$pButton}');", 'SCRIPTS');
        }
    }
}
