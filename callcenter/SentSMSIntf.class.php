<?php


/**
 * Интерфейс за изпращане на SMS' и
 *
 * @category  bgerp
 * @package   callcenter
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class callcenter_SentSMSIntf
{
    /**
     * Метод за изпращане на SMS' и
     *
     * @param string $number  - Номера на получателя
     * @param string $message - Текста на съобщението
     * @param string $sender  - От кого се изпраща съобщението
     *
     * @return array $res - Масив с информация, дали е получено
     *               o $res['sendStatus'] string - Статус на изпращането - received, sended, receiveError, sendError, pending
     *               o $res['uid'] string - Уникалното id на съобщението
     *               o $res['msg'] - Статуса
     */
    public function sendSMS($number, $message, $sender)
    {
        return $this->class->sendSMS($number, $message, $sender);
    }
    
    
    /**
     * Интерфейсен метод, който връща масив с настройките за услугата
     *
     * @return array $paramsArr
     *               o enum $paramsArr['utf8'] - no|yes - Дали поддържа UTF-8
     *               o integer $paramsArr['maxStrLen'] - Максималната дължина на стринга
     *               o string $paramsArr['allowedUserNames'] - Масив с позволените имена за изпращач
     */
    public function getParams()
    {
        return $this->class->getParams();
    }
    
    
    /**
     * Връща статуса на съобщението от съоветната услуга
     *
     * @param string $uid
     *
     * @return NULL|string
     *                     o received - Получен
     *                     o sended - Изпратен
     *                     o receiveError - Грешка при получаване
     *                     o sendError - Грешка при изпращане
     *                     o pending - Чакащо
     */
    public function getStatus($uid)
    {
        return $this->class->getStatus();
    }
}
