<?php


/**
 *
 *
 * @package   pwa
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pwa_SubscribePlg extends core_Plugin
{
    /**
     * След колко минути отново може да се покаже автоматичният екран
     */
    const PROMPT_SNOOZE_MINUTES = 10080;


    /**
     * Колко време да не се показва екранът след изричен отказ
     */
    const PROMPT_DECLINED_MINUTES = 10080;


    /**
     * Връща ключа за запомняне на показването на екрана
     */
    protected static function getPromptKey($type, $brid, $userId, $domainId)
    {
        return 'pwa_prompt_' . $type . '_' . (int) $domainId . '_' . (int) $userId . '_' . $brid;
    }


    /**
     * Запомня резултата от показването на екрана
     */
    protected static function rememberPrompt($type, $value, $lifetime, $brid, $userId, $domainId)
    {
        $key = self::getPromptKey($type, $brid, $userId, $domainId);
        core_Permanent::set($key, $value, $lifetime);
    }


    /**
     * Проверява дали екранът е показван скоро
     */
    protected static function isPromptRemembered($type, $brid, $userId, $domainId)
    {
        $key = self::getPromptKey($type, $brid, $userId, $domainId);
        if (core_Permanent::get($key)) {

            return true;
        }

        return false;
    }


    /**
     * Извиква се преди изпълняването на екшън
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
        // Ако няма ключ, да не сработва
        $key = null;
        if ((Request::get('isPwa') == 'yes') || ($action == 'pwasubscribe')) {
            $dId = cms_Domains::getCurrent('id', false);
            $canUse = null;
            if ($dId) {
                $dRec = cms_Domains::fetch($dId);
                $key = $dRec ? $dRec->publicKey : null;
                if ($key) {
                    $canUse = pwa_Settings::canUse($dId);
                }
            }

            if (!$dId || !$key || $canUse != 'yes') {
                if ($action == 'pwasubscribe') {
                    $res = new Redirect(array('Portal', 'Show'));

                    return false;
                }

                return;
            }
        }

        // Прихваща извикването на екшън
        if ($action == 'pwasubscribe') {
            $defRedirect = array('Portal', 'Show');

            pwa_PushSubscriptions::requireRightFor('subscribe');

            $brid = log_Browsers::getBrid();
            $cu = core_Users::getCurrent();
            $isForced = (bool) Request::get('forceSubscribe');
            Mode::setPermanent('pwaSubscribe', false);

            $pRec = pwa_PushSubscriptions::fetch(array("#brid = '[#1#]' AND #userId = '[#2#]' AND #domainId = '[#3#]'", $brid, $cu, $dId));
            if ($pRec && $pRec->state == 'active') {
                $rArr = $defRedirect;
                if (pwa_PushSubscriptions::haveRightFor('edit', $pRec)) {
                    $rArr = array('pwa_PushSubscriptions', 'edit', $pRec->id, 'ret_url' => $defRedirect);
                }

                $res = new Redirect($rArr, 'Това приложение има активен абонамент за известия.');

                return false;
            }

            $promptType = $isForced ? 'recovery' : 'onboarding';
            self::rememberPrompt($promptType, 'shown', self::PROMPT_SNOOZE_MINUTES, $brid, $cu, $dId);

            $form = cls::get('core_Form');

            $form->title = 'Абониране за известяване';

            $enumOpt = array();
            $enumOpt['yesWorking'] = 'Да, предимно в работно време';
            $enumOpt['yesSet'] = 'Да, искам да ги настроя';
            $enumOpt['no'] = 'Не, не желая';

            $form->FNC('subscribe', 'enum', 'caption=Желаете ли да получавате известия на това устройство?->Избор,silent,input,maxRadio=4,mandatory, columns=1');
            $form->FNC('force', 'enum(no,yes)', 'input=hidden, silent');

            $form->setOptions('subscribe', $enumOpt);

            $form->view = 'vertical';

            $form->input(null, true);

            if ($isForced) {
                $form->setField('subscribe', 'input=none');
            } elseif (!empty($form->rec->subscribe)) {
                $form->setField('subscribe', 'input=hidden');
            }

            $appendJS = $isForced;

            if (!$isForced && $form->isSubmitted()) {
                if ($form->rec->subscribe == 'no') {
                    $form->info = tr('Пропускате да се абонирате за известия от системата на това устройство.|<br>|*
                                    Ако искате може да се абонирате по-късно от бутона "Известяване" в профила си.');

                    if ($form->rec->force == 'yes') {
                        self::rememberPrompt('onboarding', 'declined', self::PROMPT_DECLINED_MINUTES, $brid, $cu, $dId);
                        $res = new Redirect($defRedirect);

                        return false;
                    }

                    $form->setDefault('force', 'yes');

                    $form->toolbar->addBtn('Назад', array($mvc, 'pwaSubscribe'), 'ef_icon=img/16/back16.png');
                } else if ($form->rec->subscribe) {
                    $appendJS = true;
                }
            }

            if ($appendJS) {
                $btnTitle = $isForced ? 'Поднови известията' : 'Разреши известията';
                $btnHint = $isForced ? 'Подновяване на абонамента за известия' : 'Разрешаване на известията за това устройство';
                $form->toolbar->addFnBtn($btnTitle, '', "id=push-subscription-button, class=pwa-push-default button linkWithIcon, title={$btnHint}, ef_icon=img/16/pwa.png");

                if ($isForced && $pRec) {
                    // Това е стандартна сървърна навигация. Отделният id не
                    // позволява JS unsubscribe handler-ът да стартира второ,
                    // конкурентно отписване преди навигацията.
                    $form->toolbar->addBtn('Не желая известия', array('pwa_PushSubscriptions', 'stop', $pRec->id, 'ret_url' => $defRedirect), 'id=pwa-recovery-decline-button, ef_icon=img/16/deletered.png');
                }

                $form->info = $isForced
                    ? tr('Абонаментът за известия на това устройство вече не работи. Натиснете "Поднови известията", за да бъде създаден отново.')
                    : tr('Натиснете "Разреши известията" и потвърдете системния въпрос на браузъра или устройството.');
            } else {
                $form->toolbar->addSbBtn('Продължи', 'default', 'id=filter', 'ef_icon = img/16/move.png');
            }

            $tpl = $form->renderHtml();

            $notificationDeniedText = tr('Известията са блокирани за това приложение. Разрешете ги от настройките на браузъра или операционната система и опитайте отново.');
            if ($isForced) {
                $retUrl = getRetUrl();
                if (!empty($retUrl)) {
                    if (is_array($retUrl)) {
                        unset($retUrl['isPwa']);
                    }
                    $redirectUrl = toUrl($retUrl, 'local');
                    $infoLink = ht::createLink('информация', 'https://bgerp.com/Bg/PWA-prilozhenie#Instalirane-na-prilozhenieto-ot-potrebitelite', null, 'target=_blank, ef_icon=img/16/bgerp.png');
                    $notificationDeniedText = tr("<div>|Известията са забранени за това приложение или браузър.|*</div><div>|Разрешете ги от настройките на браузъра/устройството|* - {$infoLink}</div>");

                    $tpl->appendOnce('const redirectUrl = ' . self::encodeJsValue($redirectUrl) . ';', 'SCRIPTS');
                }
            }

            if ($appendJS) {
                $tpl->appendOnce('const pushButtonVals = ' . self::encodeJsValue(self::getPushButtonValues()) . ';', 'SCRIPTS');
                $tpl->appendOnce('const deniedText = ' . self::encodeJsValue($notificationDeniedText) . ';', 'SCRIPTS');
                $tpl->appendOnce('const applicationServerKey = ' . self::encodeJsValue($key) . ';', 'SCRIPTS');
                $pwaSubscriptionUrl = toUrl(array('pwa_PushSubscriptions', 'Subscribe'), 'local');
                $pwaSubscriptionUrl = urlencode($pwaSubscriptionUrl);

                $tpl->appendOnce('const pwaSubscriptionUrl = ' . self::encodeJsValue($pwaSubscriptionUrl) . ';', 'SCRIPTS');
                $serverSubscriptionState = $pRec ? $pRec->state : 'missing';
                $tpl->appendOnce('const pwaServerSubscriptionState = ' . self::encodeJsValue($serverSubscriptionState) . ';', 'SCRIPTS');
                $serverSubscriptionFingerprint = self::getSubscriptionFingerprint($pRec);
                $tpl->appendOnce('const pwaServerSubscriptionFingerprint = ' . self::encodeJsValue($serverSubscriptionFingerprint) . ';', 'SCRIPTS');

                if (($form->rec->subscribe ?? null) == 'yesWorking') {
                    $redirectUrl = toUrl($defRedirect, 'local');
                    $tpl->appendOnce('const redirectUrl = ' . self::encodeJsValue($redirectUrl) . ';', 'SCRIPTS');
                }

                if ($isForced) {
                    $tpl->appendOnce("const forceRenewSubscription = 'yes';", 'SCRIPTS');
                }

                $tpl->push('pwa/js/Notifications.js', 'JS');
                $tpl->push('pwa/css/profile.css', 'CSS');
            }

            $res =  $mvc->renderWrapping($tpl);

            return false;
        }

        // Ако сме се логнали след първо влизане от PWA и нямаме абонамент
        if (Mode::get('pwaSubscribe') && core_Users::getCurrent()) {
            $brid = log_Browsers::getBrid();
            $cu = core_Users::getCurrent();
            $dId = cms_Domains::getCurrent('id', false);
            Mode::setPermanent('pwaSubscribe', false);

            if (!self::isPromptRemembered('onboarding', $brid, $cu, $dId)) {
                $res = new Redirect(array($mvc, 'pwaSubscribe'));

                return false;
            }
        }

        // Ако няма абонамет и е първо логване, препраща към екшъна за бързо абониране
        if (Request::get('isPwa') == 'yes') {
            $brid = log_Browsers::getBrid();
            $cu = core_Users::getCurrent();
            $dId = cms_Domains::getCurrent('id', false);
            $rec = null;
            if ($cu) {
                $rec = pwa_PushSubscriptions::fetch(array("#brid = '[#1#]' AND #userId = '[#2#]' AND #domainId = '[#3#]'", $brid, $cu, $dId));
            }

            if (!$rec) {
                if (!$cu) {
                    Mode::setPermanent('pwaSubscribe', true);
                } elseif (!self::isPromptRemembered('onboarding', $brid, $cu, $dId)) {
                    $res = new Redirect(array($mvc, 'pwaSubscribe'));

                    return false;
                }
            }

            if ($cu && $rec && !Request::get('ajax_mode')) {
                // Ако е спрян абонамента, дава възможност за ново абониране
                if ($rec->state == 'stopped' && !self::isPromptRemembered('recovery', $brid, $cu, $dId)) {
                    $res = new Redirect(array($mvc, 'pwaSubscribe', 'forceSubscribe' => 'yes', 'ret_url' => true));

                    return false;
                }
            }
        }
    }


    /**
     * Кодира стойност за безопасно вграждане в JavaScript
     *
     * @param mixed $value
     *
     * @return string
     */
    protected static function encodeJsValue($value)
    {
        return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }


    /**
     * Връща безопасен отпечатък на данните за абонамента
     *
     * @param stdClass|null $rec
     *
     * @return string|null
     */
    public static function getSubscriptionFingerprint($rec)
    {
        if (!$rec || !isset($rec->endpoint, $rec->publicKey, $rec->authToken)) {

            return null;
        }

        return hash('sha256', $rec->endpoint . "\0" . $rec->publicKey . "\0" . $rec->authToken);
    }


    /**
     * Връща преведените текстове за състоянията на PUSH бутона
     *
     * @return stdClass
     */
    public static function getPushButtonValues()
    {
        $values = new stdClass();
        $values->enabled = (object) array('btnText' => tr('Известия'), 'btnTitle' => tr('Редактиране на настройките за известията на това устройство'));
        $values->disabled = (object) array('btnText' => tr('Включи известия'), 'btnTitle' => tr('Включване на известията на това устройство'));
        $values->renew = (object) array('btnText' => tr('Поднови известията'), 'btnTitle' => tr('Създаване на нов абонамент за известия на това устройство'));
        $values->computing = (object) array('btnText' => tr('Настройване…'), 'btnTitle' => tr('Проверява се абонаментът за известия'));
        $values->incompatible = (object) array('btnText' => tr('Неподдържани известия'), 'btnTitle' => tr('Браузърът или устройството не поддържа PUSH известия'));
        $values->denied = (object) array('btnText' => tr('Известия: блокирани'), 'btnTitle' => tr('Разрешете известията от настройките на браузъра или операционната система'));

        return $values;
    }
}
