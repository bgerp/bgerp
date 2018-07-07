<?php


/**
 * Изпрещане на SMS с програмата SMSSync
 *
 * @category  vendors
 * @package   clickatell
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @see http://smssync.ushahidi.com/
 */
class smssync_SMS extends core_Manager
{
    
    
    /**
     * Кой има право да чете?
     */
    protected $canRead = 'no_one';
    
    
    /**
     * Кой има право да променя?
     */
    protected $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    protected $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    protected $canView = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    protected $canList = 'debug';
    
    
    /**
     * Кой има право да го изтрие?
     */
    protected $canDelete = 'no_one';
    
    
    /**
     * Интерфейсния клас за изпращане на SMS
     */
    public $interfaces = 'callcenter_SentSMSIntf';
    
    
    /**
     * Заглавие на модела
     */
    public $title = 'SMSSync';
    
    
    /**
     * Плъгини за зареждане
     *
     * var string|array
     */
    public $loadList = 'plg_Created';
    
    
    
    public function description()
    {
        $this->FLD('sender', 'varchar', 'caption=Изпращач');
        $this->FLD('number', 'drdata_PhoneType', 'caption=Получател');
        $this->FLD('message', 'blob', 'caption=Съобщение');
        $this->FLD('status', 'enum(pending=Чакащо, fetched=Извлечен, sended=Изпратен)', 'caption=Статус, input=none, hint=Статус на съобщението');
        $this->FLD('uid', 'varchar', 'caption=Хендлър, input=none');
    }
    
    
    /**
     * Интерфейсен метод за изпращане на SMS' и
     * @see callcenter_SentSMSIntf
     *
     * @param string $number  - Номера на получателя
     * @param string $message - Текста на съобщението
     * @param string $sender  - От кого се изпраща съобщението
     *
     * @return array $nRes - Масив с информация, дали е получено
     *               $res['sendStatus'] string - Статус на изпращането - received, sended, receiveError, sendError, pending
     *               $nRes['uid'] string - Уникалното id на съобщението
     *               $nRes['msg'] - Статуса
     */
    public function sendSMS($number, $message, $sender)
    {
        // Добавяме запис в модела
        $nRec = new stdClass();
        $nRec->sender = $sender;
        $nRec->number = $number;
        $nRec->message = $message;
        $nRec->status = 'pending';
        
        $i = 0;
        
        // Опитваме се да генерираме уникално id за SMS-a
        do {
            if (16 < $i++) {
                error('@Unable to generate random uid', $nRec);
            }
            
            $nRec->uid = self::getUid();
        } while (self::fetch("#uid = '{$nRec->uid}'"));
        
        // Резултата
        $nRes = array('uid' => $nRec->uid);
        if (self::save($nRec)) {
            $nRes['sendStatus'] = 'pending';
            $nRes['msg'] = '|Добавен е в списъка за изпращане';
        } else {
            $nRes['sendStatus'] = 'sendError';
            $nRes['msg'] = '|Грешка при добвяне в списъка';
        }
        
        return $nRes;
    }
    
    
    /**
     * Инрерфейсен метод
     * Връща статуса на съобщението от съоветната услуга
     * @see callcenter_SentSMSIntf
     *
     * @param string $uid
     *
     * @return
     */
    public function getStatus($uid)
    {
    }
    
    
    /**
     * Екшъна, който се вика от SMSSync.
     * Връща JSON данни за SMS-а за изпращане.
     * Може и да се вика при входящ SMS на телефона
     */
    public function act_Sync()
    {
        $secret = Request::get('secret');
        $task = Request::get('task');
        
        // Ако ключа не е коректен или IP-то не е в допустимите, не може да извлича записте
        if (!self::isAuthorizied($secret)) {
            
            return false;
        }
        
        $res = array();
        
        // Ако ще се извличат съобщенията
        if ($task) {
            
            // Вземаме записите
            $recs = self::getRecs();
            
            // Подготвяме записите
            $res = self::prepareSendRes($recs, $task, $secret);
            
            // Изтриваме записите
            self::deleteRecs($recs);
        } else {
            
            // Входящо съобщение в телефона
            
            $dataArr = array();
            $dataArr['secret'] = $secret; // Ключа
            $dataArr['from'] = Request::get('from'); // Изпращача на съобщението
            $dataArr['message'] = Request::get('message'); // Съобщението
            $dataArr['sentTimestamp'] = Request::get('sent_timestamp'); // Време на получаване
            $dataArr['sentTo'] = Request::get('sent_to'); // Получател на SMS (реално е устройството, което извика линка)
            $dataArr['messageId'] = Request::get('message_id'); // Уникален номер на съобщението
            $dataArr['deviceId'] = Request::get('device_id'); // Инфомация за устройството
            
            // Подготвяме резултата за входящото съобщение
            $res = self::prepareIncomingRes($dataArr);
        }
        
        // Връщаме резултата в JSON формат и спираме процеса
        core_App::outputJson($res);
    }
    
    
    /**
     * Изтирва записите
     *
     * @param array $recs
     */
    protected static function deleteRecs($recs)
    {
        $classId = self::getClassId();
        
        foreach ((array) $recs as $id => $rec) {
            
            // Обновяваме статуса на съобщението
            callcenter_SMS::update($classId, $rec->uid, 'sended');
            
            if (!$id) {
                continue;
            }
            
            // Изтриваме записа от този модел
            self::delete($id);
        }
    }
    
    
    /**
     * Връща всички записи, на които да се прати съобщение
     */
    protected static function getRecs_()
    {
        // Връща всички записи до достигане на лимита в настройките, които са със състояние чакащо
        $recs = array();
        $conf = core_Packs::getConfig('smssync');
        $query = self::getQuery();
        $query->where("#status = 'pending'");
        $query->limit($conf->SMSSYNC_SMS_LIMIT);
        $query->orderBy('id', 'ASC');
        while ($rec = $query->fetch()) {
            $recs[$rec->id] = $rec;
            
            // Обновяваме статуса за да не се извлече повторно
            $nRec = clone $rec;
            $nRec->status = 'fetched';
            self::save($nRec, 'status');
        }
        
        return $recs;
    }
    
    
    /**
     * Подготвя масива с данните за изходящите съобщения
     *
     * @param array  $recs
     * @param string $task
     * @param string $secret
     *
     * @return array
     */
    protected static function prepareSendRes($recs, $task, $secret)
    {
        $messages = self::prepareMessages($recs);
        $res = array(
                    'payload' => array(
                        'task' => $task,
                        'secret' => $secret,
                        'messages' => $messages
                    )
                );
                
        return $res;
    }
    
    
    /**
     * Подготвя масива с изходящите съобщения
     *
     * @param array $recs
     */
    protected static function prepareMessages_($recs)
    {
        $messagesArr = array();
        foreach ((array) $recs as $rec) {
            $messagesArr[] = array('to' => $rec->number, 'message' => $rec->message);
        }
        
        return $messagesArr;
    }
    
    
    /**
     * Подготвя масива с данните за входящите съобщения
     *
     * @param array $dataArr
     */
    protected static function prepareIncomingRes($dataArr)
    {
        $incomingMsg = self::prepareIncomingMessage($dataArr);
        $res = array(
            'payload' => $incomingMsg
        );
        
        return $res;
    }
    
    
    /**
     * Подготвя масива след получаване на входящо съобщение
     *
     * @param array $dataArr
     */
    protected static function prepareIncomingMessage_($dataArr)
    {
        $res = array(
                'success' => true,
                'error' => null
            );
            
        return $res;
    }
    
    
    /**
     * Проверява дали текущия потребител има достъп да изпраща/получава съобщения
     *
     * @param string $secret
     */
    protected static function isAuthorizied($secret)
    {
        // Вземам конфигурационните данни
        $conf = core_Packs::getConfig('smssync');
        
        // Ако не отговаря на посочения от нас
        if ($secret != $conf->SMSSYNC_SECRET_KEY) {
            
            // Записваме в лога
            self::logErr('Невалиден публичен ключ: ' . $secret);
            
            // Връщаме
            return false;
        }
        
        // Масив с разрешените IP' та
        $allowedIpArr = arr::make($conf->SMSSYNC_ALLOWED_IP_ADDRESS);
        
        // Ако е зададено
        if (count($allowedIpArr)) {
            
            // Вземаме IP' то на извикщия
            $realIpAdd = core_Users::getRealIpAddr();
            
            // Обхождаме масива с хостовете
            foreach ((array) $allowedIpArr as $allowedIp) {
                
                // Ако се съдържа в нашия списък
                if (stripos($realIpAdd, $allowedIp) === 0) {
                    
                    return true;
                }
            }
            
            // Записваме в лога
            self::logWarning('Не е позволен достъпа от това IP');
            
            return false;
        }
        
        // Ако проверките минат успешно
        return true;
    }
    
    
    /**
     * Връща уникално id
     *
     * @return string
     */
    protected static function getUid()
    {
        return $uid = str::getRand('aaaaaa') . '_smssync';
    }
    
    
    /**
     * Добавя филтър за изпратените SMS-и
     *
     * @param callcenter_SMS $mvc
     * @param object         $data
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->query->orderBy('status', 'ASC');
        $data->query->orderBy('id', 'DESC');
    }
    
    
    /**
     * Интерфейсен метод, който връща масив с настройките за услугата
     * @see callcenter_SentSMSIntf
     *
     * @return array $paramsArr
     *               enum $paramsArr['utf8'] - no|yes - Дали поддържа UTF-8
     *               integer $paramsArr['maxStrLen'] - Максималната дължина на стринга
     *               string $paramsArr['allowedUserNames'] - Масив с позволените имена за изпращач
     */
    public function getParams()
    {
        $conf = core_Packs::getConfig('smssync');
        $paramsArr = array();
        $paramsArr['utf8'] = $conf->SMSSYNC_SUPPORT_UTF8;
        $paramsArr['maxStrLen'] = $conf->SMSSYNC_MAX_STRING_LEN;
        $paramsArr['allowedUserNames'] = arr::make($conf->SMSSYNC_ALLOWED_USER_NAMES, true);
        
        return $paramsArr;
    }
}
