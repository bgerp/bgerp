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
        if (self::canInstallPwa($data)) {
            // Бутонът се показва от swRegister.js само когато браузърът
            // предостави beforeinstallprompt за текущото устройство.
            $data->toolbar->addFnBtn('Инсталирай', '', 'class=pwa-install-button linkWithIcon, id=pwa-install-button, order=13, title=Инсталиране на приложението на това устройство, ef_icon=img/16/install.png, style=display:none, aria-hidden=true');
        }

        if (self::getApplicationServerKey($data)) {
            $subscriptionRec = self::getServerSubscription();
            $buttonText = 'Включи известия';
            $buttonTitle = 'Включване на известията на това устройство';
            $buttonClass = 'pwa-push-disabled';
            $buttonUrl = array('bgerp_Portal', 'pwaSubscribe', 'ret_url' => true);

            if ($subscriptionRec && $subscriptionRec->state == 'active') {
                $buttonText = 'Известия';
                $buttonTitle = 'Редактиране на настройките за известията на това устройство';
                $buttonClass = 'pwa-push-enabled';
            } elseif ($subscriptionRec && $subscriptionRec->state == 'stopped') {
                $buttonText = 'Поднови известията';
                $buttonTitle = 'Създаване на нов абонамент за известия на това устройство';
                $buttonClass = 'pwa-push-renew';
                $buttonUrl['forceSubscribe'] = 'yes';
            }

            // Ако JavaScript не се зареди, бутонът остава работещ и води към
            // стандартния екран за настройване на известията.
            $data->toolbar->addBtn($buttonText, $buttonUrl, "class={$buttonClass} button linkWithIcon, id=push-subscription-button, order=14, title={$buttonTitle}, row=2, ef_icon=img/16/pwa.png, aria-busy=false");
        }
    }


    /**
     * Проверява дали показаният профил може да предлага инсталиране на PWA
     *
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function canInstallPwa($data)
    {
        $cu = core_Users::getCurrent();
        if (!$cu || empty($data->rec->userId) || ($cu != $data->rec->userId)) {

            return false;
        }

        $dId = cms_Domains::getCurrent('id', false);

        return $dId && pwa_Settings::canUse($dId) == 'yes';
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
        $key = self::getApplicationServerKey($data);

        if ($key) {
            $tpl->push('pwa/js/Notifications.js', 'JS');
            $tpl->push('pwa/css/profile.css', 'CSS');
            $jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
            $keyJson = json_encode($key, $jsonFlags);
            $tpl->appendOnce("const applicationServerKey = {$keyJson};", 'SCRIPTS');
            $pwaSubscriptionUrl = toUrl(array('pwa_PushSubscriptions', 'Subscribe'), 'local');
            $pwaSubscriptionUrl = urlencode($pwaSubscriptionUrl);
            $pwaSubscriptionUrlJson = json_encode($pwaSubscriptionUrl, $jsonFlags);

            $tpl->appendOnce("const pwaSubscriptionUrl = {$pwaSubscriptionUrlJson};", 'SCRIPTS');

            $pButton = pwa_SubscribePlg::getPushButtonValues();
            $pButtonJson = json_encode($pButton, $jsonFlags);
            $tpl->appendOnce("const pushButtonVals = {$pButtonJson};", 'SCRIPTS');

            $deniedText = tr('Известията са блокирани за това приложение. Разрешете ги от настройките на браузъра или операционната система и опитайте отново.');
            $deniedTextJson = json_encode($deniedText, $jsonFlags);
            $tpl->appendOnce("const deniedText = {$deniedTextJson};", 'SCRIPTS');

            $subscriptionRec = self::getServerSubscription();
            $subscriptionState = $subscriptionRec ? $subscriptionRec->state : 'missing';
            $tpl->appendOnce('const pwaServerSubscriptionState = ' . json_encode($subscriptionState, $jsonFlags) . ';', 'SCRIPTS');
            $subscriptionFingerprint = pwa_SubscribePlg::getSubscriptionFingerprint($subscriptionRec);
            $tpl->appendOnce('const pwaServerSubscriptionFingerprint = ' . json_encode($subscriptionFingerprint, $jsonFlags) . ';', 'SCRIPTS');
            if ($subscriptionState == 'stopped') {
                $tpl->appendOnce("const forceRenewSubscription = 'yes';", 'SCRIPTS');
            }
        }
    }


    /**
     * Връща публичния VAPID ключ, ако потребителят може да управлява
     * известията за показания профил
     *
     * @param stdClass $data
     *
     * @return string|null
     */
    protected static function getApplicationServerKey($data)
    {
        $cu = core_Users::getCurrent();
        if (!$cu || empty($data->rec->userId) || ($cu != $data->rec->userId) || !pwa_PushSubscriptions::haveRightFor('subscribe')) {

            return null;
        }

        $dId = cms_Domains::getCurrent('id', false);
        if (!$dId || pwa_Settings::canUse($dId) != 'yes') {

            return null;
        }

        $dRec = cms_Domains::fetch($dId);

        return ($dRec && !empty($dRec->publicKey)) ? $dRec->publicKey : null;
    }


    /**
     * Връща сървърния абонамент за текущия потребител и устройство
     *
     * @return stdClass|null
     */
    protected static function getServerSubscription()
    {
        $cu = core_Users::getCurrent();
        $dId = cms_Domains::getCurrent('id', false);
        if (!$cu || !$dId) {

            return null;
        }

        $brid = log_Browsers::getBrid();

        return pwa_PushSubscriptions::fetch(array("#brid = '[#1#]' AND #userId = '[#2#]' AND #domainId = '[#3#]'", $brid, $cu, $dId));
    }
}
