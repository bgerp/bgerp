<?php




/**
 * Пращане на SMSи от smsapi
 *
 * @category  vendors
 * @package   mobio
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */

class smsapi_SMS extends core_Manager
{
    /**
     * Кой има право да чете?
     */
    public $canRead = 'no_one';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'no_one';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Интерфейсния клас за изпращане на SMS
     */
    public $interfaces = 'callcenter_SentSMSIntf';
    
    
    public $title = 'SMSAPI';
    
    
    /**
     * Интерфейсен метод за изпращане на SMS' и
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
        // Конфигурацията на модула
        $conf = core_Packs::getConfig('smsapi');
        
        // Масива, който ще връщаме
        $nRes = array();
        
        // Ако константата за УРЛ-то е зададена
        if ($conf->SMSAPI_URL != '' && $conf->SMSAPI_TOKEN != '') {
            $number = self::prepareNumberStr($number);
            
            $params = array(
                'to'            => $number,     //destination number
                'from'          => '1511',      //sendername made in https://portal.smsapi.bg/sms_settings/sendernames
                'message'       => $message,    //message content
                'format'        => 'json',
            );
            
            if (!empty($conf->SMSAPI_NOTIFY_URL)) {
                $params['notify_url'] = $conf->SMSAPI_NOTIFY_URL;
            }
            
            $c = curl_init();
            curl_setopt($c, CURLOPT_URL, $conf->SMSAPI_URL);
            curl_setopt($c, CURLOPT_POST, true);
            curl_setopt($c, CURLOPT_POSTFIELDS, $params);
            curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($c, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer {$conf->SMSAPI_TOKEN}"
            ));
            
            $res = curl_exec($c);

            $http_status = curl_getinfo($c, CURLINFO_HTTP_CODE);
            
            curl_close($c);
            
            // Ако има грешки
            if ($http_status != 200) {
                // Сетваме променливите
                $nRes['sendStatus'] = 'sendError';
                $nRes['msg'] = '|Не може да се изпрати';
                
                if (isDebug()) {
                    $nRes['msg'] .= '|*.<br>' . 'http_status: ' . $http_status;
                }
                
                self::logErr('Грешка при изпращане на SMS: ' . 'http_status: ' . $http_status);
            }
            
            $res = json_decode($res);
                        
            if (isset($res->error)) {
                // Сетваме грешките
                $nRes['sendStatus'] = 'sendError';
                $nRes['msg'] = "|{$res->message}";
                
                // Записваме в лога
                self::logAlert("{$res->message}");
            } else {
                // Сетваме променливите
                $nRes['sendStatus'] = $res->list[0]->status;
                $nRes['uid'] = $res->list[0]->id;
                $nRes['msg'] = '|Успешно изпратен SMS';
            }
        } else {
            // Сетваме грешки
            $nRes['sendStatus'] = 'sendError';
            $nRes['msg'] = "|Липсва token или URL";
            
            // Записваме в лога
            self::logAlert("Липсва token или URL");
        }
        
        return $nRes;
    }
    
    
    /**
     * Интерфейсен метод, който връща масив с настройките за услугата
     *
     * @return array $paramsArr
     *               enum $paramsArr['utf8'] - no|yes - Дали поддържа UTF-8
     *               integer $paramsArr['maxStrLen'] - Максималната дължина на стринга
     *               string $paramsArr['allowedUserNames'] - Масив с позволените имена за изпращач
     */
    public function getParams()
    {
        $conf = core_Packs::getConfig('smsapi');
        $paramsArr = array();
        $paramsArr['utf8'] = $conf->SMSAPI_SUPPORT_UTF8;
        $paramsArr['maxStrLen'] = $conf->SMSAPI_MAX_STRING_LEN;
        $paramsArr['allowedUserNames'] = arr::make($conf->SMSAPI_ALLOWED_USER_NAMES, true);
        
        return $paramsArr;
    }
    
    
    /**
     * Инрерфейсен метод
     * Подготвя номера на получателя
     *
     * @param string $number
     *
     * @return string
     */
    protected static function prepareNumberStr($number)
    {
        $number = drdata_PhoneType::getNumberStr($number, 0, '');
        
        return $number;
    }
    
    
    /**
     * Инрерфейсен метод
     * Връща статуса на съобщението от съоветната услуга
     *
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
     * Отбелязване на статуса на съобщенито
     * Извиква се от външната програма след промяна на статуса на SMS'а
     */
    public function act_Delivery()
    {
        // Вземаме променливите
        $uid = Request::get('MsgId', 'varchar');
        $number = Request::get('to', 'varchar');
        $code = Request::get('status_name', 'varchar');
        $time = Request::get('sent_at', 'varchar');
        $timestamp = null;
        // Определяне състоянието
        switch ($code) {
            case "SENT":
            case "ACCEPTED":
                $status = 'sended';
                break;
            case "DELIVERED":
                $status = 'received';
                break;
            case "QUEUE":
                $status = 'pending';
                break;
            case "UNDELIVERED":
                $status = 'receiveError';
                break;
            default: $status = 'sendError';
        }
        
        try {
            $classId = $this->getClassId();
            
            if ($time) {
                $timestamp = dt::mysql2timestamp($time);
            }
            
            // Обновяваме статуса на съобщението
            callcenter_SMS::update($classId, $uid, $status, $timestamp);
        } catch (core_exception_Expect $e) {
            reportException($e);
            self::logErr('Възникна грешка при обновяване на състоянието с msgid: ' . $uid . ' ' . $e->getMessage());
        }
        
        die('OK');
    }
}
