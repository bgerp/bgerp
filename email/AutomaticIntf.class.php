<?php


/**
 * Интерфейс на документ който може да бъде изпращан по електронна поща
 *
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за допълнителна обработка на имейлите
 */
class email_AutomaticIntf
{
    
    
    /**
     * Тежест на интерфейса
     */
    public $weight = 0;
    
    
    /**
     * Обработва имейла
     *
     * @param email_Mime  $mime
     * @param integer $accId
     * @param integer $uid
     *
     * @return string|null
     */
    public function process($mime, $accId, $uid)
    {
        
        return $this->class->process($mime, $accId, $uid);
    }
}
