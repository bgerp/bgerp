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
     * Извиква се преди изпълняването на екшън
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
        // Ако няма ключ, да не сработва
        $key = null;
        if ((Request::get('isPwa') == 'yes') || ($action == 'pwasubscribe')) {
            $dId = cms_Domains::getCurrent('id', false);
            if ($dId) {
                $dRec = cms_Domains::fetch($dId);
                $key = $dRec->publicKey;

                if (!$key) {

                    return ;
                }
            }

            $canUse = pwa_Settings::canUse();

            if ($canUse != 'yes') {

                return ;
            }
        }

        // Прихваща извикването на екшън
        if ($action == 'pwasubscribe') {
            $defRedirect = array('Portal', 'Show');

            pwa_PushSubscriptions::requireRightFor('subscribe');

            $brid = log_Browsers::getBrid();
            core_Permanent::set('pwa_firstLogin_' . $brid, dt::mysql2timestamp(dt::now()), 1000000);
            Mode::setPermanent('pwaSubscribe', false);

            if ($pRec = pwa_PushSubscriptions::fetch(array("#brid = '[#1#]' AND #state = 'active'", $brid))) {
                $rArr = $defRedirect;
                if ($cu = core_Users::getCurrent()) {
                    if ($cu == $pRec->userId) {
                        $rArr = array('pwa_PushSubscriptions', 'edit', $pRec->id, 'ret_url' => $defRedirect);
                    }
                }

                $res = new Redirect($rArr, 'Това приложение има активен абонамент за известия.');

                return false;
            }

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

            if ($form->rec->subscribe) {
                $form->setField('subscribe', 'input=hidden');
            }

            $appendJS = false;

            if ($form->isSubmitted()) {
                if ($form->rec->subscribe == 'no') {
                    $form->info = tr('Пропускате да се абонирате за известия от системата на това устройство.|<br>|*
                                    Ако искате може да се абонирате по-късно от бутона "Известяване" в профила си.');

                    if ($form->rec->force == 'yes') {
                        $res = new Redirect($defRedirect);

                        return false;
                    }

                    $form->setDefault('force', 'yes');

                    $form->toolbar->addBtn('Назад', array($mvc, 'pwaSubscribe'), 'ef_icon=img/16/back16.png');
                } else if ($form->rec->subscribe) {
                    $appendJS = true;
                }
            }

            $form->toolbar->addSbBtn('Продължи', 'default', 'id=filter', 'ef_icon = img/16/move.png');

            if ($appendJS) {
                $form->info = tr('Трябва да позволите получаването на известия от изкачащия прозорец или от настройките на браузъра си.');
            }

            $tpl = $form->renderHtml();

            if (Request::get('forceSubscribe')) {
                $appendJS = true;
                $retUrl = getRetUrl();
                if (!empty($retUrl)) {
                    unset($retUrl['isPwa']);
                    $redirectUrl = toUrl($retUrl, 'local');
                    $subscribeUrl = ht::createLink('oтписване',
                        array('pwa_PushSubscriptions', 'stop', pwa_PushSubscriptions::fetchField(array("#brid = '[#1#]'", $brid)), 'ret_url' => toUrl($retUrl, 'local')), null,
                    'ef_icon=img/16/deletered.png');
                    $infoLink = ht::createLink('информация', 'https://bgerp.com/Bg/PWA-prilozhenie#Instalirane-na-prilozhenieto-ot-potrebitelite', null, 'target=_blank, ef_icon=img/16/bgerp.png');
                    $deniedText = tr("<div>|Грешка при подновяване на абонамента за нотификации.|*</div><div>|Може да се отпишете от |*{$subscribeUrl}</div><div>|Или да активирате в настройките на браузъра|* - {$infoLink}</div>");

                    $tpl->appendOnce("const redirectUrl = '{$redirectUrl}';", 'SCRIPTS');
                    $tpl->appendOnce("const deniedText = '{$deniedText}';", 'SCRIPTS');
                }
            }

            if ($appendJS) {
                $tpl->appendOnce("const applicationServerKey = '{$key}';", 'SCRIPTS');
                $pwaSubscriptionUrl = toUrl(array('pwa_PushSubscriptions', 'Subscribe'), 'local');
                $pwaSubscriptionUrl = urlencode($pwaSubscriptionUrl);

                $tpl->appendOnce("const pwaSubscriptionUrl = '{$pwaSubscriptionUrl}';", 'SCRIPTS');
                $tpl->appendOnce("const forceSubscribe = 'yes';", 'SCRIPTS');

                if ($form->rec->subscribe == 'yesWorking') {
                    $redirectUrl = toUrl($defRedirect, 'local');
                    $tpl->appendOnce("const redirectUrl = '{$redirectUrl}';", 'SCRIPTS');
                }

                $tpl->push('pwa/js/Notifications.js', 'JS');
            }

            $res =  $mvc->renderWrapping($tpl);

            return false;
        }

        // Ако сме се логнали след първо влизане от PWA и нямаме абонамент
        if (Mode::get('pwaSubscribe') && core_Users::getCurrent()) {

            $res = new Redirect(array($mvc, 'pwaSubscribe'));

            return false;
        }

        // Ако няма абонамет и е първо логване, препраща към екшъна за бързо абониране
        if (Request::get('isPwa') == 'yes') {
            $brid = log_Browsers::getBrid();
            $rec = pwa_PushSubscriptions::fetch(array("#brid = '[#1#]'", $brid));

            if (!$rec) {
                if (!core_Permanent::get('pwa_firstLogin_' . $brid)) {
                    if (!core_Users::getCurrent()) {
                        Mode::setPermanent('pwaSubscribe', true);
                    } else {

                        $res = new Redirect(array($mvc, 'pwaSubscribe'));

                        return false;
                    }
                }
            } else {
                core_Permanent::set('pwa_firstLogin_' . $brid, dt::mysql2timestamp(dt::now()), 1000000);
            }

            if (!Request::get('ajax_mode')) {
                // Ако е спрян абонамента, дава възможност за ново абониране
                $pState = pwa_PushSubscriptions::fetchField(array("#brid = '[#1#]'", $brid), 'state');
                if ($pState == 'stopped') {
                    $res = new Redirect(array($mvc, 'pwaSubscribe', 'forceSubscribe' => 'yes', 'ret_url' => true));

                    return false;
                }
            }
        }
    }
}
