<?php


/**
 * Клас  'type_Bigint' - Тип за цели числа
 *
 *
 * @category  ef
 * @package   type
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_Bigint extends type_Int
{
    
    
    /**
     * MySQL тип на полето в базата данни
     */
    public $dbFieldType = 'bigint';
    
    
    /**
     * Дължина на полето в mySql таблица
     */
    public $dbFieldLen = '21';
    
    
    /**
     * Параметър определящ максималната широчина на полето
     */
    public $maxFieldSize = 21;
}
