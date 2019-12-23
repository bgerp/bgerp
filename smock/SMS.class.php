<?php


/**
 * Пращане на SMS за тестване
 *
 * @category  vendors
 * @package   smock
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class smock_SMS extends core_Manager
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
    public $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'admin';
    
    
    /**
     * Интерфейсния клас за изпращане на SMS
     */
    public $interfaces = 'callcenter_SentSMSIntf';
    
    
    public $title = 'Smock';
    
    
    public function description()
    {
        $this->FLD('number', 'varchar(32)', 'caption=Номер');
        $this->FLD('message', 'varchar', 'caption=Съобщение');
        $this->FLD('sender', 'varchar(32)', 'caption=Изпращач');
    }
    
    
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
        $number = self::prepareNumberStr($number);
        
        $rec = (object) array(
            'number' => $number,
            'message' => $message,
            'sender' => $sender,
        );
        
        $this->save($rec);
        
        $nRes = array();
        
        $nRes['sendStatus'] = 'sended';
        $nRes['uid'] = $rec->id;
        $nRes['msg'] = '|Успешно изпратен SMS';
        
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
        $paramsArr['utf8'] = true;
        $paramsArr['maxStrLen'] = 140;
        $paramsArr['allowedUserNames'] = false;
        
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
    protected function prepareNumberStr($number)
    {
        $number = drdata_PhoneType::getNumberStr($number, 0, '');
        
        return $number;
    }
}
