<?php

/**
 * Клас  'type_Text' - Тип за дълъг текст
 *
 *
 * @category   Experta Framework
 * @package    type
 * @author     Milen Georgiev
 * @copyright  2006-2010 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class type_Text extends core_Type {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $dbFieldType = 'text';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $dbFieldLen = 65536;
    
    
    /**
     *  @todo Чака за документация...
     */
    function renderInput_($name, $value="", $attr = array())
    {
        if(Mode::is('screenMode', 'narrow')) {
            setIfnot($attr['rows'], $this->params['rows'], 5);
            setIfnot($attr['cols'], $this->params['cols'], 20);
        } else {
            setIfnot($attr['rows'], $this->params['rows'], 10);
            setIfnot($attr['cols'], $this->params['cols'], 30);
        }
        
        return ht::createTextArea($name, $value, $attr);
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function getMysqlAttr()
    {
        // Умножаваме по 2 размера, заради UTF-8, който представя кирилицата с 2 байта
        $size = 2*($this->params['size']?$this->params['size']:$this->params[0]);
        
        if(!$size) {
            $this->dbFieldType = "text";
        } elseif( $size <256 ) {
            $this->dbFieldType = "tinytext";
        } elseif( $size <65536 ) {
            $this->dbFieldType = "text";
        } elseif( $size <16777216 ) {
            $this->dbFieldType = "mediumtext";
        } else {
            $this->dbFieldType = "longtext";
        }
        
        return parent::getMysqlAttr();
    }
    
    
    /**
     * Връща стойноста на текста, без изменения, защото се
     * предполага, че той е в HTML формат
     */
    function toVerbal($value)
    {
        return str_replace(array('<', "\n"), array('&lt;','<br>'), $value) ;
    }
}