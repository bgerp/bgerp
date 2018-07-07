<?php


/**
 * Пращане на SMS'и от нетфинити
 *
 * @category  vendors
 * @package   netfinity
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class netfinity_SMS extends core_Manager
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
    
    
    
    public $title = 'Нетфинити';
    
    
    
    public $protectId = false;
    
    
    /**
     * Интерфейсен метод за изпращане на SMS' и
     *
     * @param string $number  - Номера на получателя
     * @param string $message - Текста на съобщението
     * @param string $sender  - От кого се изпраща съобщението
     *
     * @return array - Масив с информация, дали е получено
     *               $res['sendStatus'] string - Статус на изпращането - received, sended, receiveError, sendError, pending
     *               $nRes['uid'] string - Уникалното id на съобщението
     *               $nRes['msg'] - Статуса
     */
    public function sendSMS($number, $message, $sender)
    {
        // Масива, който ще връщаме
        $nRes = array();
        
        $url = trim(netfinity_Setup::get('URL'));
        
        if ($url) {
            $number = self::prepareNumberStr($number);
            
            $sender = trim($sender);
            
            if ($sender) {
                $message = "<{$sender}> {$message}";
            }
            
            // Вземаме шаблона
            $tpl = new ET($url);
            
            $msgId = dt::mysql2timestamp();
            $msgId = substr($msgId, 3, 7);
            $msgId = '1' . str::getRand('##') . $msgId;
            
            // Заместваме данните
            $tpl->placeArray(array('apikey' => urlencode(netfinity_Setup::get('APIKEY')), 'number' => urlencode($number), 'message' => urlencode($message), 'msgid' => urlencode($msgId)));
            
            // Вземаме съдържанието
            $url = $tpl->getContent();
            
            // Опитваме се да изпратим
            $ctx = stream_context_create(array('http' => array('timeout' => 5)));
            
            // Вземаме резултата
            $resStr = file_get_contents($url, 0, $ctx);
            
            // Ако има грешка - веднага маркираме в SMS Мениджъра
            $resArr = explode(' ', $resStr);
            
            // Ако няма грешки
            if ($resArr[0] == 'OK') {
                
                // Сетваме променливите
                $nRes['sendStatus'] = 'sended';
                $nRes['uid'] = $resArr[1];
                $nRes['msg'] = '|Успешно изпратен SMS';
            } else {
                
                // Сетваме променливите
                $nRes['sendStatus'] = 'sendError';
                $nRes['msg'] = '|Не може да се изпрати';
                
                if (isDebug()) {
                    $nRes['msg'] .= '|*.<br>' . $resStr;
                }
                
                self::logErr('Грешка при изпращане на SMS: ' . $resStr);
            }
        } else {
            
            // Ако не е дефиниран шаблона
            
            // Сетваме грешките
            $nRes['sendStatus'] = 'sendError';
            $nRes['msg'] = "|Липсва константа за URL' то";
            
            // Записваме в лога
            self::logAlert("Липсва константа за URL' то");
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
        $paramsArr = array();
        $paramsArr['utf8'] = netfinity_Setup::get('SUPPORT_UTF8');
        $paramsArr['maxStrLen'] = netfinity_Setup::get('MAX_STRING_LEN');
        $paramsArr['allowedUserNames'] = arr::make(netfinity_Setup::get('ALLOWED_USER_NAMES'), true);
        
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
        $uid = Request::get('id', 'int');
        $status = Request::get('status', 'varchar');
        $timestamp = Request::get('ts', 'varchar');
        $attempt = Request::get('attempt', 'int');
        
        // Ако не е получен успешно
        if ($status == 'delivered_mobile') {
            $status = 'received';
        } elseif (($status == 'not_delivered_mobile') || ($status == 'not_delivered')) {
            $status = 'receiveError';
        } else {
            $status = 'pending';
        }
        
        try {
            $classId = $this->getClassId();
            
            $timestamp = dt::mysql2timestamp($timestamp);
            
            // Обновяваме статуса на съобщението
            callcenter_SMS::update($classId, $uid, $status, $timestamp);
        } catch (core_exception_Expect $e) {
            reportException($e);
            self::logErr('Възникна грешка при обновяване на състоянието с msgid: ' . $uid . ' ' . $e->getMessage());
        }
        
        // Пращаме им очаквания отговор, без значение дали е възникнала грешка
        echo 'OK ' . $uid;
        shutdown();
    }
}
