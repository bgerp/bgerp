<?php


/**
 * Интерфейс за допълнителна обработка на сервизните инейли
 * 
 * @category  bgerp
 * @package   email
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за допълнителна обработка на сервизните инейли
 */
class email_ServiceRulesIntf extends embed_DriverIntf
{
    
    
    /**
     * Обработва имейла
     *
     * @param email_Mime  $mime
     * @param stdClass  $serviceRec
     *
     * @return string|array|false|null
     *                  o string - писмото е обработено и не се записва като входящ имейл
     *                  o array  - записва се като входящ имейл с подадените опции
     *                  o null   - без действие, правилото се счита за съвпаднало
     *                  o false  - правилото не се прилага за писмото
     */
    public function process($mime, $serviceRec)
    {
        
        return $this->class->process($mime, $serviceRec);
    }
}
