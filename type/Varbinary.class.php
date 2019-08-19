<?php


/**
 * Клас  'type_Blob' - Представя двоични данни
 *
 *
 * @category  ef
 * @package   type
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class type_Varbinary extends type_Varchar
{
    /**
     * Стойност по подразбиране
     */
    public $defaultValue = '';
    

    /**
     * MySQL тип на полето в базата данни
     */
    public $dbFieldType = 'varbinary';
    
    
    /**
     * Дължина на полето в mySql таблица
     */
    public $dbFieldLen = 255;


    /**
     * Връща атрибутите на MySQL полето
     */
    public function getMysqlAttr()
    {
        $res = $this->_baseGetMysqlAttr();
        $res->size = floor(($res->size+1)/2);

        return $res;
    }


    /**
     * Връща вербално представяне на стойността на двоичното поле
     */
    public function toVerbal($value)
    {
        if (empty($value)) {
            
            return;
        }
        
        return $value;
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
 
        
        return bin2hex($value);
    }


    /**
     * Връща MySQL-ската стойност на стойността, така обезопасена,
     * че да може да участва в заявки
     */
    public function toMysql($value, $db, $notNull, $defValue)
    {
        if(strlen($value)) {
            $value = '0x' . $value;
        }
        
        return $value;
    }

}
