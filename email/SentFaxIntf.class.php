<?php


/**
 * Интерфейс
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за изпращане на факсове
 */
class email_SentFaxIntf
{
    /**
     * Метод за изпращане на факсове
     */
    public function sendFax($rec, $fax)
    {
        return $this->class->sendFax($rec, $fax);
    }
}
