<?php





/**
 * Клас 'type_Decimal' - Тип за рационални числа
 *
 *
 * @category  ef
 * @package   type
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_Decimal extends type_Double {
    
    
    /**
     * Тип на полето в mySql таблица
     */
    var $dbFieldType = 'decimal';
    
    
    
    /**
     * Параметър определящ максималната широчина на полето
     */
    var $maxFieldSize = 15;

 

    /**
     * Връща атрибутите на MySQL полето
     */
    public function getMysqlAttr()
    {
        $res = $this->_baseGetMysqlAttr();
        
        setIfNot($this->params['size'], $this->params[0], 10.4);
        
        $size = str_replace('.', ',', $this->params['size']);

        $res->size = $size;

        return $res;
    }


}
