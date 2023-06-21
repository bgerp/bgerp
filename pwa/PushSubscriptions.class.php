<?php
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 *
 *
 * @category  bgerp
 * @package   pwa
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pwa_PushSubscriptions extends core_Manager
{


    /**
     * Кой има права да се абонира в модела?
     */
    public $canSubscribe = 'user';


    /**
     * Заглавие на мениджъра
     */
    public $title = 'PWA Push абонанаменти';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2';


    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'powerUser';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'pwa, admin';


    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'debug';


    /**
     * @var string
     */
    protected $neverValue = 'Никога';


    /**
     * Дефолтна настройка на полетата за известие
     */
    protected $enumOptVal = 'enum(Никога, 1 мин, 5 мин, 20 мин, 1 час, 2 час, 24 часа)';


    /**
     * Вербални стойности на приоритетите
     */
    protected $priorityMapVerb = array('warning' => 'Спешно', 'alert' => 'Критично');


    /**
     * Дефолтни стойности на приоритетите
     */
    protected $defaultValues = array('criticalWorking' => '5 мин', 'urgentWorking' => '20 мин', 'docWorking' => '1 час', 'allWorking' => '1 час');


    /**
     * Заглавие на единичния обект
     */
    public $singleTitle = 'PUSH абонамент';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('userId', 'user', 'caption=Потребител, input=none');
        $this->FLD('brid', 'varchar(8)', 'caption=Браузър, input=none');
        $this->FLD('publicKey', 'varchar', 'caption=Ключ, input=none'); //88
        $this->FLD('authToken', 'varchar', 'caption=Токен, input=none'); //24
        $this->FLD('domainId', 'key(mvc=cms_Domains, select=titleExt)', 'caption=Домейн, input=none');
        $this->FLD('contentEncoding', 'varchar', 'caption=Енкодинг, input=none');
        $this->FLD('endpoint', 'Url', 'caption=Точка, input=none');
        $this->FLD('data', 'blob(compress, serialize)', 'caption=Данни, input=none');

        $this->FLD('criticalWorking', $this->enumOptVal, 'caption=Известяване за критични новости->Работно време');
        $this->FLD('criticalNonWorking', $this->enumOptVal, 'caption=Известяване за критични новости->Неработно време');
        $this->FLD('criticalNight', $this->enumOptVal, 'caption=Известяване за критични новости->През нощта');

        $this->FLD('urgentWorking', $this->enumOptVal, 'caption=Известяване за спешни и критични новости->Работно време');
        $this->FLD('urgentNonWorking', $this->enumOptVal, 'caption=Известяване за спешни и критични новости->Неработно време');
        $this->FLD('urgentNight', $this->enumOptVal, 'caption=Известяване за спешни и критични новости->През нощта');

        $this->FLD('docWorking', $this->enumOptVal, 'caption=Известяване за имейли|*&#44; |запитвания и сигнали->Работно време');
        $this->FLD('docNonWorking', $this->enumOptVal, 'caption=Известяване за имейли|*&#44; |запитвания и сигнали->Неработно време');
        $this->FLD('docNight', $this->enumOptVal, 'caption=Известяване за имейли|*&#44; |запитвания и сигнали->През нощта');

        $this->FLD('allWorking', $this->enumOptVal, 'caption=Известяване за всякакви новости->Работно време');
        $this->FLD('allNonWorking', $this->enumOptVal, 'caption=Известяване за всякакви новости->Неработно време');
        $this->FLD('allNight', $this->enumOptVal, 'caption=Известяване за всякакви новости->През нощта');

        $this->setDbUnique('publicKey, authToken');
        $this->setDbUnique('brid');
    }


    /**
     * Праща ПУШ нотификации към сървъра
     *
     * @param integer $userId - id на потребителя
     * @param string $title - заглавие на съобщението
     * @param string $text - текст на съобщението
     * @param null|array $url - линк за отваряне
     * @param null|bool $tag - таг - ако е зададено, известията ще се презаписват за същия таг
     * @param null|string $icon - икона
     * @param null| string $image - изображение
     * @param null|string $brid - id на браузъра
     * @param null|integer $domainId - id на домейн
     * @param bool $sound - звук
     * @param null|bool $vibration - вибрация
     *
     * @return array
     */
    public static function sendAlert($userId, $title, $text, $url = null, $tag = null, $icon = null, $image = null, $brid = null, $domainId = null, $sound = true, $vibration = null)
    {
        if ($icon !== false) {
            $icon = '/favicon.png';
        }
        $resArr = array();

        if (!core_Composer::isInUse()) {

            self::logNotice('Не е зададена стойност за EF_VENDOR_PATH и не може да се използва composer');

            return $resArr;
        }

        $query = self::getQuery();
        $query->where(array("#userId = '[#1#]'", $userId));

        if (isset($brid)) {
            $query->where(array("#brid = '[#1#]'", $brid));
        }

        $query->orderBy('id', 'DESC');

        if (isset($domainId)) {
            $query->where(array("#domainId = '[#1#]'", $domainId));
        }

        $cAcc = email_Accounts::getCorporateAcc();
        if ($cAcc) {
            $mailTo = $cAcc->email;
        } else {
            $common = email_Accounts::getCommonAndCorporate();
            if (!empty($common)) {
                $mailTo = reset($common);
            }
        }

        if (!isset($mailTo)) {
            $cDomain = cms_Domains::getCurrent('domain', false);
            $mailTo = 'team@' . $cDomain;
        }

        if (!isset($mailTo)) {
            $mailTo = 'localhost@localhost';
        }

        while ($rec = $query->fetch()) {
            if (isset($rec->domainId)) {
                $dRec = cms_Domains::fetch($rec->domainId);
            } else {

                continue;
            }

            try {
                $auth = array(
                    'VAPID' => array(
                        'subject' => "mailto:{$mailTo}",
                        'publicKey' => $dRec->publicKey,
                        'privateKey' => $dRec->privateKey
                    ),
                );
            } catch (core_exception_Expect $e) {
                reportException($e);
                continue;
            } catch (Throwable $t) {
                reportException($t);
                continue;
            } catch (Error $e) {
                reportException($e);
                continue;
            }

            $webPush = new WebPush($auth);

            $s = array('endpoint' => $rec->endpoint, 'publicKey' => $rec->publicKey,
                'authToken' => $rec->authToken, 'contentEncoding' => $rec->contentEncoding);

            $subscription = Subscription::create($s);

            $data = new stdClass();
            $data->title = $title;
            $data->text = $text;
            $data->icon = $icon;
            $data->image = $image;
            $data->sound = $sound;
            $data->vibration = $vibration;
            $data->tag = $tag;
            if (isset($url)) {
                if (is_array($url)) {
                    setIfNot($url['fpn'], true); // From PUSH Notification
                }
                $data->url = toUrl($url);
            }

            $statusObj = $webPush->sendOneNotification($subscription, json_encode($data));
            $reason = $statusObj->getReason();

            $statusData = (object) array('isSuccess' => $statusObj->isSuccess(), 'brid' => $rec->brid, 'userId' => $rec->userId, 'reason' => $reason);

            $resArr[$rec->id] =  $statusData;

            if (!$statusData->isSuccess) {
                self::logDebug("Грешка при изпращане на PUSH известие: {$reason}", $rec->id, 7);
            } else {
                self::logDebug("Успешно изпратено PUSH известие - {$data->text}", $rec->id, 3);
            }
        }

        return $resArr;
    }


    /**
     * Екшън за абониране към получаване на push съобщения
     */
    public function act_Subscribe()
    {
        $this->requireRightFor('subscribe');

        expect(Request::get('ajax_mode'));

        $brid = log_Browsers::getBrid();

        if (Request::get('haveSubscription')) {
            $rec = $this->fetch(array("#brid = '[#1#]'", $brid));
            if ($rec) {
                $statusObj = new stdClass();
                $statusObj->func = 'redirect';
                $statusObj->arg = array('url' => toUrl(array($this, 'edit', $rec->id, 'ret_url' => true)));

                return array($statusObj);
            }
        }
        
        $action = Request::get('action');
        expect($action == 'subscribe' || $action == 'unsubscribe', $action);

        $publicKey = Request::get('publicKey');
        $authToken = Request::get('authToken');
        $endpoint = Request::get('endpoint');
        $contentEncoding = Request::get('contentEncoding');

        expect($publicKey && $authToken, $publicKey, $authToken);

        $cu = core_Users::getCurrent();

        $statusData = array();

        $retUrl = getRetUrl();
        if (empty($retUrl)) {
            $retUrl = crm_Profiles::getUrl(core_Users::getCurrent());
        }

        if ($action == 'unsubscribe') {
            $this->delete(array("#publicKey = '[#1#]' AND #authToken = '[#2#]'", $publicKey, $authToken));
            $this->delete(array("#brid = '[#1#]'", $brid));

            status_Messages::newStatus('Премахване на Push абонамент за получаване на известия');

            $statusObj = new stdClass();
            $statusObj->func = 'redirect';
            $statusObj->arg = array('url' => toUrl($retUrl));
            return array($statusObj);
        } else {
            $rec = new stdClass();
            $rec->userId = $cu;
            $rec->brid = $brid;
            $rec->authToken = $authToken;
            $rec->publicKey = $publicKey;
            $rec->domainId = cms_Domains::getCurrent('id', false);;
            $rec->contentEncoding = $contentEncoding;
            $rec->endpoint = $endpoint;
            $rec->data = (object) array('authToken' => $authToken, 'publicKey' => $publicKey, 'endpoint' => $endpoint, 'contentEncoding' => $contentEncoding);

            $this->save($rec, null, 'REPLACE');

            if ($rec->id) {

                // При успешно абониране, показваме PUSH известие
                $msgTitle = "Абониране за PUSH известия в " . core_Setup::get('EF_APP_TITLE', true);
                $msg = 'Добавен е Push абонамент за получване на известия в "' . core_Setup::get('EF_APP_TITLE', true) . '"';

                $isSendArr = $this->sendAlert($rec->userId, tr($msgTitle),tr($msg),
                            array('pwa_PushSubscriptions', 'edit', $rec->id, 'ret_url' => true), null, null, null, $rec->brid);

                foreach ($isSendArr as $iVal) {
                    if (!$iVal->isSuccess) {
                        status_Messages::newStatus('Добавен е Push абонамент за получване на известия');

                        break;
                    }
                }

                $statusObj = new stdClass();
                $statusObj->func = 'redirect';
                $statusObj->arg = array('url' => toUrl(array($this, 'edit', $rec->id, 'ret_url' => true)));

                return array($statusObj);
            }
        }

        $statusData['type'] = 'notice';
        $statusData['isSticky'] = 0;
        $statusData['timeOut'] = 700;
        $statusData['stayTime'] = 15000;

        if (!isset($statusData['text'])) {
            $statusData['text'] = 'Грешка при добавяне на Push абонамент за получаване на известия';
            $statusData['type'] = 'warning';
            $statusData['isSticky'] = 1;
        }

        $statusObj = new stdClass();
        $statusObj->func = 'showToast';
        $statusObj->arg = $statusData;

        return array($statusObj);
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        foreach ($mvc->defaultValues as $fName => $fVal) {
            $data->form->setDefault($fName, $fVal);
        }
    }


    /**
     * Извиква се след подготовката на toolbar-а на формата за редактиране/добавяне
     */
    protected static function on_AfterPrepareEditToolbar($mvc, $data)
    {
        if ($data->form->rec->userId == core_Users::getCurrent() && $data->form->rec->brid == log_Browsers::getBrid()) {
            $data->form->toolbar->addFnBtn('Отписване', '', 'class=fright pwa-push-default-uns button linkWithIcon, order=30, ef_icon=img/16/rowtools-btn-grey-orange.png, title=Спиране на получаването на Push известия, id=push-subscription-button-unsubscribe');
        }
    }


    /**
     * Изпълнява се след опаковане на съдаржанието от мениджъра
     *
     * @param core_Mvc       $mvc
     * @param string|core_ET $res
     * @param string|core_ET $tpl
     * @param stdClass       $data
     *
     * @return boolean
     */
    public static function on_AfterRenderWrapping(core_Manager $mvc, &$res, &$tpl = null, $data = null)
    {
        $res->push('pwa/js/Notifications.js', 'JS');

        $pwaSubscriptionUrl = toUrl(array('pwa_PushSubscriptions', 'Subscribe'), 'local');
        $pwaSubscriptionUrl = urlencode($pwaSubscriptionUrl);
        $tpl->appendOnce("const pwaSubsctiptionUrl = '{$pwaSubscriptionUrl}';", 'SCRIPTS');
    }


    /**
     * След преобразуването към вербални стойности, проказваме OS и Browser, като
     * скриваме USER_AGENT стринга зад отварящ се блок
     */
    public function on_AfterRecToVerbal($mvc, $row, $rec, $fields)
    {
        $row->brid = log_Browsers::getLink($rec->brid);
    }


    /**
     * Изпълнява се след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('createdOn', 'DESC');
        $data->query->orderBy('id', 'DESC');
    }


    /**
     * Изпращане на известия по крон
     *
     * @return void
     * @throws core_exception_Break
     */
    public function cron_PushAlertForNotifications()
    {
        $maxNotificationsPerUser = 5;

        // Намираме всички регистрирани потребители, които са активни и имат Push абонамент и ги групираме по BRID
        $uArr = $allUsersArr = array();
        $query = $this->getQuery();
        $query->EXT('uState', 'core_Users', 'externalName=state, externalKey=userId');
        $query->where("#uState = 'active'");
        while ($rec = $query->fetch()) {
            $uArr[$rec->userId][$rec->brid] = $rec;
            $allUsersArr[$rec->userId] = $rec->userId;
        }

        if (empty($allUsersArr)) {

            return ;
        }

        // Кога последно е видян портала от тези потребители
        $lastPortalSeen = array();
        foreach ($allUsersArr as $userId => $oArr) {
            $lastPortalSeen[$userId] = bgerp_LastTouch::get('portal', $userId);
        }

        // За последните 48 часа вземаме последните 5 известия на потребител, като ги групираме по приорите
        $ntfsMsg = $userNotifyCnt = array();
        $nQuery = bgerp_Notifications::getQuery();
        $nQuery->where("#state = 'active'");
        $nQuery->where(array("#activatedOn > '[#1#]'", dt::addSecs(-48 * 3600)));
        $nQuery->in('userId', array_keys($uArr));

        $nQuery->XPR('priorityOrder', 'int', "(CASE #priority WHEN 'alert' THEN 1 WHEN 'warning' THEN 2 WHEN 'normal' THEN 3 ELSE 5 END)");
        $nQuery->orderBy('#priorityOrder=ASC');
        $nQuery->orderBy('activatedOn', 'DESC');
        $nQuery->orderBy('id', 'DESC');

        while ($nRec = $nQuery->fetch()) {

            // Прескачаме тези, които са по-стари от последното виждане на портала
            if ($lastPortalSeen[$nRec->userId] > $nRec->activatedOn) {
                continue;
            }

            // Максимум по 5 известия на потребител
            if ($userNotifyCnt[$nRec->userId] >= $maxNotificationsPerUser) {
                continue;
            }

            $ntfsMsg[$nRec->userId][$nRec->priority][$nRec->id] =  $nRec;

            $userNotifyCnt[$nRec->userId]++;
        }

        $now = dt::now();

        foreach ($ntfsMsg as $userId => $nArr) {
            // Определяме времето в момента
            list($d, $t) = explode(' ', $now);
            if ($t > '22:00:00' || $t < '08:00:00') {
                $dayTime = 'Night';
            } elseif ($t > '18:00:00' || $t < '09:00:00' || cal_Calendar::isDayType($d . ' 12:00:00', 'nonworking')
                || cal_Calendar::isHoliday($now) || cal_Calendar::isAbsent($now, $userId)) {
                $dayTime = 'NonWorking';
            } else {
                $dayTime = 'Working';
            }

            // Масис с приоритет спрямо полето
            $daysFieldArr = array();
            $daysFieldArr['critical'] = 'critical' . $dayTime;
            $daysFieldArr['urgent'] = 'urgent' . $dayTime;
            $daysFieldArr['doc'] = 'doc' . $dayTime;
            $daysFieldArr['all'] = 'all' . $dayTime;

            foreach ($nArr as $priority => $nArr2) {
                foreach ($nArr2 as $msgObj) {
                    foreach ((array)$uArr[$userId] as $brid => $uRec) {

                        // Проверяваме дали преди това има изпратено известие
                        $showUrlHash = md5($msgObj->url . '|' . $userId . '|' . $brid);
                        if (core_Permanent::get($showUrlHash)) {
                            self::logDebug("Прескочено изпращане на PUSH известие поради дублиране на URL: {$msgObj->url}", $uRec->id, 7);

                            continue;
                        }

                        $mustSend = false;

                        // Спрямо настройките, определяме дали трябва да се изпрати известие за тази нотификация
                        $msg = $msgObj->msg;
                        $msgLower = mb_strtolower($msg);
                        foreach ($daysFieldArr as $fType => $fName) {
                            if ($fType == 'doc') {
                                $correctDoc = false;
                                if ((strpos($msgLower, '|добави|') !== false) || (strpos($msgLower, '|хареса') !== false)
                                    || (strpos($msgLower, '|промени|') !== false) || (strpos($msgLower, '|сподели|') !== false)) {

                                    if ((strpos($msgLower, '|входящ имейл|') !== false) || (strpos($msgLower, '|задача|') !== false)
                                        || (strpos($msgLower, '|запитване|') !== false)) {

                                        $correctDoc = true;
                                    }
                                }
                                if (!$correctDoc) {

                                    continue;
                                }
                            } else {
                                if ($fType == 'critical') {
                                    if ($priority != 'alert') {
                                        continue;
                                    }
                                }

                                if ($fType == 'urgent') {
                                    if (($priority != 'alert') || ($priority != 'warning')) {

                                        continue;
                                    }
                                }
                            }

                            $time = $uRec->{$fName};
                            if (!isset($time)) {
                                $time = $this->defaultValues[$fName];
                            }

                            if (!isset($time)) {
                                continue;
                            }

                            if ($time == $this->neverValue) {

                                continue;
                            }

                            $timeVal = cls::get('type_Time')->fromVerbal($time);

                            $bTime = dt::subtractSecs($timeVal);

                            if ($bTime > $msgObj->activatedOn) {
                                $mustSend = true;
                            }

                            if ($mustSend) {
                                break;
                            }
                        }

                        if (!$mustSend) {
                            continue;
                        }

                        $priorityVerb = isset($this->priorityMapVerb[$priority]) ?  $this->priorityMapVerb[$priority]: 'Ново';
                        $msgTitle = "{$priorityVerb} известие в " . core_Setup::get('EF_APP_TITLE', true);

                        // Превеждама заглавието и съобщението спрямо настройките на съответния потребител
                        $nRecUserId = $nRec->userId;
                        $sudo = null;
                        if ($nRecUserId > 0) {
                            $sudo = core_Users::sudo($nRecUserId);
                        }

                        $lg = core_Setup::get('EF_USER_LANG', true);

                        if ($lg) {
                            core_Lg::push($lg);
                        }

                        $msg = tr("|*{$msg}");
                        $msgTitle = tr($msgTitle);

                        if ($lg) {
                            core_Lg::pop();
                        }

                        if ($sudo) {
                            core_Users::exitSudo();
                        }

                        $url = bgerp_Notifications::getUrl($msgObj);

                        $urlArr = array($this, 'openUrl', 'url' => toUrl($url, 'local'), 'hash' => $showUrlHash);

                        // Изпращаме известието и записваме в лога съответното действие
                        $isSendArr = $this->sendAlert($userId, $msgTitle, $msg, $urlArr, null, null, null, $brid);

                        $lifetime = 24 * 60;
                        foreach ($isSendArr as $iVal) {
                            $resStatusMsg = 'Неуспешно';
                            $lifetime = 2 * 60; // 2 часа за повторно изпращане, ако има грешка
                            if ($iVal->isSuccess) {
                                $resStatusMsg = 'Успешно';
                                $lifetime = 24 * 60; // 24 часа за повторно изпращане, ако няма грешка
                            }

                            self::logDebug("{$resStatusMsg} изпращане на известие: '{$msgTitle}': '{$msg}'", $uRec->id, 7);
                        }

                        core_Permanent::set($showUrlHash, 1, $lifetime);
                    }
                }
            }
        }
    }


    /**
     * След отваряне на линка, премахва хеша от списъка с отворени линкове и редиректва към зададения линк
     */
    function act_OpenUrl()
    {
        $this->requireRightFor('subscribe');

        $url = Request::get('url');
        $hash = Request::get('hash');

        core_Permanent::remove($hash);

        $urlArr = parseLocalUrl($url);

        return new Redirect($urlArr);
    }


    /**
     * Изпраща съобщение до потребител
     *
     * @param $userId
     * @param $msg
     *
     * @see remote_SendMessageIntf::sendMessage()
     *
     * @return string
     */
    public function sendMessage($userId, $msg)
    {
        $res = false;

        $sArr = $this->sendAlert($userId, 'bgERP notification', $msg, array('Portal', 'Show', '#' => 'notificationsPortal'), 'Notifications');

        if (!empty($sArr)) {
            foreach ($sArr as $s) {
                if ($s->isSuccess) {
                    $res = true;

                    break;
                }
            }
        }

        return $res;
    }


    /**
     * Пращане на тестово известие и показване на дебъг информация
     */
    function act_Test()
    {
        requireRole('admin');

        $userId = Request::get('userId');
        if (!isset($userId) || ($userId <= 0)) {
            $userId = core_Users::getCurrent();
        }

        bp($this->sendAlert($userId, "Тестово известие", "Тестово известие: " . rand(1, 1111), array('Portal', 'Show'), 'Test'));
    }
}
