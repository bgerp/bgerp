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
     * @param array $params - Масив с класа и функцията, която ще се извика в act_Delivery
     * @params['class'] - Класа
     * @params['function'] - Функцията, която да се стартира от съответния клас
     * 
     * @return array $res - Mасив с информация, дали е получено
     * $res['sended'] boolean - Дали е изпратен или не
     * $res['uid'] string - Уникалното id на съобщението
     * $res['msg'] - Статуса
     */
    function sendSMS($number, $message, $sender, $params)
    {
        return $this->class->sendFax($number, $message, $sender);
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
