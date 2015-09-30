<?php


 /**
 * Интерфейс за документи, които ще се изпращат
 *
 * @category  bgerp
 * @package   email
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за документи, които ще се изпращат
 */
class email_SendIntf
{
    
    
    /**
     * Изпращане на документа
     */
    function send($rec, $options, $lg)
    {
        
        return $this->class->send($rec, $options, $lg);
    }
    
    
    /**
     * Връща инстанция на класа, в който са записани данните
     */
    function getModelClass()
    {
        
        return $this->class->getModelClass();
    }
}