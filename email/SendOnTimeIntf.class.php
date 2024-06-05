<?php


/**
 * Интерфейс за отложено изпращане на имейли
 *
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за отложено изпращане на имейли
 */
class email_SendOnTimeIntf
{
    /**
     * Връща тялото на имейла генериран от документа
     *
     * @params mixed $data - данните на документа
     * @params int $id - id на документа
     */
    public function sendOnTime($data, $id)
    {

        return $this->class->sendOnTime($data, $id);
    }
}
