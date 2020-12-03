<?php


/**
 * Интерфейс за допълнителна обработка на сервизните инейли
 * 
 * @category  bgerp
 * @package   email
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за допълнителна обработка на сервизните инейли
 */
class email_ServiceRulesIntf
{
    
    
    /**
     * Обработва имейла
     *
     * @param email_Mime  $mime
     * @param integer $accId
     * @param integer $uid
     *
     * @return string|null
     */
    public function process($mime)
    {
        
        return $this->class->process($mime, $accId, $uid);
    }
}
