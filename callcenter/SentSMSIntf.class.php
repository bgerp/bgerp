<?php

 /**
 * Интерфейс за изпращане на SMS' и
 *
 * @category  bgerp
 * @package   callcenter
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class callcenter_SentSMSIntf
{
    
    
    /**
     * Метод за изпращане на SMS' и
     * 
     * @param string $number - Номера на получателя
     * @param string $message - Текста на съобщението
     * @param string $sender - От кого се изпраща съобщението
     * 
     * @return array $res - Mасив с информация, дали е получено
     * $res['sendStatus'] string - Статус на изпращането - received, sended, receiveError, sendError, pending
     * $res['uid'] string - Уникалното id на съобщението
     * $res['msg'] - Статуса
     */
    function sendSMS($number, $message, $sender)
    {
        return $this->class->sendFax($number, $message, $sender);
    }
    
    
    /**
     * Интерфейсен метод, който връща масив с настройките за услугата
     * 
     * @return array $paramsArr
     * enum $paramsArr['utf8'] - no|yes - Дали поддържа UTF-8
     * integer $paramsArr['maxStrLen'] - Максималната дължина на стринга
     * string $paramsArr['allowedUserNames'] - Масив с позволените имена за изпращач
     */
    function getParams()
    {
        return $this->class->getParams();
    }
    
    
    /**
     * Отбелязване на статуса на съобщенито
     * Извиква се от външната програма след промяна на статуса на SMS'а
     */
    function act_Delivery()
    {
        return $this->class->act_Delivery();
    }
}
