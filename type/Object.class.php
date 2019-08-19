<?php


/**
 * Клас  'type_Object' - Структурни данни в MYSQL blob поле
 *
 *
 * @category  ef
 * @package   type
 *
 * @author    Milen Georgiev <milen@download.bg> и Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class type_Object extends type_Blob
{
    /**
     * Стойност по подразбиране
     */
    public $defaultValue = '';
    
    
    /**
     * Връща представяне подходящо за MySQL за структурни данни
     *
     * @param string $value
     *
     * @return string
     */
    public function toMysql($value, $db, $notNull, $defValue)
    {
        if ($value !== null) {
            $value = json_encode($value);
        }
        
        return parent::toMysql($value, $db, $notNull, $defValue);
    }
    
    
    /**
     * @see core_Type::fromMysql()
     *
     * @param string $value
     *
     * @return mixed
     */
    public function fromMysql($value)
    {
        if ($value !== null) {
            $value = @json_decode($value);
        }
        
        return parent::fromMysql($value);
    }
}
