<?php

/**
 * Клас  'type_Varchar' - Тип за символни последователности (стринг)
 *
 *
 * @category   Experta Framework
 * @package    type
 * @author     Milen Georgiev
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class type_Varchar extends core_Type {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $dbFieldType = 'varchar';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $dbFieldLen = 255;
    
    
    /**
     *  @todo Чака за документация...
     */
    function renderInput_($name, $value="", $attr = array())
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