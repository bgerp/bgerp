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
     * @var string
     */
    public $interfaces = 'remote_SendMessageIntf';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2';


    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'no_one';


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
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('userId', 'user', 'caption=Потребител');
        $this->FLD('brid', 'varchar(8)', 'caption=Браузър');
        $this->FLD('publicKey', 'varchar', 'caption=Ключ'); //88
        $this->FLD('authToken', 'varchar', 'caption=Токен'); //24
        $this->FLD('domainId', 'key(mvc=cms_Domains, select=titleExt)', 'caption=Домейн');
        $this->FLD('contentEncoding', 'varchar', 'caption=Енкодинг');
        $this->FLD('endpoint', 'Url', 'caption=Точка');
        $this->FLD('data', 'blob(compress, serialize)', 'caption=Данни');

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
     * @param null|integer $domainId - id на домейн
     * @param null|string $brid - id на браузъра
     * @param bool $sound - звук
     * @param null|bool $vibration - вибрация
     *
     * @return array
     */
    public static function sendAlert($userId, $title, $text, $url = null, $tag = null, $icon = null, $image = null, $domainId = null, $brid = null, $sound = true, $vibration = null)
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

        while ($rec = $query->fetch()) {
            if (isset($rec->domainId)) {
                $dRec = cms_Domains::fetch($rec->domainId);
            } else {

                continue;
            }

            try {
                $auth = array(
                    'VAPID' => array(
                        'subject' => 'bgERP',
                        'publicKey' => $dRec->publicKey,
                        'privateKey' => $dRec->privateKey,
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

            $resArr[] = $webPush->sendOneNotification($subscription, json_encode($data));
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

        $action = Request::get('action');
        expect($action == 'subscribe' || $action == 'unsubscribe', $action);

        $publicKey = Request::get('publicKey');
        $authToken = Request::get('authToken');
        $endpoint = Request::get('endpoint');
        $contentEncoding = Request::get('contentEncoding');

        expect($publicKey && $authToken, $publicKey, $authToken);

        $cu = core_Users::getCurrent();
        $brid = log_Browsers::getBrid();

        $statusData = array();

        if ($action == 'unsubscribe') {
            $this->delete(array("#publicKey = '[#1#]' AND #authToken = '[#2#]'", $publicKey, $authToken));
            $this->delete(array("#brid = '[#1#]'", $brid));

            $statusData['text'] = tr('Премахване на Push абонамент за получаване на известия');
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

            $statusData['text'] = tr('Добавен е Push абонамент за получване на известия');
        }

        $statusData['type'] = 'notice';
        $statusData['timeOut'] = 700;
        $statusData['isSticky'] = 0;
        $statusData['stayTime'] = 15000;

        $statusObj = new stdClass();
        $statusObj->func = 'showToast';
        $statusObj->arg = $statusData;

        return array($statusObj);
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
                if ($s->isSuccess()) {
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

        bp($this->sendAlert(core_Users::getCurrent(), 'Тестово известие', 'Тестово известие: ' . rand(1, 1111), array('Portal', 'Show'), 'Test'));
    }
}
