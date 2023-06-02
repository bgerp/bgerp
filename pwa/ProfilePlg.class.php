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
        $key = pwa_Setup::get('PUBLIC_KEY');
        if ($key) {
            $data->toolbar->addFnBtn('Известия', '', 'class=pwa-push-default button linkWithIcon, id=push-subscription-button, order=14, title=Получаване на известия');
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
        $key = pwa_Setup::get('PUBLIC_KEY');
        if ($key) {
            $tpl->push('pwa/js/Notifications.js', 'JS');
            $tpl->push('pwa/css/profile.css', 'CSS');
            $tpl->appendOnce("const applicationServerKey = '{$key}';", 'SCRIPTS');
            $pwaSubscriptionUrl = toUrl(array('pwa_PushSubscriptions', 'Subscribe'), 'local');
            $pwaSubscriptionUrl = urlencode($pwaSubscriptionUrl);

            $tpl->appendOnce("const pwaSubsctiptionUrl = '{$pwaSubscriptionUrl}';", 'SCRIPTS');

            $pButton = new stdClass();
            $pButton->enabled = (object) array('btnText' => tr('Спиране'), 'btnTitle' => tr('Спиране на известията'));
            $pButton->disabled = (object) array('btnText' => tr('Известия'), 'btnTitle' => tr('Пускане на известията'));
            $pButton->computing = (object) array('btnText' => tr('Изчисляване'), 'btnTitle' => tr('Стартиране на ивзестията'));
            $pButton->incompatible = (object) array('btnText' => tr('Несъвсместимо'), 'btnTitle' => tr('Грешка при пускане на известията'));
            $pButton->denied = (object) array('btnText' => tr('Блокирани'), 'btnTitle' => tr('Трябва да се разрешат получаването на известия от настройките'));

            $pButton = json_encode($pButton);
            $tpl->appendOnce("const pushButtonVals = JSON.parse('{$pButton}');", 'SCRIPTS');
        }
    }
}
