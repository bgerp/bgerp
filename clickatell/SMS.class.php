<?php


/**
 * Пращане на SMS' и от clickatell
 *
 * @category  vendors
 * @package   clickatell
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class clickatell_SMS extends core_Manager
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
    
    
    
    public $title = 'Clickatell';
    
    
    /**
     * Интерфейсен метод за изпращане на SMS' и
     *
     * @param string $number  - Номера на получателя
     * @param string $message - Текста на съобщението
     * @param string $sender  - От кого се изпраща съобщението
     *
     * @see callcenter_SentSMSIntf
     *
     * @return array $nRes - Масив с информация, дали е получено
     *               $res['sendStatus'] string - Статус на изпращането - received, sended, receiveError, sendError, pending
     *               $nRes['uid'] string - Уникалното id на съобщението
     *               $nRes['msg'] - Статуса
     */
    public function sendSMS($number, $message, $sender)
    {
        // Конфигурацията на модула
        $conf = core_Packs::getConfig('clickatell');
        
        // Масива, който ще връщаме
        $nRes = array();
        
        // Ако константата за УРЛ-то е зададена
        if ($conf->CLICKATELL_URL != '') {
            
            // Вземаме шаблона
            $tpl = new ET($conf->CLICKATELL_URL);

            // Заместваме данните
            $tpl->placeArray(array('FROM' => urlencode($sender), 'PHONE' => urlencode($number), 'MESSAGE' => urlencode($message), 'APIID' => $conf->CLICKATELL_APIID, 'USERNAME' => $conf->CLICKATELL_USERNAME, 'PASSWORD' => $conf->CLICKATELL_PASSWORD));

            // Вземаме съдържанието
            $url = $tpl->getContent();
            
            // Изпращаме съобщението
            $ret = file($url);
            
            // Резултата, който се е върнал
            $retRes = $ret[0];
            
            // Друг начин за изпращане
//            // Опитваме се да изпратим
//            $ctx = stream_context_create(array('http' => array('timeout' => 5)));
//
//            // Вземаме резултата
//            $retRes = file_get_contents($url, 0, $ctx);

            // Разделяме стринга
            $sendStatusArr = explode(':', $retRes);
     
            // Ако изпращането е било успешно
            if ($sendStatusArr[0] == 'ID') {
                
                // Сетваме променливите
                $nRes['sendStatus'] = 'sended';
                $nRes['uid'] = trim($sendStatusArr[1]);
                $nRes['msg'] = '|Успешно изпратен SMS';
            } else {
                
                // Сетваме променливите
                $nRes['sendStatus'] = 'sendError';
                $nRes['msg'] = '|Не може да се изпрати';
                
                if (isDebug()) {
                    $nRes['msg'] .= '|*.<br>' . $retRes;
                }
                
                self::logErr('Грешка при изпращане на SMS: ' . $retRes);
            }
        } else {
            
            // Ако не е дефиниран шаблона
            
            // Сетваме грешките
            $nRes['sendStatus'] = 'sendError';
            $nRes['msg'] = "|Липсва константа за URL' то";
            
            // Записваме в лога
            self::logErr("Липсва константа за URL' то");
        }
        
        return $nRes;
    }
    
    
    /**
     * Интерфейсен метод, който връща масив с настройките за услугата
     *
     * @see callcenter_SentSMSIntf
     *
     * @return array $paramsArr
     *               enum $paramsArr['utf8'] - no|yes - Дали поддържа UTF-8
     *               integer $paramsArr['maxStrLen'] - Максималната дължина на стринга
     *               string $paramsArr['allowedUserNames'] - Масив с позволените имена за изпращач
     */
    public function getParams()
    {
        $conf = core_Packs::getConfig('clickatell');
        $paramsArr = array();
        $paramsArr['utf8'] = $conf->CLIKATELL_SUPPORT_UTF8;
        $paramsArr['maxStrLen'] = $conf->CLIKATELL_MAX_STRING_LEN;
        $paramsArr['allowedUserNames'] = arr::make($conf->CLIKATELL_ALLOWED_USER_NAMES, true);
        
        return $paramsArr;
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
        $uid = Request::get('apiMsgId', 'varchar');
        $code = Request::get('status', 'varchar');
        $timestamp = Request::get('timestamp', 'int');
        
        // Други променливи, които се пращат
        $apiId = Request::get('api_id', 'varchar');
        $cliMsgId = Request::get('cliMsgId', 'varchar');
        $to = Request::get('to', 'varchar');
        $from = Request::get('from', 'varchar');
        $charge = Request::get('charge', 'varchar');
        
        // Ако е получен успешно
        if ($code == '004') {
            
            // Статуса
            $status = 'received';
        } else {
            
            // Грешка при получаване
            $status = 'receiveError';
        }
        
        // Ако има зададен клас и фунцкия
        try {
            $classId = $this->getClassId();
            
            // Обновяваме статуса на съобщението
            callcenter_SMS::update($classId, $uid, $status, $timestamp);
        } catch (core_exception_Expect $e) {
            $errMsg = 'Възникна грешка при обновяване на състоянието с apiMsgId';
            self::logErr("{$errMsg}: " . $uid);
        }
    }
    
    
    /**
     * Екшън за проверка на връзката с услугата
     */
    public function act_Check()
    {
        // Очакваме да има роля admin
        expect(haveRole('admin'));
        
        // Конфигурацията на модула
        $conf = core_Packs::getConfig('clickatell');
        
        // Ако константата за УРЛ-то е зададена
        expect($conf->CLICKATELL_CHECK_URL != '');
        
        // Вземаме шаблона
        $tpl = new ET($conf->CLICKATELL_CHECK_URL);

        // Заместваме данните
        $tpl->placeArray(array('APIID' => $conf->CLICKATELL_APIID, 'USERNAME' => $conf->CLICKATELL_USERNAME, 'PASSWORD' => $conf->CLICKATELL_PASSWORD));

        // Вземаме съдържанието
        $url = $tpl->getContent();
        
        try {
            
            // Изпращаме заявката
            $ret = @file($url);
        } catch (core_exception_Expect $e) {
        }
        
        // Вземаме резултата
        $sess = explode(':', $ret[0]);
        
        // Очакваме да е ОК
        expect($sess[0] == 'OK', 'Не може да се ауторизира', $ret);
        
        // Връщаме съобщението
        return tr('Clickatell работи коректно');
    }
}
