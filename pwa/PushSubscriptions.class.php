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
     * Максимална дължина на browser PUSH endpoint-а
     */
    const ENDPOINT_MAX_LENGTH = 1024;


    /**
     * Кой има права да се абонира в модела?
     */
    public $canSubscribe = 'user';


    /**
     * Кой има права да се абонира в модела?
     */
    public $canStop = 'user';


    /**
     * Заглавие на мениджъра
     */
    public $title = 'Абонаменти за известяване';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, plg_Modified, pwa_Wrapper, plg_State';


    /**
     * Стойност по подразбиране на състоянието
     *
     * @see plg_State
     */
    public $defaultState = 'active';


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
    protected $defaultValues = array('criticalWorking' => '5 мин', 'criticalNonWorking' => '5 мин', 'criticalNight' => '5 мин',
                                     'urgentWorking' => '20 мин', 'urgentNonWorking' => '20 мин',
                                     'docWorking' => '20 мин',
                                     'shareWorking' => '20 мин',
                                     'allWorking' => '1 час',
                                     'groupNotify' => 'yes',
                                     'forceNotify' => 'no');


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
        $this->FLD('publicKey', 'varchar(128)', 'caption=Ключ, input=none'); //88
        $this->FLD('authToken', 'varchar(128)', 'caption=Токен, input=none'); //24
        $this->FLD('domainId', 'key(mvc=cms_Domains, select=titleExt)', 'caption=Домейн, input=none');
        $this->FLD('contentEncoding', 'varchar', 'caption=Енкодинг, input=none');
        $this->FLD('endpoint', 'url(' . self::ENDPOINT_MAX_LENGTH . ')', 'caption=Точка, input=none');
        $this->FLD('data', 'blob(compress, serialize)', 'caption=Данни, input=none');
        $this->FLD('subscriptionVersion', 'varchar(32)', 'caption=Версия на абонамента, input=none');

        $this->FLD('criticalWorking', $this->enumOptVal, 'caption=Известяване за критични новости->Работно време');
        $this->FLD('criticalNonWorking', $this->enumOptVal, 'caption=Известяване за критични новости->Неработно време');
        $this->FLD('criticalNight', $this->enumOptVal, 'caption=Известяване за критични новости->През нощта');

        $this->FLD('urgentWorking', $this->enumOptVal, 'caption=Известяване за спешни и критични новости->Работно време');
        $this->FLD('urgentNonWorking', $this->enumOptVal, 'caption=Известяване за спешни и критични новости->Неработно време');
        $this->FLD('urgentNight', $this->enumOptVal, 'caption=Известяване за спешни и критични новости->През нощта');

        $this->FLD('docWorking', $this->enumOptVal, 'caption=Известяване за имейли|*&#44; |запитвания и сигнали->Работно време');
        $this->FLD('docNonWorking', $this->enumOptVal, 'caption=Известяване за имейли|*&#44; |запитвания и сигнали->Неработно време');
        $this->FLD('docNight', $this->enumOptVal, 'caption=Известяване за имейли|*&#44; |запитвания и сигнали->През нощта');

        $this->FLD('shareWorking', $this->enumOptVal, 'caption=Известяване за споделяне->Работно време');
        $this->FLD('shareNonWorking', $this->enumOptVal, 'caption=Известяване за споделяне->Неработно време');
        $this->FLD('shareNight', $this->enumOptVal, 'caption=Известяване за споделяне->През нощта');

        $this->FLD('allWorking', $this->enumOptVal, 'caption=Известяване за всякакви новости->Работно време');
        $this->FLD('allNonWorking', $this->enumOptVal, 'caption=Известяване за всякакви новости->Неработно време');
        $this->FLD('allNight', $this->enumOptVal, 'caption=Известяване за всякакви новости->През нощта');

        $this->FLD('groupNotify', 'enum(no=Не,yes=Да)', 'caption=Групиране на известията->Избор');
        $this->FLD('forceNotify', 'enum(no=Не, yes=Да (Само при промяна на съобщението), yesAll=Да (Винаги при обновяване))', 'caption=Неотворените известия да продължат да се обновяват при промяна->Избор');

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
     * @param null|string|false $icon - икона
     * @param null| string $image - изображение
     * @param null|string $brid - id на браузъра
     * @param null|integer $domainId - id на домейн
     * @param bool $sound - звук
     * @param null|bool $vibration - вибрация
     * @param array $otherParamsArr - масив с други параметри
     * ['ttl'] - време на живот на известието
     * ['badge'] - иконка подобна на favicon.ico, която се показва в приложението
     *
     * @return array
     */
    public static function sendAlert($userId, $title, $text, $url = null, $tag = null, $icon = null, $image = null, $brid = null, $domainId = null, $sound = true, $vibration = null, $otherParamsArr = array())
    {
        setIfNot($otherParamsArr['ttl'], 3600);
        setIfNot($otherParamsArr['badge'], null);

        if ($icon !== false) {
            if (core_Webroot::isExists('favicon.png')) {
                $icon = '/favicon.png';
            } else if (core_Webroot::isExists('favicon.ico')) {
                $icon = '/favicon.ico';
            }
        }

        if ($otherParamsArr['badge'] !== false) {
            if (core_Webroot::isExists('badge.png')) {
                $otherParamsArr['badge'] = '/badge.png';
            }
        }

        $resArr = array();

        if (!core_Composer::isInUse()) {

            self::logNotice('Не е зададена стойност за EF_VENDOR_PATH и не може да се използва composer');

            return $resArr;
        }

        $query = self::getQuery();
        $query->where(array("#userId = '[#1#]'", $userId));
        $query->where("#state = 'active'");

        if (isset($brid)) {
            $query->where(array("#brid = '[#1#]'", $brid));
        }

        $query->orderBy('id', 'DESC');

        if (isset($domainId)) {
            $query->where(array("#domainId = '[#1#]'", $domainId));
        }

        $mailTo = trim((string) pwa_Setup::get('MAILTO'));
        if (empty($mailTo)) {
            $cAcc = email_Accounts::getCorporateAcc();
            if ($cAcc) {
                $mailTo = $cAcc->email;
            } else {
                $common = email_Accounts::getCommonAndCorporate();
                if (!empty($common)) {
                    $mailTo = reset($common);
                }
            }

            $mailTo = trim((string) $mailTo);
        }

        if (empty($mailTo)) {
            $cDomain = cms_Domains::getCurrent('domain', false);
            $mailTo = 'team@' . $cDomain;
            $mailTo = trim($mailTo);
        }

        if (empty($mailTo)) {
            $mailTo = 'localhost@localhost';
        }

        $Subscriptions = cls::get('pwa_PushSubscriptions');
        while ($rec = $query->fetch()) {
            if (isset($rec->domainId)) {
                $dRec = cms_Domains::fetch($rec->domainId);
            } else {

                continue;
            }

            try {
                if (!$dRec || empty($dRec->publicKey) || empty($dRec->privateKey)) {
                    $reason = 'Липсват VAPID ключове за домейна на PUSH абонамента';
                    $resArr[$rec->id] = (object) array('isSuccess' => false, 'brid' => $rec->brid, 'userId' => $rec->userId, 'reason' => $reason);
                    self::logErr($reason, $rec->id, 7);

                    continue;
                }

                $auth = array(
                    'VAPID' => array(
                        'subject' => "mailto:{$mailTo}",
                        'publicKey' => $dRec->publicKey,
                        'privateKey' => $dRec->privateKey
                    ),
                );

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
                if ($otherParamsArr['badge']) {
                    $data->badge = $otherParamsArr['badge'];
                }

                if (isset($url)) {
                    if (is_array($url)) {
                        setIfNot($url['fpn'], true); // From PUSH Notification
                    }
                    $data->url = toUrl($url);
                }

                $statusObj = $webPush->sendOneNotification($subscription, json_encode($data), array('TTL' => $otherParamsArr['ttl']));
                $reason = $statusObj->getReason();

                $statusData = (object) array('isSuccess' => $statusObj->isSuccess(), 'brid' => $rec->brid, 'userId' => $rec->userId, 'reason' => $reason);

                $resArr[$rec->id] = $statusData;

                if (!$statusData->isSuccess) {
                    self::logDebug("Грешка при изпращане на PUSH известие - '{$reason}'", $rec->id, 7);

                    if (method_exists($statusObj, 'isSubscriptionExpired') && $statusObj->isSubscriptionExpired()) {
                        $Subscriptions->markSubscriptionStoppedIfCurrent($rec);
                    }
                } else {
                    self::logDebug("Успешно изпратено PUSH известие - '{$data->text}'", $rec->id, 3);
                }
            } catch (Throwable $t) {
                reportException($t);

                $reason = $t->getMessage();
                $resArr[$rec->id] = (object) array('isSuccess' => false, 'brid' => $rec->brid, 'userId' => $rec->userId, 'reason' => $reason);
                self::logErr("Грешка при изпращане на PUSH известие - '{$reason}'", $rec->id, 7);
            }
        }

        return $resArr;
    }


    /**
     * Екшън за спиране на абонамент
     *
     * @return void
     * @throws core_exception_Expect
     */
    public function act_Stop()
    {
        $this->requireRightFor('stop');

        $id = Request::get('id', 'int');
        expect($id);

        $subscriptionLock = $this->obtainSubscriptionLock();
        expect($subscriptionLock, 'Друг процес променя PUSH абонаментите. Опитайте отново.');

        try {
            $fetchedRec = $this->fetch($id, '*', false);
            $rec = $fetchedRec ? clone $fetchedRec : null;
            expect($rec);

            expect(core_Users::getCurrent() == $rec->userId);

            $brid = log_Browsers::getBrid();

            expect($brid && $rec->brid == $brid);

            $rec->state = 'closed';

            $savedId = $this->save($rec, 'state,modifiedOn,modifiedBy');
            expect($savedId !== false, 'Не може да се спре PUSH абонаментът');
        } finally {
            $this->releaseSubscriptionLock($subscriptionLock);
        }

        return new Redirect(getRetUrl());
    }


    /**
     * Екшън за абониране към получаване на push съобщения
     */
    public function act_Subscribe()
    {
        $this->requireRightFor('subscribe');

        if (!Request::get('ajax_mode')) {
            self::logWarning('Опит за промяна на PUSH абонамент извън AJAX заявка');

            return self::getAjaxToastResponse('Заявката за известия не може да бъде изпълнена. Обновете страницата и опитайте отново.');
        }

        $cu = core_Users::getCurrent();
        $brid = log_Browsers::getBrid();
        if (!$cu || !$brid) {
            self::logWarning('Липсва потребител или BRID при промяна на PUSH абонамент');

            return self::getAjaxToastResponse('Липсва информация за потребителя или устройството. Влезте отново в системата и опитайте пак.');
        }

        $action = Request::get('action');
        $publicKey = Request::get('publicKey');
        $authToken = Request::get('authToken');
        $endpoint = Request::get('endpoint');
        $contentEncoding = Request::get('contentEncoding');
        $haveSubscription = Request::get('haveSubscription');
        $forceRenewSubscription = Request::get('renewSubscription');
        $hasSubscriptionData = !empty($publicKey) && !empty($authToken) && !empty($endpoint);

        if ($endpoint !== null && strlen($endpoint) > self::ENDPOINT_MAX_LENGTH) {
            self::logWarning('Получен е прекалено дълъг PUSH endpoint');

            return self::getAjaxToastResponse('Адресът на PUSH абонамента е прекалено дълъг. Обновете браузъра и опитайте отново.');
        }

        $retUrl = getRetUrl();
        if (empty($retUrl)) {
            $retUrl = crm_Profiles::getUrl($cu);
        }

        // Старите клиенти изпращат само haveSubscription. Синхронизираме
        // наличния запис, но никога не продължаваме към невалиден action.
        if ($haveSubscription && empty($action) && !$hasSubscriptionData) {
            $rec = $this->fetch(array("#brid = '[#1#]'", $brid));
            if (!$rec) {

                return self::getAjaxToastResponse('Браузърът има абонамент, но липсват данни за него в системата. Изключете и включете известията отново.');
            }

            if (empty($rec->publicKey) || empty($rec->authToken) || empty($rec->endpoint)) {

                return self::getAjaxToastResponse('Записът за известията е непълен. Изключете и включете известията отново.');
            }

            $domainId = cms_Domains::getCurrent('id', false);
            if (!$domainId) {

                return self::getAjaxToastResponse('Не може да се определи домейнът на приложението. Обновете страницата и опитайте отново.');
            }

            if ($rec->domainId != $domainId || $rec->state == 'stopped') {

                return self::getAjaxToastResponse('Съществуващият абонамент е за друг домейн или вече е изтекъл. Изключете и включете известията отново.');
            }

            $legacyEndpoint = $rec->endpoint;
            $legacyPublicKey = $rec->publicKey;
            $legacyAuthToken = $rec->authToken;
            $subscriptionLock = $this->obtainSubscriptionLock();
            if (!$subscriptionLock) {

                return self::getAjaxToastResponse('Друга заявка настройва същия абонамент. Изчакайте няколко секунди и опитайте отново.');
            }

            try {
                $rec = $this->fetch((int) $rec->id, '*', false);
                if (!$rec || $rec->brid != $brid || $rec->domainId != $domainId || $rec->state == 'stopped' ||
                    $rec->endpoint != $legacyEndpoint || $rec->publicKey != $legacyPublicKey || $rec->authToken != $legacyAuthToken) {

                    return self::getAjaxToastResponse('Абонаментът е променен от друга заявка. Обновете страницата и опитайте отново.');
                }

                $ownerChanged = $rec->userId != $cu;
                $mustSendWelcome = $ownerChanged || $rec->state == 'closed';

                // Натиснат бутон "Известия" при вече активен абонамент - потребителят
                // иска настройките си, а не ново абониране
                $wasActiveSubscription = !$ownerChanged && $rec->state == 'active';

                $this->closeMatchingSubscriptions($legacyEndpoint, $legacyPublicKey, $legacyAuthToken);

                // Четем наново и променяме clone, за да не може save()
                // оптимизаторът да сравнява със същия вече мутиран
                // lastFetchedRec (особено при id=1).
                $closedRec = $this->fetch((int) $rec->id, '*', false);
                if (!$closedRec) {
                    throw new core_exception_Expect('Липсва PUSH абонаментът след синхронизирането');
                }
                $rec = clone $closedRec;
                if ($ownerChanged) {
                    $this->setDefaultSubscriptionPreferences($rec);
                }

                $rec->userId = $cu;
                $rec->brid = $brid;
                $rec->domainId = $domainId;
                $rec->state = 'active';
                $rec->subscriptionVersion = $this->getNewSubscriptionVersion();
                $rec->data = (object) array('authToken' => $rec->authToken, 'publicKey' => $rec->publicKey,
                    'endpoint' => $rec->endpoint, 'contentEncoding' => $rec->contentEncoding);

                $subscriptionFields = 'userId,brid,authToken,publicKey,domainId,contentEncoding,endpoint,data,state,subscriptionVersion,modifiedOn,modifiedBy';
                $savedId = $ownerChanged ? $this->save($rec) : $this->save($rec, $subscriptionFields);
                if ($savedId === false) {
                    throw new core_exception_Expect('Не може да се синхронизира PUSH абонаментът');
                }

                if ($mustSendWelcome) {
                    try {
                        $this->scheduleWelcomeNotification($rec);
                    } catch (Throwable $t) {
                        reportException($t);
                        self::logErr('Грешка при планиране на приветстващо PUSH известие', $rec->id, 7);
                    }
                }

                return self::getAjaxRedirectResponse($this->getPostSubscribeRedirectUrl($rec, Request::get('redirectUrl'), $retUrl, $wasActiveSubscription));
            } catch (Throwable $t) {
                reportException($t);
                self::logErr('Грешка при синхронизиране на съществуващ PUSH абонамент', $rec->id ?? null, 7);

                return self::getAjaxToastResponse('Грешка при синхронизиране на известията. Обновете страницата и опитайте отново.');
            } finally {
                $this->releaseSubscriptionLock($subscriptionLock);
            }
        }

        if ($haveSubscription && empty($action) && $hasSubscriptionData) {
            $action = 'subscribe';
        }

        if ($action == 'unsubscribe') {
            $subscriptionLock = $this->obtainSubscriptionLock();
            if (!$subscriptionLock) {

                return self::getAjaxToastResponse('Друга заявка настройва известията. Изчакайте няколко секунди и опитайте отново.');
            }

            try {
                $query = $this->getQuery();
                if ($publicKey && $authToken) {
                    $query->where(array("#userId = '[#1#]' AND (#brid = '[#2#]' OR (#publicKey = '[#3#]' AND #authToken = '[#4#]'))", $cu, $brid, $publicKey, $authToken));
                } else {
                    $query->where(array("#userId = '[#1#]' AND #brid = '[#2#]'", $cu, $brid));
                }

                while ($rec = $query->fetch()) {
                    if ($rec->state == 'closed') {

                        continue;
                    }

                    // Пазим оригиналния lastFetchedRec за коректното сравнение
                    // в save(); при id=1 PHP 7.4 иначе може да приеме мутиралия
                    // object за кеширания запис и да пропусне state.
                    $rec = clone $rec;
                    $rec->state = 'closed';
                    $savedId = $this->save($rec, 'state,modifiedOn,modifiedBy');
                    if ($savedId === false) {
                        throw new core_exception_Expect('Не може да се изключи PUSH абонаментът');
                    }
                }

                status_Messages::newStatus('Премахване на Push абонамент за получаване на известия');

                $unsubscribeRedirectUrl = Request::get('redirectUrl');
                if ($unsubscribeRedirectUrl && $unsubscribeRedirectUrl != 'none') {
                    $parsedRedirectUrl = parseLocalUrl($unsubscribeRedirectUrl);
                    if ($parsedRedirectUrl) {
                        $retUrl = $parsedRedirectUrl;
                    }
                }

                return self::getAjaxRedirectResponse($retUrl);
            } catch (Throwable $t) {
                reportException($t);
                self::logErr('Грешка при премахване на PUSH абонамент', null, 7);

                return self::getAjaxToastResponse('Грешка при изключване на известията. Обновете страницата и опитайте отново.');
            } finally {
                $this->releaseSubscriptionLock($subscriptionLock);
            }
        }

        if ($action != 'subscribe') {
            self::logWarning('Невалидно действие за PUSH абонамент');

            return self::getAjaxToastResponse('Невалидна заявка за известия. Обновете страницата и опитайте отново.');
        }

        if (!$hasSubscriptionData) {

            return self::getAjaxToastResponse('Браузърът не предостави всички данни за абонамента. Проверете разрешенията за известия и опитайте отново.');
        }

        $domainId = cms_Domains::getCurrent('id', false);
        if (!$domainId) {

            return self::getAjaxToastResponse('Не може да се определи домейнът на приложението. Обновете страницата и опитайте отново.');
        }

        $subscriptionLock = $this->obtainSubscriptionLock();
        if (!$subscriptionLock) {

            return self::getAjaxToastResponse('Друга заявка настройва същия абонамент. Изчакайте няколко секунди и опитайте отново.');
        }

        $rec = null;
        try {
            $bridRec = $this->fetch(array("#brid = '[#1#]'", $brid), '*', false);
            $subscriptionRec = $this->fetch(array("#endpoint = '[#1#]' AND #publicKey = '[#2#]' AND #authToken = '[#3#]'", $endpoint, $publicKey, $authToken), '*', false);
            $rec = $bridRec ? $bridRec : $subscriptionRec;

            $isNew = !$rec;
            if ($isNew) {
                $rec = new stdClass();
                $this->setDefaultSubscriptionPreferences($rec);
            }

            $ownerChanged = !$isNew && $rec->userId != $cu;
            $sameOwnerAndDomain = !$isNew && !$ownerChanged && $rec->domainId == $domainId;

            // Натиснат бутон "Известия" при вече активен абонамент - потребителят
            // иска настройките си, а не ново абониране
            $wasActiveSubscription = $sameOwnerAndDomain && $rec->state == 'active';

            $sameStoppedSubscription = $sameOwnerAndDomain && $rec->state == 'stopped' &&
                $rec->endpoint == $endpoint && $rec->publicKey == $publicKey && $rec->authToken == $authToken;
            if ($sameStoppedSubscription && !$forceRenewSubscription) {

                return self::getAjaxToastResponse(
                    'Абонаментът е изтекъл и трябва да бъде създаден отново. Натиснете „Поднови известията“.',
                    'warning',
                    array('pwaRenewSubscription' => 1)
                );
            }

            $mustSendWelcome = $isNew || $ownerChanged || $rec->state == 'closed' || $rec->state == 'stopped';

            if (!$isNew && ($rec->endpoint != $endpoint || $rec->publicKey != $publicKey || $rec->authToken != $authToken)) {
                // Затваряме и стария endpoint на избрания BRID преди да го
                // презапишем. При неуспешен финален save fail-safe резултатът
                // е "closed", а не останал активен стар/чужд endpoint.
                if (!empty($rec->endpoint) && !empty($rec->publicKey) && !empty($rec->authToken)) {
                    $this->closeMatchingSubscriptions($rec->endpoint, $rec->publicKey, $rec->authToken);
                } else {
                    $this->closeSubscriptionById($rec->id);
                }
            }

            $this->closeMatchingSubscriptions($endpoint, $publicKey, $authToken);

            if (!$isNew) {
                // Вземаме действителното DB състояние и мутираме clone, а не
                // lastFetchedRec на MVC.
                $closedRec = $this->fetch((int) $rec->id, '*', false);
                if (!$closedRec) {
                    throw new core_exception_Expect('Липсва PUSH абонаментът след затваряне на дубликатите');
                }
                $rec = clone $closedRec;
                if ($ownerChanged) {
                    $this->setDefaultSubscriptionPreferences($rec);
                }
            }

            $rec->userId = $cu;
            $rec->brid = $brid;
            $rec->authToken = $authToken;
            $rec->publicKey = $publicKey;
            $rec->domainId = $domainId;
            $rec->contentEncoding = $contentEncoding ? $contentEncoding : 'aesgcm';
            $rec->endpoint = $endpoint;
            $rec->data = (object) array('authToken' => $authToken, 'publicKey' => $publicKey,
                'endpoint' => $endpoint, 'contentEncoding' => $rec->contentEncoding);
            $rec->state = 'active';
            $rec->subscriptionVersion = $this->getNewSubscriptionVersion();

            $subscriptionFields = 'userId,brid,authToken,publicKey,domainId,contentEncoding,endpoint,data,state,subscriptionVersion,modifiedOn,modifiedBy';
            $savedId = ($isNew || $ownerChanged) ? $this->save($rec) : $this->save($rec, $subscriptionFields);
            if ($savedId === false) {
                throw new core_exception_Expect('Не може да се запише PUSH абонаментът');
            }

            if (empty($rec->id)) {
                $rec->id = $savedId;
            }

            if (empty($rec->id)) {
                throw new core_exception_Expect('Не може да се запише PUSH абонаментът');
            }

            if ($mustSendWelcome) {
                try {
                    $this->scheduleWelcomeNotification($rec);
                } catch (Throwable $t) {
                    reportException($t);
                    self::logErr('Грешка при планиране на приветстващо PUSH известие', $rec->id, 7);
                }
            }

            $redirectUrl = $this->getPostSubscribeRedirectUrl($rec, Request::get('redirectUrl'), $retUrl, $wasActiveSubscription);

            return self::getAjaxRedirectResponse($redirectUrl);
        } catch (Throwable $t) {
            reportException($t);
            self::logErr('Грешка при записване на PUSH абонамент', isset($rec->id) ? $rec->id : null, 7);

            return self::getAjaxToastResponse('Грешка при добавяне на абонамента за известия. Обновете страницата и опитайте отново.');
        } finally {
            $this->releaseSubscriptionLock($subscriptionLock);
        }
    }


    /**
     * Връща AJAX команда за показване на съобщение
     *
     * @param string $text
     * @param string $type
     * @param array  $extraArgs
     *
     * @return array
     */
    protected static function getAjaxToastResponse($text, $type = 'warning', $extraArgs = array())
    {
        $statusObj = new stdClass();
        $statusObj->func = 'showToast';
        $statusObj->arg = array_merge(
            array('text' => tr($text), 'type' => $type, 'isSticky' => 1, 'timeOut' => 700, 'stayTime' => 15000),
            (array) $extraArgs
        );

        return array($statusObj);
    }


    /**
     * Връща AJAX команда за пренасочване
     *
     * @param mixed $url
     *
     * @return array
     */
    protected static function getAjaxRedirectResponse($url)
    {
        $statusObj = new stdClass();
        $statusObj->func = 'redirect';
        $statusObj->arg = array('url' => toUrl($url));

        return array($statusObj);
    }


    /**
     * Определя достъпно пренасочване след абониране
     *
     * @param stdClass  $rec
     * @param string    $requestedRedirectUrl
     * @param array     $retUrl
     * @param bool      $isSettingsRequest - бутонът е натиснат при активен абонамент
     *
     * @return array
     */
    protected function getPostSubscribeRedirectUrl($rec, $requestedRedirectUrl = null, $retUrl = null, $isSettingsRequest = false)
    {
        if ($requestedRedirectUrl && $requestedRedirectUrl != 'none') {
            $requestedRedirect = parseLocalUrl($requestedRedirectUrl);
            if ($requestedRedirect && $this->canUsePostSubscribeRedirect($requestedRedirect, $rec)) {

                return $requestedRedirect;
            }
        }

        if ($this->mustOpenSubscriptionSettings($rec, $isSettingsRequest)) {

            return array($this, 'edit', $rec->id, 'ret_url' => true);
        }

        if ($retUrl && $this->canUsePostSubscribeRedirect($retUrl, $rec)) {

            return $retUrl;
        }

        return array('Portal', 'Show');
    }


    /**
     * Проверява дали след абониране да се отвори формата с настройките
     *
     * При натиснат бутон "Известия" на вече абонирано устройство формата се
     * отваря винаги - това е изричното желание на потребителя. Автоматично
     * след ново абониране се отваря само ако още не е минавал през нея и
     * настройките са дефолтните.
     *
     * @param stdClass $rec
     * @param bool     $isSettingsRequest
     *
     * @return bool
     */
    protected function mustOpenSubscriptionSettings($rec, $isSettingsRequest = false)
    {
        if (!$this->haveRightFor('edit', $rec)) {

            return false;
        }

        if ($isSettingsRequest) {

            return true;
        }

        if (pwa_SubscribePlg::isPromptRemembered('settings', $rec->brid, $rec->userId, $rec->domainId)) {

            return false;
        }

        return $this->haveDefaultSubscriptionPreferences($rec);
    }


    /**
     * Проверява дали абонаментът е още с дефолтните настройки за известяване
     *
     * @param stdClass $rec
     *
     * @return bool
     */
    protected function haveDefaultSubscriptionPreferences($rec)
    {
        foreach ($this->getSubscriptionPreferenceFields() as $field) {
            $value = isset($rec->{$field}) ? $rec->{$field} : null;

            // Незададеното поле работи с дефолтната си стойност
            if (!isset($value) || ($value === '')) {

                continue;
            }

            if ((string) $value !== (string) $this->getSubscriptionPreferenceDefault($field)) {

                return false;
            }
        }

        return true;
    }


    /**
     * Проверява дали post-subscribe URL няма да отвори недостъпна edit форма
     *
     * @param array    $url
     * @param stdClass $rec
     *
     * @return bool
     */
    protected function canUsePostSubscribeRedirect($url, $rec)
    {
        $controller = $url['Ctr'] ?? ($url[0] ?? null);
        $action = $url['Act'] ?? ($url[1] ?? null);
        if (is_object($controller)) {
            $controller = get_class($controller);
        }

        if (strtolower((string) $controller) == strtolower(get_class($this)) && strtolower((string) $action) == 'edit') {

            return $this->haveRightFor('edit', $rec);
        }

        return true;
    }


    /**
     * Заключва конкурентните промени на PUSH subscription записите
     *
     * @return string|null Име на придобития framework lock
     */
    protected function obtainSubscriptionLock()
    {
        // Един общ framework lock покрива едновременно уникалния BRID и
        // endpoint инварианта, включително заявки с различен BRID за един
        // endpoint. Критичната секция съдържа само framework fetch/save.
        $lockName = 'pwaPushSubscriptions';

        try {
            if (core_Locks::obtain($lockName, 30, 5, 2)) {

                return $lockName;
            }
        } catch (Throwable $t) {
            reportException($t);
            self::logErr('Грешка при заключване на PUSH абонамент', null, 7);
        }

        return null;
    }


    /**
     * Освобождава framework lock-а на subscription операцията
     *
     * @param string|null $lockName
     */
    protected function releaseSubscriptionLock($lockName)
    {
        if (!$lockName) {

            return;
        }

        try {
            core_Locks::release($lockName);
        } catch (Throwable $t) {
            reportException($t);
            self::logErr('Грешка при освобождаване на заключването на PUSH абонамент', null, 7);
        }
    }


    /**
     * Затваря всички записи за един browser endpoint
     *
     * Всеки запис минава през save(), за да се приложат типовете, plugin-ите,
     * audit полетата и физическите имена от модела. Извикващият код държи общия
     * subscription lock и не активира нов запис при грешка в затварянето.
     *
     * @param string $endpoint
     * @param string $publicKey
     * @param string $authToken
     */
    protected function closeMatchingSubscriptions($endpoint, $publicKey, $authToken)
    {
        $query = $this->getQuery();
        $query->where(array(
            "#endpoint = '[#1#]' AND #publicKey = '[#2#]' AND #authToken = '[#3#]' AND #state != 'closed'",
            $endpoint,
            $publicKey,
            $authToken
        ));

        while ($dbRec = $query->fetch()) {
            // Query::fetch() поставя същия обект в lastFetchedRec. Работим
            // върху clone, за да не пропусне save() промяната при id=1.
            $rec = clone $dbRec;
            $rec->state = 'closed';
            $savedId = $this->save($rec, 'state,modifiedOn,modifiedBy');
            if ($savedId === false) {
                throw new core_exception_Expect('Не може да се затворят старите PUSH абонаменти');
            }
        }
    }


    /**
     * Затваря конкретен непълен стар запис преди безопасното му обновяване
     *
     * @param int $id
     */
    protected function closeSubscriptionById($id)
    {
        $dbRec = $this->fetch(array("#id = '[#1#]' AND #state != 'closed'", (int) $id), '*', false);
        if (!$dbRec) {

            return;
        }

        $rec = clone $dbRec;
        $rec->state = 'closed';
        $savedId = $this->save($rec, 'state,modifiedOn,modifiedBy');
        if ($savedId === false) {
            throw new core_exception_Expect('Не може да се затвори старият PUSH абонамент');
        }
    }


    /**
     * Маркира endpoint като изтекъл само ако записът не е бил подновен,
     * докато WebPush заявката е чакала отговор от външния доставчик
     *
     * @param stdClass $rec
     */
    protected function markSubscriptionStoppedIfCurrent($rec)
    {
        $subscriptionLock = $this->obtainSubscriptionLock();
        if (!$subscriptionLock) {
            self::logWarning('Изтекъл PUSH абонамент не е спрян, защото друга заявка го променя', $rec->id ?? null);

            return false;
        }

        try {
            $query = $this->getQuery();
            $query->where(array(
                "#id = '[#1#]' AND #state = 'active' AND #endpoint = '[#2#]' AND #publicKey = '[#3#]' AND #authToken = '[#4#]'",
                (int) $rec->id,
                $rec->endpoint,
                $rec->publicKey,
                $rec->authToken
            ));
            if (!empty($rec->subscriptionVersion)) {
                $query->where(array("#subscriptionVersion = '[#1#]'", $rec->subscriptionVersion));
            } else {
                // Съвместимост със записите отпреди версията. При следващо
                // активиране те винаги получават ненулева стойност.
                $query->where("(#subscriptionVersion IS NULL OR #subscriptionVersion = '')");
            }

            $dbRec = $query->fetch();
            if (!$dbRec) {

                return false;
            }

            $saveRec = clone $dbRec;
            $saveRec->state = 'stopped';
            $savedId = $this->save($saveRec, 'state,modifiedOn,modifiedBy');
            if ($savedId === false) {
                throw new core_exception_Expect('Не може да се промени състоянието на изтеклия PUSH абонамент');
            }

            return true;
        } finally {
            $this->releaseSubscriptionLock($subscriptionLock);
        }
    }


    /**
     * Връща нова версия за всяко (повторно) активиране на абонамент.
     *
     * Така закъснял отговор от push доставчика за по-стара заявка не може
     * да спре междувременно подновен абонамент със същия endpoint и ключове.
     *
     * @return string
     */
    protected function getNewSubscriptionVersion()
    {
        return md5(str::getRand() . uniqid('', true) . microtime(true));
    }


    /**
     * Връща полетата с настройките за известяване на абонамента
     *
     * @return array
     */
    protected function getSubscriptionPreferenceFields()
    {
        return array('criticalWorking', 'criticalNonWorking', 'criticalNight',
            'urgentWorking', 'urgentNonWorking', 'urgentNight',
            'docWorking', 'docNonWorking', 'docNight',
            'shareWorking', 'shareNonWorking', 'shareNight',
            'allWorking', 'allNonWorking', 'allNight',
            'groupNotify', 'forceNotify');
    }


    /**
     * Връща стойността по подразбиране на поле с настройка за известяване
     *
     * @param string $field
     *
     * @return string
     */
    protected function getSubscriptionPreferenceDefault($field)
    {
        return isset($this->defaultValues[$field]) ? $this->defaultValues[$field] : $this->neverValue;
    }


    /**
     * Задава началните настройки на абонамент за нов потребител
     *
     * @param stdClass $rec
     */
    protected function setDefaultSubscriptionPreferences($rec)
    {
        foreach ($this->getSubscriptionPreferenceFields() as $field) {
            $rec->{$field} = $this->getSubscriptionPreferenceDefault($field);
        }
    }


    /**
     * Планира приветстващото известие извън AJAX заявката за абониране
     *
     * @param stdClass $rec
     */
    protected function scheduleWelcomeNotification($rec)
    {
        $appTitle = core_Setup::get('EF_APP_TITLE', true);
        $data = array(
            'subscriptionId' => (int) $rec->id,
            'userId' => (int) $rec->userId,
            'brid' => $rec->brid,
            'title' => tr("Абониране за PUSH известия в {$appTitle}"),
            'message' => tr("Добавен е Push абонамент за получване на известия в \"{$appTitle}\"")
        );

        $callId = core_CallOnTime::setOnce('pwa_PushSubscriptions', 'SendWelcomeNotification', $data, dt::addSecs(1));
        if ($callId === false) {
            throw new core_exception_Expect('Не може да се планира приветстващото PUSH известие');
        }
    }


    /**
     * Изпраща предварително планираното приветстващо PUSH известие
     *
     * @param array $data
     */
    public static function callback_SendWelcomeNotification($data)
    {
        if (!is_array($data) || empty($data['subscriptionId'])) {

            return;
        }

        $rec = self::fetch((int) $data['subscriptionId']);
        if (!$rec || $rec->state != 'active' || $rec->userId != ($data['userId'] ?? null) || $rec->brid != ($data['brid'] ?? null)) {

            return;
        }

        $url = array('Portal', 'Show');
        if (self::haveRightFor('edit', $rec, $rec->userId)) {
            $url = array('pwa_PushSubscriptions', 'edit', $rec->id, 'ret_url' => array('Portal', 'Show'));
        }

        self::sendAlert($rec->userId, $data['title'], $data['message'], $url, null, null, null, $rec->brid);
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

        $mvc->rememberSubscriptionSettingsVisit(isset($data->form->rec->id) ? $data->form->rec->id : null);
    }


    /**
     * Отбелязва, че потребителят е минал през екрана с настройките на устройството
     *
     * След това екранът не се отваря автоматично при следващо абониране -
     * независимо дали настройките са били записани или само разгледани.
     *
     * @param int|null $id
     */
    protected function rememberSubscriptionSettingsVisit($id)
    {
        if (empty($id)) {

            return;
        }

        $cu = core_Users::getCurrent();
        if (!$cu) {

            return;
        }

        // Записът се чете наново - полетата за устройството не идват от формата
        $rec = $this->fetch((int) $id);
        if (!$rec || ($rec->userId != $cu) || empty($rec->brid)) {

            return;
        }

        pwa_SubscribePlg::rememberPrompt('settings', 'visited', $rec->brid, $rec->userId, $rec->domainId);
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
        $res->push('pwa/css/profile.css', 'CSS');

        $pwaSubscriptionUrl = toUrl(array('pwa_PushSubscriptions', 'Subscribe'), 'local');
        $pwaSubscriptionUrl = urlencode($pwaSubscriptionUrl);
        $tpl->appendOnce("const pwaSubscriptionUrl = '{$pwaSubscriptionUrl}';", 'SCRIPTS');
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
        $data->query->orderBy('modifiedOn', 'DESC');
        $data->query->orderBy('id', 'DESC');

        $data->listFilter->FNC('users', "users(rolesForAll=admin,rolesForTeams=admin, showClosedGroups)", 'caption=Потребители, autoFilter');

        // Да се показва полето за търсене
        $data->listFilter->showFields = 'users';

        $data->listFilter->view = 'horizontal';

        //Добавяме бутон "Филтрирай"
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');

        $data->listFilter->setDefault('users', core_Users::getCurrent());

        $data->listFilter->input();

        if (!empty($data->listFilter->rec->users)) {
            $uArr = type_Keylist::toArray($data->listFilter->rec->users);

            // -1 e маркерът за "всички" - тогава не се филтрира
            if (empty($uArr[-1])) {
                $data->query->in('userId', $uArr);
            }
        }
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

//        $nQuery->XPR('priorityOrder', 'int', "(CASE #priority WHEN 'alert' THEN 1 WHEN 'warning' THEN 2 WHEN 'normal' THEN 3 ELSE 5 END)");
//        $nQuery->orderBy('#priorityOrder=ASC');
        $nQuery->orderBy('modifiedOn', 'DESC');
        $nQuery->orderBy('id', 'DESC');

        while ($nRec = $nQuery->fetch()) {

            // Прескачаме тези, които са по-стари от последното виждане на портала
            if ($lastPortalSeen[$nRec->userId] > $nRec->activatedOn) {
                continue;
            }

            if (!isset($userNotifyCnt[$nRec->userId])) {
                $userNotifyCnt[$nRec->userId] = 0;
            }

            // Максимум по 5 известия на потребител
            if ($userNotifyCnt[$nRec->userId] >= $maxNotificationsPerUser) {
                continue;
            }

            $ntfsMsg[$nRec->userId][$nRec->priority][$nRec->id] =  $nRec;

            $userNotifyCnt[$nRec->userId]++;
        }

        $now = dt::now();

        $allNotifyArr = array();

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
            $daysFieldArr['share'] = 'share' . $dayTime;
            $daysFieldArr['all'] = 'all' . $dayTime;

            $mDate = null;
            foreach ($nArr as $priority => $nArr2) {
                foreach ($nArr2 as $msgObj) {
                    $pMsgHash = md5($msgObj->msg . '|' . $msgObj->url . '|' . $msgObj->priority . '|' . $msgObj->customUrl);
                    foreach ((array)$uArr[$userId] as $brid => $uRec) {
                        $isGroup = ($uRec->groupNotify != 'no') ? true : false;
                        $isForceNotify = (($uRec->forceNotify == 'yes') || ($uRec->forceNotify == 'yesAll')) ? true : false;

                        $mField = $isForceNotify ? 'modifiedOn' : 'activatedOn';

                        setIfNot($mDate, $msgObj->{$mField});

                        if (strtotime($msgObj->{$mField}) > strtotime($mDate)) {
                            $mDate = $msgObj->{$mField};
                        }

                        // Проверяваме дали преди това има изпратено известие
                        $showUrlHash = md5($msgObj->url . '|' . $userId . '|' . $brid);
                        if ($prevMsgHash = core_Permanent::get('pwa_' . $showUrlHash)) {
                            // Ако има промяна в съобщението и настройката за принудително изпращане на известия е включена, подновяваме известието
                            $continue = true;
                            if ($isForceNotify) {
                                if ($uRec->forceNotify == 'yesAll') {
                                    $pMsgHash = md5($pMsgHash . '|' . $msgObj->lastTime . '|' . $msgObj->modifiedOn);
                                }

                                if ($prevMsgHash != $pMsgHash) {
                                    $continue = false;
                                }
                            }

                            if ($continue) {
//                            self::logDebug("Прескочено изпращане на PUSH известие поради дублиране на URL - '{$msgObj->url}'", $uRec->id, 7);

                                if (!$isGroup) {

                                    continue;
                                }
                            }
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
                            } elseif ($fType == 'share') {
                                if (strpos($msgLower, '|сподели|') === false) {

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

                        $tag = 'ntf' . $msgObj->id;

                        if ($isGroup) {
                            $tag = 'ntfGroup';
                            $pMsgHash = $mDate;
                        }

                        $bt = $tag . '|' . $brid;

                        if (!empty($allNotifyArr[$userId][$bt]['msg'])) {
                            $msg = $allNotifyArr[$userId][$bt]['msg'] . "\n" . $msg;
                            $urlArr = array('Portal', 'Show', '#' => 'notificationsPortal');
                        }

                        $allNotifyArr[$userId][$bt] = array('msgTitle' => $msgTitle, 'msg' => $msg, 'urlArr' => $urlArr,
                            'brid' => $brid, 'tag' => $tag, 'showUrlHash' => $showUrlHash, 'pMsgHash' => $pMsgHash,
                            'uRec' => $uRec, 'isGroup' => $isGroup);
                    }
                }
            }
        }

        foreach ($allNotifyArr as $userId => $tArr) {
            foreach ($tArr as $uNotifyArr) {
                if ($uNotifyArr['isGroup']) {
                    $prevMsgHash = core_Permanent::get('pwa_' . $uNotifyArr['showUrlHash']);

                    if ($prevMsgHash && (strtotime($prevMsgHash) >= strtotime($uNotifyArr['pMsgHash']))) {

                        continue;
                    }
                }

                // Изпращаме известието и записваме в лога съответното действие
                $isSendArr = $this->sendAlert($userId, $uNotifyArr['msgTitle'], $uNotifyArr['msg'], $uNotifyArr['urlArr'],
                    $uNotifyArr['tag'], null, null, $uNotifyArr['brid']);

                $lifetime = 24 * 60;
                foreach ($isSendArr as $iVal) {
                    $resStatusMsg = 'Неуспешно';
                    $lifetime = 2 * 60; // 2 часа за повторно изпращане, ако има грешка
                    if ($iVal->isSuccess) {
                        $resStatusMsg = 'Успешно';
                        $lifetime = 24 * 60; // 24 часа за повторно изпращане, ако няма грешка
                    }

//                    self::logDebug("{$resStatusMsg} изпращане на известие - '{$msgTitle}': '{$msg}'", $uRec->id, 7);
                }

                core_Permanent::set('pwa_' . $uNotifyArr['showUrlHash'], $uNotifyArr['pMsgHash'], $lifetime);
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

        expect($hash, 'Не е зададен хеш на линка');
        expect($url, 'Не е зададен линк');

        core_Permanent::remove('pwa_' . $hash);

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
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'edit') && $rec) {
            if ($rec->userId == $userId) {
                // canEdit=powerUser е минималната роля и не се
                // понижава до user за собствения запис.
                if (!haveRole('powerUser', $userId)) {
                    $requiredRoles = 'no_one';
                }
            } elseif (!haveRole('admin', $userId)) {
                $requiredRoles = 'no_one';
            }

            return;
        }

        if (($action == 'delete') && $rec && ($rec->userId != $userId)) {
            if (!haveRole('admin', $userId)) {
                $requiredRoles = 'no_one';
            }
        }
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
