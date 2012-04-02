<?php



/**
 * Интерфейс за изпращачите на SMS-и
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за изпращачите на SMS-и
 */
class bgerp_SMSIntf
{
    
    
    /**
     * Изпраща текстово съобщение
     */
    function send()
    {
        return $this->class->send();
    }
}