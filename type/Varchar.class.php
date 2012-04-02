<?php



/**
 * Клас  'type_Varchar' - Тип за символни последователности (стринг)
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
class type_Varchar extends core_Type {
    
    
    /**
     * MySQL тип на полето в базата данни
     */
    var $dbFieldType = 'varchar';
    
    
    /**
     * Дължина на полето в mySql таблица
     */
    var $dbFieldLen = 255;
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        if($this->params[0]) {
            $attr['maxlength'] = $this->params[0];
        }
        
        if($this->params['size']) {
            $attr['size'] = $this->params['size'];
        }
        
        if($this->inputType) {
            $attr['type'] = $this->inputType;
        }
        
        $tpl = $this->createInput($name, $value, $attr);
        
        return $tpl;
    }
}