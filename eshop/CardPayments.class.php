<?php


/**
 * Опити за плащане с карта през онлайн шлюз - общата част за всички картови драйвери
 *
 *
 * @category  bgerp
 * @package   eshop
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class eshop_CardPayments extends core_Manager
{
    /**
     * Старо име на класа
     */
    public $oldClassName = 'cond_CardPayments';


    /**
     * Заглавие
     */
    public $title = 'Плащания с карта';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Плащане с карта';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_Sorting, plg_SelectPeriod, plg_Search, eshop_Wrapper';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, createdOn, domainId, paymentId, initiatorId=Поръчка, orderNum, amount, state, notifiedOn, transactionRef, approvalCode, cardNumber, message';


    /**
     * Полета за търсене
     */
    public $searchFields = 'orderNum, gatewayRef, transactionRef, approvalCode';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'eshop,ceo,admin';


    /**
     * Кой може да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да редактира?
     */
    public $canEdit = 'no_one';


    /**
     * Кой може да изтрива?
     */
    public $canDelete = 'no_one';


    /**
     * Кой може да започне плащане - купувачът е анонимен
     */
    public $canPay = 'every_one';


    /**
     * Кой може да види резултата от плащането - купувачът по защитеното ид
     */
    public $canResult = 'every_one';


    /**
     * Кой може да изпраща известия - сървърът на шлюза, проверката е по подписа
     */
    public $canNotify = 'every_one';


    /**
     * Кой може да повтори отразяването на платеното
     */
    public $canRetry = 'eshop,ceo,admin';


    /**
     * Колко пъти общо се опитва отразяването на платеното, преди да се известят отговорните
     */
    const MAX_NOTIFY_TRIES = 5;


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('paymentId', 'key(mvc=cond_PaymentMethods,select=name)', 'caption=Метод');
        $this->FLD('initiatorClassId', 'class(interface=eshop_InitiatorPaymentIntf)', 'caption=Инициатор->Клас');
        $this->FLD('initiatorId', 'int', 'caption=Инициатор->Ид');
        $this->FLD('domainId', 'key(mvc=cms_Domains,select=titleExt)', 'caption=Домейн');
        $this->FLD('operation', 'enum(payment=Плащане,refund=Отмяна)', 'caption=Операция,notNull,value=payment');
        $this->FLD('originId', 'key(mvc=eshop_CardPayments)', 'caption=Към плащане');
        $this->FLD('orderNum', 'varchar(32)', 'caption=Номер в шлюза');
        $this->FLD('nonce', 'varchar(64)', 'caption=Nonce,column=none');
        $this->FLD('gatewayRef', 'varchar(128)', 'caption=Сесия в шлюза,column=none');
        $this->FLD('baseAmount', 'double(decimals=2)', 'caption=Сума в основна валута');
        $this->FLD('amount', 'double(decimals=2)', 'caption=Сума');
        $this->FLD('currencyCode', 'varchar(3)', 'caption=Валута');
        $this->FLD('state', 'enum(pending=Изпратено,paid=Платено,declined=Отказано,error=Грешка)', 'caption=Състояние,notNull,value=pending');
        $this->FLD('gatewayStatus', 'varchar(32)', 'caption=Статус в шлюза');
        $this->FLD('transactionRef', 'varchar(64)', 'caption=Трансакция');
        $this->FLD('approvalCode', 'varchar(16)', 'caption=Код за одобрение');
        $this->FLD('cardNumber', 'varchar(32)', 'caption=Карта');
        $this->FLD('cardholderInfo', 'varchar(128)', 'caption=Съобщение от банката');
        $this->FLD('posId', 'varchar(32)', 'caption=Терминал');
        $this->FLD('merchantId', 'varchar(32)', 'caption=Merchant ID');
        $this->FLD('paidOn', 'datetime', 'caption=Платено на');
        $this->FLD('notifiedOn', 'datetime', 'caption=Отразено на');
        $this->FLD('notifyTries', 'int', 'caption=Опити за отразяване,notNull,value=0');
        $this->FLD('message', 'varchar', 'caption=Съобщение');
        $this->FLD('config', 'blob(serialize, compress)', 'input=none,column=none,single=none');
        $this->FLD('okUrl', 'varchar(1024)', 'input=none,column=none,single=none');
        $this->FLD('cancelUrl', 'varchar(1024)', 'input=none,column=none,single=none');
        $this->FLD('response', 'blob(serialize, compress)', 'caption=Отговор на шлюза,column=none');

        $this->setDbIndex('paymentId,orderNum,operation');
        $this->setDbIndex('paymentId,gatewayRef');
        $this->setDbIndex('initiatorClassId,initiatorId');
    }


    /**
     * Урл на бутона за плащане - опитът и сумата се определят чак при натискането му
     *
     * @param int    $paymentId      - начин на плащане
     * @param array  $okUrl          - урл при успешно плащане
     * @param array  $cancelUrl      - урл при отказ
     * @param mixed  $initiatorClass - класа инициатор
     * @param int    $initiatorId    - ид на инициатора
     *
     * @return string
     */
    public static function getPayUrl($paymentId, $okUrl, $cancelUrl, $initiatorClass, $initiatorId)
    {
        $protected = 'paymentId,initiatorClassId,initiatorId,okUrl,cancelUrl';
        Request::setProtected($protected);
        $url = toUrl(array(get_called_class(), 'pay',
            'paymentId' => $paymentId,
            'initiatorClassId' => cls::get($initiatorClass)->getClassId(),
            'initiatorId' => $initiatorId,
            'okUrl' => toUrl($okUrl, 'absolute'),
            'cancelUrl' => toUrl($cancelUrl, 'absolute'),
        ), 'absolute');
        Request::removeProtected($protected);

        return $url;
    }


    /**
     * Нов опит за плащане и автоматично изпращане на купувача към шлюза
     */
    public function act_Pay()
    {
        $this->requireRightFor('pay');
        Request::setProtected('paymentId,initiatorClassId,initiatorId,okUrl,cancelUrl');
        expect($paymentId = Request::get('paymentId', 'int'));
        expect($initiatorClassId = Request::get('initiatorClassId', 'int'));
        expect($initiatorId = Request::get('initiatorId', 'int'));
        $okUrl = Request::get('okUrl', 'varchar');
        $cancelUrl = Request::get('cancelUrl', 'varchar');
        Request::removeProtected('paymentId,initiatorClassId,initiatorId,okUrl,cancelUrl');

        $Driver = self::getDriver($paymentId);
        expect($Driver, 'Методът на плащане не е картов');

        // Платеното, но неотразено не се плаща втори път - купувачът вижда, че плащането е получено
        $paidRec = self::fetchUnreflected($initiatorClassId, $initiatorId);
        if (is_object($paidRec)) {

            return new Redirect(array($this, 'result', $paidRec->id));
        }

        // Сумата е тази на поръчката в момента, а не при показването на бутона
        $baseAmount = cls::getInterface('eshop_InitiatorPaymentIntf', $initiatorClassId)->getAmountToPay($initiatorId, $paymentId);
        expect(isset($baseAmount) && $baseAmount > 0, 'Поръчката не очаква плащане с карта');

        $methodRec = cond_PaymentMethods::fetch($paymentId);
        list($amount, $currencyCode) = $Driver->getGatewayAmount($methodRec, $baseAmount);

        $rec = (object) array('paymentId' => $paymentId, 'domainId' => cms_Domains::getPublicDomain('id'), 'initiatorClassId' => $initiatorClassId, 'initiatorId' => $initiatorId, 'baseAmount' => $baseAmount,
            'amount' => $amount, 'currencyCode' => $currencyCode, 'state' => 'pending', 'okUrl' => $okUrl, 'cancelUrl' => $cancelUrl,
            'config' => $Driver->getConfig($methodRec));
        $this->save($rec);

        // Шлюзът съпоставя отговора по терминал, номер и nonce - номерът може да се повтори след 24 часа
        $rec->orderNum = $Driver->getOrderNum($rec);
        $rec->nonce = $Driver->getNonce($rec);
        $this->save($rec, 'orderNum,nonce');

        $request = $Driver->getGatewayRequest($rec, $methodRec);

        // Шлюзовете с API (Stripe, PayPal) създават сесия, по която после се познава отговорът
        if (!empty($request['gatewayRef'])) {
            $rec->gatewayRef = $request['gatewayRef'];
            $this->save($rec, 'gatewayRef');
        }
        $this->logInfo('Изпращане към шлюза за плащане с карта', $rec->id);
        Mode::set('wrapper', 'cms_page_External');

        // Вградена форма на шлюза на нашата страница
        if (isset($request['html'])) {

            return $request['html'];
        }

        if (strtoupper($request['method'] ?? 'POST') == 'GET') {

            return new Redirect($request['url']);
        }

        $tpl = new ET(tr("|*<div style='margin:2em;text-align:center'><form method='POST' action='[#url#]' id='cardPaymentForm'>[#fields#]<p>|Пренасочване към страницата за плащане с карта|*...</p>[#btn#]</form></div>"));
        $tpl->replace($request['url'], 'url');
        $tpl->replace(ht::createHidden($request['fields']), 'fields');
        $tpl->replace(ht::createSbBtn('Към плащането'), 'btn');
        jquery_Jquery::run($tpl, "$('#cardPaymentForm').submit();");

        return $tpl;
    }


    /**
     * Връщане на купувача от шлюза (BACKREF, success_url) - през браузъра му, без сесия
     */
    public function act_Response()
    {
        Request::setProtected('pm,cp');
        $paymentId = Request::get('pm', 'int');
        $id = Request::get('cp', 'int');
        Request::removeProtected('pm,cp');

        // На общия адрес (/A/CR) методът не се подава - познава се по подписания отговор
        if (empty($paymentId)) {
            list($paymentId, $result) = self::readFixedResponse();
        } else {
            $Driver = self::getDriver($paymentId);
            expect($Driver, 'Методът на плащане не е картов');

            $rec = !empty($id) ? $this->fetch($id) : null;
            expect(!is_object($rec) || $rec->paymentId == $paymentId);

            $result = $Driver->readResponse(cond_PaymentMethods::fetch($paymentId), $rec);
        }
        if (!is_array($result)) {
            $this->logWarning('Отговор от шлюза с невалиден подпис');
            expect(false, 'Невалиден отговор от шлюза');
        }

        $rec = self::processResult($paymentId, $result);
        expect($rec, 'Непознато плащане');

        // Резултатът е на отделен адрес, за да не се изпраща отговорът повторно при презареждане
        return new Redirect(array($this, 'result', $rec->id));
    }


    /**
     * Отговор на общия адрес - методът е този, чийто драйвер приеме подписа и при който е опитът
     *
     * @return array [ид на метода, нормализиран отговор] или [null, null]
     */
    protected static function readFixedResponse()
    {
        $query = cond_PaymentMethods::getQuery();
        $query->where('#onlinePaymentDriver IS NOT NULL');
        $query->show('id');
        while ($mRec = $query->fetch()) {
            $Driver = self::getDriver($mRec->id);
            if (!$Driver || !$Driver->hasFixedResponseUrl()) continue;

            $result = $Driver->readResponse(cond_PaymentMethods::fetch($mRec->id));
            if (is_array($result) && self::findAttempt($mRec->id, $result)) {

                return array($mRec->id, $result);
            }
        }

        return array(null, null);
    }


    /**
     * Известие от сървъра на шлюза (webhook) - отговорът е само HTTP кодът
     */
    public function act_Notify()
    {
        $this->requireRightFor('notify');
        Request::setProtected('pm');
        $paymentId = Request::get('pm', 'int');
        Request::removeProtected('pm');

        $Driver = !empty($paymentId) ? self::getDriver($paymentId) : null;
        $result = is_object($Driver) ? $Driver->readNotification(cond_PaymentMethods::fetch($paymentId)) : null;
        if ($result === null) {
            $this->logWarning('Известие от шлюза с невалиден подпис');
        }

        // Само непознатото известие се връща с грешка - шлюзът ще го изпрати повторно
        $code = 200;
        if (is_array($result)) {
            $code = self::processResult($paymentId, $result) ? 200 : 503;
        } elseif ($result === null) {
            $code = 400;
        }

        http_response_code($code);
        echo ($code == 200) ? 'OK' : 'ERROR';
        core_App::shutdown();
    }


    /**
     * Потвърждение за купувача след успешно плащане
     */
    public function act_Result()
    {
        $this->requireRightFor('result');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        expect($rec->operation == 'payment');

        $tpl = getTplFromFile('cond/tpl/CardPaymentResult.shtml');
        $tpl->placeObject($this->recToVerbal($rec, 'amount,currencyCode,orderNum,transactionRef,approvalCode,cardNumber,cardholderInfo,paidOn'));
        if ($rec->state == 'declined') {
            $tpl->replace('&#10006;', 'ICON');
            $tpl->replace('cpr-fail', 'CLASS');
            $tpl->replace(tr('Плащането е отказано'), 'TITLE');
            $tpl->replace(ht::createBtn('Обратно към поръчката', $rec->cancelUrl, false, false, 'class=order-btn eshop-btn'), 'BTN');
        } elseif ($rec->state == 'pending') {

            // Шлюзът още не е дал окончателен отговор - може да завърши успешно и по-късно
            $tpl->replace('&#8987;', 'ICON');
            $tpl->replace('cpr-wait', 'CLASS');
            $tpl->replace(tr('Плащането се обработва'), 'TITLE');
            $tpl->replace(tr('Ще получите имейл, когато плащането приключи - не плащайте повторно'), 'NOTE');
        } elseif ($rec->state == 'error') {
            $tpl->replace('!', 'ICON');
            $tpl->replace('cpr-wait', 'CLASS');
            $tpl->replace(tr('Плащането не е завършено автоматично'), 'TITLE');
            $tpl->replace(tr('Ако сумата е изтеглена от картата Ви, ще се свържем с Вас - не плащайте повторно'), 'NOTE');
        } elseif (!empty($rec->notifiedOn)) {
            $tpl->replace('&#10004;', 'ICON');
            $tpl->replace('cpr-ok', 'CLASS');
            $tpl->replace(tr('Плащането е успешно'), 'TITLE');
            $tpl->replace(ht::createBtn('Продължи', $rec->okUrl, false, false, 'class=order-btn eshop-btn'), 'BTN');
        } else {

            // Парите са взети, но поръчката още не е завършена - купувачът не бива да плаща отново
            $tpl->replace('&#8987;', 'ICON');
            $tpl->replace('cpr-wait', 'CLASS');
            $tpl->replace(tr('Плащането е получено'), 'TITLE');
            $tpl->replace(tr('Поръчката ще бъде потвърдена допълнително - не плащайте повторно'), 'NOTE');
        }

        // Пакетите добавят свои връзки към платеното (напр. документа по Н-18)
        $this->invoke('AfterRenderResult', array(&$tpl, $rec));
        $tpl->push('cond/tpl/CardPaymentResult.css', 'CSS');
        Mode::set('wrapper', 'cms_page_External');

        return $tpl;
    }


    /**
     * Повторен опит за отразяване на платеното, което не е стигнало до поръчката
     */
    public function act_Retry()
    {
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('retry', $rec);

        $ok = self::retryNotify($rec->id);
        if (!isset($ok)) {

            return new Redirect(array($this, 'list'), 'Плащането се обработва в момента|*!', 'warning');
        }

        if ($ok) {

            return new Redirect(array($this, 'list'), 'Платеното е отразено');
        }

        return new Redirect(array($this, 'list'), 'Платеното пак не е отразено - причината е в лога|*!', 'error');
    }


    /**
     * Повторно отразяване на платеното в поръчката - от бутона и от cron-а
     *
     * @param int           $id  - опитът за плащане
     * @param stdClass|null $rec - опитът след опита за отразяване
     *
     * @return bool|null - отразено ли е; null, ако опитът се обработва в момента
     */
    public static function retryNotify($id, &$rec = null)
    {
        $lockKey = self::getLockKey($id);
        if (!core_Locks::obtain($lockKey, 120, 20, 15)) {

            return null;
        }

        try {
            $rec = self::fetch($id, '*', false);
            $ok = (empty($rec->notifiedOn) && $rec->state == 'paid') ? self::notifyInitiator($rec) : true;
        } finally {
            core_Locks::release($lockKey);
        }

        // Известието е за всички неотразени - маха се, когато не остане нито едно
        if ($ok && !self::count("#state = 'paid' AND #notifiedOn IS NULL AND #operation = 'payment'")) {
            bgerp_Notifications::clear(self::getUnreflectedListUrl(), '*');
        }

        return $ok;
    }


    /**
     * Платеният опит за поръчката, който още не е отразен в нея
     *
     * @param mixed $initiatorClass - класът инициатор (име или ид)
     * @param int   $initiatorId    - ид на инициатора
     *
     * @return stdClass|false
     */
    public static function fetchUnreflected($initiatorClass, $initiatorId)
    {
        $initiatorClassId = core_Classes::getId($initiatorClass);

        return self::fetch(array("#initiatorClassId = [#1#] AND #initiatorId = [#2#] AND #operation = 'payment' AND #state = 'paid' AND #notifiedOn IS NULL", (int) $initiatorClassId, (int) $initiatorId));
    }


    /**
     * Списъкът с платеното и неотразеното - към него води известието
     *
     * @return array
     */
    protected static function getUnreflectedListUrl()
    {
        return array('eshop_CardPayments', 'list', 'fState' => 'notNotified');
    }


    /**
     * Отразява резултата от шлюза - общо за отговора през браузъра и за проверката на статус
     *
     * @param int   $paymentId - начин на плащане
     * @param array $result    - нормализиран отговор (@see cond_ProtoCardPaymentDriver::parseResponse)
     *
     * @return stdClass|null - опитът за плащане или null, ако не е намерен
     */
    public static function processResult($paymentId, $result)
    {
        $id = self::findAttempt($paymentId, $result);
        if (empty($id)) {
            self::logWarning('Отговор от шлюза за непознато плащане ' . ($result['gatewayRef'] ?? $result['orderNum'] ?? ''));

            return null;
        }

        // Връщането, известието и проверката на статус могат да дойдат едновременно
        $lockKey = self::getLockKey($id);
        if (!core_Locks::obtain($lockKey, 120, 20, 15)) {
            self::logWarning('Отговор от шлюза при заключено плащане', $id);

            return null;
        }

        try {
            $rec = self::fetch($id, '*', false);

            // Повторното известие не плаща втори път, но довършва неотразеното
            if ($rec->state == 'paid') {
                self::logInfo('Повторен отговор от шлюза', $rec->id);
                if (empty($rec->notifiedOn) && $rec->operation == 'payment') {
                    self::notifyInitiator($rec);
                }

                return $rec;
            }

            // Окончателният резултат не се променя от по-късен отговор
            if ($rec->state != 'pending') {
                self::logInfo('Отговор от шлюза за приключен опит', $rec->id);

                return $rec;
            }

            $rec->response = $result['raw'] ?? null;
            $rec->message = $result['message'] ?? null;
            $rec->gatewayStatus = $result['gatewayStatus'] ?? null;
            foreach (array('transactionRef', 'approvalCode', 'cardNumber', 'cardholderInfo', 'posId') as $field) {
                $rec->{$field} = $result[$field] ?? null;
            }

            // Търговецът не е в подписания отговор - взема се от изпратеното
            $rec->merchantId = self::getConfig($rec)['merchantId'] ?? null;

            $status = $result['status'] ?? null;
            if ($status == 'approved') {
                if ($error = self::getMismatch($rec, $result)) {

                    // Парите са взети, но поръчката не се маркира като платена - нужна е ръчна намеса (връщане)
                    $rec->state = 'error';
                    $rec->message = $error;
                } else {
                    $rec->state = 'paid';
                    $rec->paidOn = $result['paidOn'] ?? dt::now();
                }
            } elseif ($status == 'declined') {
                $rec->state = 'declined';
            }

            // При междинен статус или техническа грешка на шлюза опитът остава отворен за следващ отговор
            self::save($rec);
            self::logWrite("Плащане с карта - {$rec->state}" . (($rec->state == 'pending') ? " ({$status})" : ''), $rec->id);
            if ($rec->state == 'error') {
                self::logErr("Одобрено плащане не е прието: {$rec->message}", $rec->id);
            }

            if ($rec->state == 'paid' && $rec->operation == 'payment') {
                self::notifyInitiator($rec);
            }
        } finally {
            core_Locks::release($lockKey);
        }

        return $rec;
    }


    /**
     * Опитът, за който е отговорът
     *
     * @param int   $paymentId - начин на плащане
     * @param array $result    - нормализиран отговор
     *
     * @return int|null
     */
    protected static function findAttempt($paymentId, $result)
    {
        $operation = $result['operation'] ?? 'payment';
        $query = self::getQuery();
        $query->where(array("#paymentId = [#1#] AND #operation = '[#2#]'", $paymentId, $operation));
        $query->show('id');

        // Ид-то идва само от потвърдения от шлюза отговор, сесията - от API-то му, а номерът е за шлюзовете с подписана форма
        if (!empty($result['id'])) {
            $query->where(array('#id = [#1#]', (int) $result['id']));
        } elseif (!empty($result['gatewayRef'])) {
            $query->where(array("#gatewayRef = '[#1#]'", $result['gatewayRef']));
        } elseif (isset($result['orderNum']) && strlen($result['orderNum'])) {
            $query->where(array("#orderNum = '[#1#]'", $result['orderNum']));

            // Номерът е уникален само за 24 часа - без nonce се търси само сред последните опити
            if (!empty($result['nonce'])) {
                $query->where(array("#nonce = '[#1#]'", $result['nonce']));
            } else {
                $query->where(array("#createdOn >= '[#1#]'", dt::addSecs(-2 * 86400)));
            }
        } else {

            return null;
        }
        $query->orderBy('id', 'DESC');

        return $query->fetch()->id ?? null;
    }


    /**
     * Проверка на статуса направо в шлюза - за опитите, чийто отговор през браузъра се е загубил
     *
     * @param stdClass $rec - опитът
     *
     * @return stdClass|null - опитът след проверката или null, ако драйверът не поддържа проверка
     */
    public static function checkStatus($rec)
    {
        $Driver = self::getDriver($rec->paymentId);
        $methodRec = cond_PaymentMethods::fetch($rec->paymentId);
        $data = is_object($Driver) ? $Driver->checkStatus($rec, $methodRec) : null;
        if (!is_array($data)) {

            return null;
        }

        $result = $Driver->parseResponse($methodRec, $data);
        if (!is_array($result)) {
            self::logWarning('Отговор от проверката на статус с невалиден подпис', $rec->id);

            return null;
        }

        // Шлюзът е питан за този опит - ако отговорът не го посочва сам
        if (empty($result['gatewayRef']) && !isset($result['orderNum'])) {
            $result['id'] = $result['id'] ?? $rec->id;
        }

        return self::processResult($rec->paymentId, $result);
    }


    /**
     * Несъответствие между одобрения отговор и изпратения опит
     *
     * @param stdClass $rec    - опитът
     * @param array    $result - нормализираният отговор
     *
     * @return string|null - причината или null, ако всичко съвпада
     */
    protected static function getMismatch($rec, $result)
    {
        if (!isset($result['amount']) || !is_numeric($result['amount']) || abs($result['amount'] - $rec->amount) > 0.001) {

            return 'Платената сума се различава от изпратената';
        }

        if (($result['currencyCode'] ?? null) !== $rec->currencyCode) {

            return 'Валутата на плащането се различава от изпратената';
        }

        // Отговорът трябва да е от терминала, към който е изпратено плащането
        $terminalId = self::getConfig($rec)['terminalId'] ?? null;
        if (!empty($terminalId) && ($result['posId'] ?? null) !== $terminalId) {

            return 'Отговорът е от друг терминал';
        }

        // Поръчката не бива да е променена, докато купувачът е бил в шлюза
        $current = cls::getInterface('eshop_InitiatorPaymentIntf', $rec->initiatorClassId)->getAmountToPay($rec->initiatorId, $rec->paymentId);
        if (!isset($current) || abs($current - $rec->baseAmount) > 0.001) {

            return 'Поръчката е променена или платена, докато е течало плащането';
        }

        return null;
    }


    /**
     * Предава платеното на инициатора - при неуспех плащането остава неотразено за повторен опит
     *
     * @param stdClass $rec - опитът за плащане
     *
     * @return bool
     */
    protected static function notifyInitiator($rec)
    {
        // Поръчката е платена с друг опит - второто плащане е за връщане, не за отразяване
        $otherCond = array("#initiatorClassId = [#1#] AND #initiatorId = [#2#] AND #operation = 'payment' AND #state = 'paid' AND #notifiedOn IS NOT NULL AND #id != [#3#]", (int) $rec->initiatorClassId, (int) $rec->initiatorId, (int) $rec->id);
        if (self::count($otherCond)) {
            $rec->state = 'error';
            $rec->message = 'Поръчката вече е платена с друго плащане';
            self::save($rec, 'state,message');
            self::logErr("Одобрено плащане не е отразено: {$rec->message}", $rec->id);

            return false;
        }

        // Неплатената още поръчка не бива да е променена след плащането (напр. преди повторен опит)
        $current = cls::getInterface('eshop_InitiatorPaymentIntf', $rec->initiatorClassId)->getAmountToPay($rec->initiatorId, $rec->paymentId);
        if (isset($current) && abs($current - $rec->baseAmount) > 0.001) {
            $rec->state = 'error';
            $rec->message = 'Поръчката е променена, след като е платена';
            self::save($rec, 'state,message');
            self::logErr("Одобрено плащане не е отразено: {$rec->message}", $rec->id);

            return false;
        }

        $config = self::getConfig($rec);
        $paymentData = array('transactionRef' => $rec->transactionRef, 'posId' => $rec->posId, 'merchantId' => $rec->merchantId,
            'paidOn' => $rec->paidOn, 'amount' => $rec->amount, 'currencyCode' => $rec->currencyCode, 'cardNumber' => $rec->cardNumber ?? null,
            'procId' => $config['procId'] ?? null);
        $reason = "Плащане с карта {$rec->orderNum}" . (!empty($rec->transactionRef) ? ", трансакция {$rec->transactionRef}" : '');

        $rec->notifyTries = ($rec->notifyTries ?? 0) + 1;
        $ok = false;
        try {
            $Driver = self::getDriver($rec->paymentId);
            if (is_object($Driver)) {
                $Driver->beforeNotifyInitiator($rec);
            }
            $ok = (cls::get($rec->initiatorClassId)->receivePayment($rec->initiatorId, $reason, null, $rec->amount, $rec->currencyCode, $config['ownAccountId'] ?? null, null, $paymentData) === true);
        } catch (core_exception_Redirect $e) {
            throw $e;
        } catch (Throwable $e) {
            reportException($e);
            self::logErr('Грешка при отразяване на платеното с карта: ' . $e->getMessage(), $rec->id);
        }

        if ($ok) {
            $rec->notifiedOn = dt::now();
        } else {
            self::logErr('Платеното с карта не е отразено в поръчката', $rec->id);
        }
        self::save($rec, 'notifyTries,notifiedOn');

        return $ok;
    }


    /**
     * Настройките на метода към момента на изпращането - по-късна промяна на метода не ги засяга
     *
     * @param stdClass $rec - опитът
     *
     * @return array - ownAccountId, terminalId, merchantId, procId
     */
    protected static function getConfig($rec)
    {
        if (is_array($rec->config ?? null)) {

            return $rec->config;
        }

        // Опитите отпреди запазването на настройките
        $methodRec = cond_PaymentMethods::fetch($rec->paymentId);
        $Driver = self::getDriver($rec->paymentId);

        return is_object($Driver) ? $Driver->getConfig($methodRec) : array();
    }


    /**
     * Ключ за заключване на опита - общ за отговора, известието, проверката на статус и повторния опит
     *
     * @param int $id
     *
     * @return string
     */
    protected static function getLockKey($id)
    {
        return "cardPayment_{$id}";
    }


    /**
     * Проверка в шлюза на опитите без окончателен отговор - загубено връщане, изоставена или висяща сесия
     */
    public function cron_CheckPending()
    {
        $query = self::getQuery();
        $query->where(array("#state = 'pending' AND #createdOn <= '[#1#]' AND #createdOn >= '[#2#]'", dt::addSecs(-15 * 60), dt::addSecs(-3 * 86400)));
        $query->orderBy('id', 'ASC');
        $query->limit(100);
        while ($rec = $query->fetch()) {
            try {
                self::checkStatus($rec);
            } catch (core_exception_Redirect $e) {
                throw $e;
            } catch (Throwable $e) {
                reportException($e);
                self::logErr('Грешка при проверка на статуса в шлюза: ' . $e->getMessage(), $rec->id);
            }
        }

        // Платеното, но неотразено се опитва пак; минутата пази от отговора, който още се отразява
        $query = self::getQuery();
        $query->where(array("#state = 'paid' AND #operation = 'payment' AND #notifiedOn IS NULL AND #notifyTries < [#1#] AND #paidOn <= '[#2#]' AND #createdOn >= '[#3#]'", self::MAX_NOTIFY_TRIES, dt::addSecs(-60), dt::addSecs(-3 * 86400)));
        $query->orderBy('id', 'ASC');
        $query->limit(100);
        while ($rec = $query->fetch()) {
            try {
                $ok = self::retryNotify($rec->id, $rec);
                if ($ok === false && ($rec->notifyTries ?? 0) >= self::MAX_NOTIFY_TRIES) {
                    self::notifyResponsible($rec);
                }
            } catch (core_exception_Redirect $e) {
                throw $e;
            } catch (Throwable $e) {
                reportException($e);
                self::logErr('Грешка при повторно отразяване на платеното: ' . $e->getMessage(), $rec->id);
            }
        }
    }


    /**
     * Известие до тези, които могат да довършат платеното, но неотразено
     *
     * @param stdClass $rec - опитът за плащане
     */
    protected static function notifyResponsible($rec)
    {
        $users = core_Users::getByRole('ceo') + core_Users::getByRole('admin');
        foreach ($users as $userId) {
            bgerp_Notifications::add('|Плащане с карта не е отразено в поръчката|*', self::getUnreflectedListUrl(), $userId, 'warning');
        }
        self::logErr('Платеното с карта не е отразено след ' . self::MAX_NOTIFY_TRIES . ' опита', $rec->id);
    }


    /**
     * Повторен опит само за платеното, което не е стигнало до поръчката
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'retry' && isset($rec)) {
            if ($rec->state != 'paid' || !empty($rec->notifiedOn) || $rec->operation != 'payment') {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Картовият драйвер на метода на плащане
     *
     * @param int $paymentId
     *
     * @return cond_ProtoCardPaymentDriver|null
     */
    public static function getDriver($paymentId)
    {
        $driverId = cond_PaymentMethods::fetchField($paymentId, 'onlinePaymentDriver');
        if (empty($driverId) || !cls::load($driverId, true)) {

            return null;
        }

        $Driver = cls::get($driverId);

        return ($Driver instanceof cond_ProtoCardPaymentDriver) ? $Driver : null;
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (!empty($rec->paymentId)) {
            $row->paymentId = cond_PaymentMethods::getHyperlink($rec->paymentId, true);
        }

        if (!empty($rec->domainId)) {
            $row->domainId = cms_Domains::getHyperlink($rec->domainId, true);
        }

        if (isset($fields['-list']) && !empty($rec->currencyCode)) {
            $row->amount .= " <span class='cCode'>" . core_Type::escape($rec->currencyCode) . '</span>';
        }

        // Цветът на реда като при документите: платено, изпратено, отказано, грешка
        if (isset($fields['-list'])) {
            $stateClasses = array('paid' => 'active', 'pending' => 'pending', 'declined' => 'closed', 'error' => 'stopped');
            $row->ROW_ATTR['class'] = 'state-' . ($stateClasses[$rec->state ?? ''] ?? 'draft');
        }

        if (!empty($rec->initiatorClassId) && !empty($rec->initiatorId) && cls::load($rec->initiatorClassId, true)) {
            $Initiator = cls::get($rec->initiatorClassId);
            if ($Initiator instanceof core_Master) {
                $row->initiatorId = $Initiator->getHyperlink($rec->initiatorId, true);
            }
        }

        // Повторният опит е само в списъка, не и на страницата за купувача
        if (isset($fields['-list']) && $mvc->haveRightFor('retry', $rec)) {
            $row->state = ($row->state ?? '') . ' ' . ht::createLink('', array($mvc, 'retry', $rec->id, 'ret_url' => true), false, 'ef_icon=img/16/arrow_refresh.png,title=Повторен опит за отразяване на платеното в поръчката');
        }
    }


    /**
     * Подготовка на филтъра на листовия изглед
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $form = $data->listFilter;
        $form->layout = new ET(tr('|*' . getFileContent('acc/plg/tpl/FilterForm.shtml')));
        $form->FLD('from', 'date', 'caption=От,silent');
        $form->FLD('to', 'date', 'caption=До,silent');
        $form->FLD('fState', 'enum(all=Всички,pending=Изпратено,paid=Платено,notNotified=Платено и неотразено,declined=Отказано,error=Грешка)', 'caption=Състояние,silent');
        $showFields = 'search,selectPeriod,from,to,fState';

        // Само методите и домейните, по които има плащания
        $options = array('paymentId' => array(), 'domainId' => array());
        foreach (array_keys($options) as $field) {
            $oQuery = $mvc->getQuery();
            $oQuery->where("#{$field} IS NOT NULL");
            $oQuery->groupBy($field);
            $oQuery->show($field);
            while ($oRec = $oQuery->fetch()) {
                $options[$field][$oRec->{$field}] = ($field == 'paymentId') ? cond_PaymentMethods::getTitleById($oRec->{$field}, false) : cms_Domains::getTitleById($oRec->{$field}, false);
            }
        }
        $filterFields = array('paymentId' => array('fPayment', 'Метод'), 'domainId' => array('fDomain', 'Домейн'));
        foreach ($filterFields as $field => $f) {
            if (countR($options[$field]) > 1) {
                $form->FLD($f[0], 'int', "caption={$f[1]},placeholder=Всички,silent");
                $form->setOptions($f[0], array('' => '') + $options[$field]);
                $showFields .= ",{$f[0]}";
            }
        }

        $form->showFields = $showFields;
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $form->input(null, 'silent');
        $form->input();
        $form->setDefault('fState', 'all');

        $filter = $form->rec;
        if (!empty($filter->from)) {
            $data->query->where(array("#createdOn >= '[#1#]'", "{$filter->from} 00:00:00"));
        }
        if (!empty($filter->to)) {
            $data->query->where(array("#createdOn <= '[#1#]'", "{$filter->to} 23:59:59"));
        }
        if (!empty($filter->fState) && $filter->fState != 'all') {
            if ($filter->fState == 'notNotified') {
                $data->query->where("#state = 'paid' AND #notifiedOn IS NULL AND #operation = 'payment'");
                bgerp_Notifications::clear(self::getUnreflectedListUrl());
            } else {
                $data->query->where(array("#state = '[#1#]'", $filter->fState));
            }
        }
        foreach ($filterFields as $field => $f) {
            if (!empty($filter->{$f[0]}) && isset($options[$field][$filter->{$f[0]}])) {
                $data->query->where(array("#{$field} = [#1#]", $filter->{$f[0]}));
            }
        }
        $data->query->orderBy('id', 'DESC');
    }
}
