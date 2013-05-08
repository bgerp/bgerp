<?php
abstract class schema_Migration
{
    /**
     * Дата и час на създаване на миграцията
     * 
     * @var string MySQL формат: Y-m-d H:i:s
     */
    public static $time;

    
    /**
     * Кога се прилага тази миграция: beforeSetup, afterSetup, ...
     * 
     * @var string
     */
    public static $when; 
    
    
    /**
     * Миграционна логика за изпълнение. Изпълнява се към момента, зададен от self::$when 
     * 
     * @return boolean
     */
    public static function apply()
    {
        
    }
}