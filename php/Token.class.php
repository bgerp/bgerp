<?php

/**
 *  class php_Token
 *  @author Milen Georgiev
 */
class php_Token
{
    
    
    /**
     *  @todo Чака за документация...
     */
    var $type;
    
    
    /**
     *  @todo Чака за документация...
     */
    var $str;
    
    
    /**
     *  @todo Чака за документация...
     */
    var $insertAfter = array();
    
    
    /**
     *  @todo Чака за документация...
     */
    var $insertBefore = array();
    
    
    /**
     *  @todo Чака за документация...
     */
    var $delete = FALSE;
    
    
    /**
     *  @todo Чака за документация...
     */
    function php_Token($type, $str)
    {
        $this->type = $type;
        $this->str = $str;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function insertAfter($type, $str)
    {
        $add = new php_Token($type, $str);
        
        $this->insertAfter[] = $add;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function insertBefore($type, $str)
    {
        $add = new php_Token($type, $str);
        
        $this->insertBefore[] = $add;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function delete()
    {
        $this->delete = TRUE;
    }
    
    
    /**
     *
     */
    function mustHaveDocComment()
    {
        if(in_array($this->type, array(T_CLASS, T_FUNCTION, T_CONST, T_VAR))) {
            
            return TRUE;
        }
    }
}