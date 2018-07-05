<?php


/**
 * Пращане на SMS' и чрез prosms
 *
 * @category  vendors
 * @package   prosms
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class prosms_SMS extends core_Manager
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
    
    
    
    public $title = 'proSMS';
    
    
    /**
     * Интерфейсен метод за изпращане на SMS' и
     *
     * @param string $number  - Номера на получателя
     * @param string $message - Текста на съобщението
     * @param string $sender  - От кого се изпраща съобщението
     *
     * @return array $nRes - Mасив с информация, дали е получено
     *               $res['sendStatus'] string - Статус на изпращането - received, sended, receiveError, sendError, pending
     *               $nRes['uid'] string - Уникалното id на съобщението
     *               $nRes['msg'] - Статуса
     */
    public function sendSMS($number, $message, $sender)
    {
        // Конфигурацията на модула
        $conf = core_Packs::getConfig('prosms');
        
        // Масива, който ще връщаме
        $nRes = array();
        
        // Ако константата за УРЛ-то е зададена
        if ($conf->PROSMS_URL != '') {
            
            // Вземаме шаблона
            $tpl = new ET($conf->PROSMS_URL);
            
            // Заместваме данните
            $tpl->placeArray(array('USER' => urlencode($conf->PROSMS_USER), 'PASS' => urlencode($conf->PROSMS_PASS), 'FROM' => urlencode($sender), 'ID' => $uid, 'PHONE' => urlencode($number), 'MESSAGE' => urlencode($message)));
            
            // Вземаме съдържанието
            $url = $tpl->getContent();
            
            // Опитваме се да изпратим
            $ctx = stream_context_create(array('http' => array('timeout' => 5)));
            
            // Вземаме резултата
            $res = file_get_contents($url, 0, $ctx);
            
            // Ако има грешки
            if ((int) $res != 0) {
                
                // Сетваме променливите
                $nRes['sendStatus'] = 'sendError';
                $nRes['msg'] = '|Не може да се изпрати';
            } else {
                
                // Ако няма грешки
                
                // Опитваме се да генерираме уникален номер
                $uid = static::getUid();
                
                // Сетваме променливите
                $nRes['sendStatus'] = 'sended';
                $nRes['msg'] = '|Успешно изпратен SMS';
                $nRes['uid'] = $uid;
            }
        } else {
            
            // Ако не е дефиниран шаблона
            
            // Сетваме грешките
            $nRes['sendStatus'] = 'sendError';
            $nRes['msg'] = "|Липсва константа за URL' то";
            
            // Записваме в лога
            self::logWarning("Липсва константа за URL' то");
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
     * Интерфейсен метод, който връща масив с настройките за услугата
     *
     * @return array $paramsArr
     *               enum $paramsArr['utf8'] - no|yes - Дали поддържа UTF-8
     *               integer $paramsArr['maxStrLen'] - Максималната дължина на стринга
     *               string $paramsArr['allowedUserNames'] - Масив с позволените имена за изпращач
     */
    public function getParams()
    {
        $conf = core_Packs::getConfig('prosms');
        $paramsArr = array();
        $paramsArr['utf8'] = $conf->PROSMS_SUPPORT_UTF8;
        $paramsArr['maxStrLen'] = $conf->PROSMS_MAX_STRING_LEN;
        $paramsArr['allowedUserNames'] = arr::make($conf->PROSMS_ALLOWED_USER_NAMES, true);
        
        return $paramsArr;
    }
    
    
    /**
     * Връща уникално id
     */
    public static function getUid()
    {
        return $uid = str::getRand('aaaaaa') . 'prosms';
    }
    
    
    /**
     * Отбелязване на статуса на съобщенито
     * Извиква се от външната програма след промяна на статуса на SMS'а
     */
    public function act_Delivery()
    {
        // Вземаме променливите
        $uid = request::get('idd', 'varchar');
        $status = request::get('status', 'varchar');
        $code = request::get('code', 'varchar');
        
        // Ако не е получен успешно
        if ((int) $code !== 0) {
            $status = 'receiveError';
        } else {
            $status = 'received';
        }
        
        try {
            $classId = $this->getClassId();
            
            // Обновяваме статуса на съобщението
            callcenter_SMS::update($classId, $uid, $status);
        } catch (core_exception_Expect $e) {
            reportException($e);
            self::logErr("Възникна грешка при обновяване на състоянието с idd '${uid}' - " . $e->getMessage());
        }
    }
}
