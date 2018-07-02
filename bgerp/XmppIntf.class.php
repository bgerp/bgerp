<?php


/**
 * Интерфейс за изпращачите по XMPP.
 *
 *
 * @category  bgerp
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за изпращачите на XMPP
 */
class bgerp_XmppIntf
{
    /**
     * Изпраща текстово съобщение.
     */
    public function send()
    {
        return $this->class->send();
    }
}
