<?php



/**
 * Клас  'type_Blob' - Представя двоични данни
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
class type_Blob extends core_Type {
    
    
    /**
     * Стойност по подразбиране
     */
    var $defaultValue = '';
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        if(Mode::is('screenMode', 'narrow')) {
            setIfNot($attr['rows'], 5);
            setIfNot($attr['cols'], 20);
        } else {
            setIfNot($attr['rows'], 15);
            setIfNot($attr['cols'], 60);
        }
        
        return ht::createTextArea($name, $value, $attr);
    }
    
    
    /**
     * Връща текста за MySQL типа
     */
    function getMysqlAttr()
    {
        // Размера в байтове на полето
        $size = $this->params['size'] ? $this->params['size'] : $this->params[0];
        
        if(!$size) {
            $this->dbFieldType = "BLOB";
        } elseif($size <256) {
            $this->dbFieldType = "TINYBLOB";
        } elseif($size <65536) {
            $this->dbFieldType = "BLOB";
        } elseif($size <16777216) {
            $this->dbFieldType = "MEDIUMBLOB";
        } else {
            $this->dbFieldType = "LONGBLOB";
        }
        
        return parent::getMysqlAttr();
    }
    
    
    /**
     * Връща вербално представяне на стойността на двоичното поле
     */
    function toVerbal($value)
    {
        if(empty($value)) return NULL;
        
        setIfNot($rowLen, $this->params['rowLen'], 16);
        setIfNot($maxRows, $this->patams['maxRows'], 100);
        $len = min(strlen($value), $rowLen * $maxRows);
        
        $dbAttr = $this->getMysqlAttr();
        
        switch($dbAttr->dbFieldType) {
            case "TINYBLOB" : $offsetLen = 2; break;
            case "BLOB" : $offsetLen = 4; break;
            case "MEDIUMBLOB" : $offsetLen = 6; break;
            case "LONGBLOB" : $offsetLen = 8; break;
        }
        
        $res = new ET("<pre style='font-family:Courier New;'>[#ROWS#]</pre>");
        
        for($i = 0; $i<$len / $rowLen; $i++) {
            $offcet = sprintf("%0{$offsetLen}X", $i * $rowLen);
            $str = ''; $hex = '';
            
            for($j = 0; $j<16; $j++) {
                if($i * $rowLen + $j<$len) {
                    $c = $value{$i * $rowLen + $j};
                    
                    if(ord($c) >= 32 && ord($c) <= 127) {
                        $str .= htmlentities($c);
                    } else {
                        if(ord($c)<32) {
                            $str .= '<font color=grey>&copy;</font>';
                        } else {
                            $str .= '<font color=grey>&reg;</font>';
                        }
                    }
                    $hex .= sprintf("%02X", ord($c)) . '&nbsp;';
                } else {
                    $str .= ' ';
                    $hex .= '  &nbsp;';
                }
            }
            
            $res->append(new ET("[#1#]: [#2#] [#3#]\n", $offcet, $str, $hex), 'ROWS');
        }
        
        return $res;
    }
    
    
    /**
     * Връща представяне подходящо за MySQL на дълги двоични данни
     * По-точно това е дълго 16-тично число
     *
     * @param string $value
     * @return string
     */
    static function toMysql($value, $db, $notNull, $defValue)
    {
        if($value !== NULL) {
            $value = (string) $value;
            
            if($value) {
                $res = '0x' . bin2hex($value);
            } else {
                $res = "''";
            }
        } else {
            $res = parent::toMysql($value, $db, $notNull, $defValue);
        }
        
        return $res;
    }
}