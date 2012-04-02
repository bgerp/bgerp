<?php



/**
 * Клас 'drdata_VatType' -
 *
 *
 * @category  vendors
 * @package   drdata
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class drdata_VatType extends type_Varchar
{
    
    
    /**
     * Колко символа е дълго полето в базата
     */
    var $dbFieldLen = 18;
    
    
    /**
     * Инициализиране на дължината
     */
    function init($params = array())
    {
        parent::init($params);
        setIfNot($this->params['size'], $this->dbFieldLen);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function isValid($value)
    {
        if(!$value) return NULL;
        
        $Vats = cls::get('drdata_Vats');
        
        $res = array();
        $res['value'] = strtoupper(trim($value));
        
        $status = $Vats->check($res['value']);
        
        if ($status == 'unknown') {
            $res['warning'] = $status;
        } elseif ($status != 'valid' && $status != 'not_vat' && $status != 'bulstat') {
            $res['error'] = $status;
        }
        
        if ((isset($res['error'])) || (isset($res['warning']))) {
            return $res;
        }
        
        return parent::isValid($value);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function toVerbal($value)
    {
        if(!$value) return NULL;
        
        $Vats = cls::get('drdata_Vats');
        $value = parent::escape($value);
        $status = $Vats->check($value);
        
        switch($status) {
            case 'unknown' : $color = "#339900"; break;
            case 'bulstat' : $color = "#000000"; break;
            case 'valid' : $color = "#000000"; break;
            case 'invalid' : $color = "#ff3300"; break;
            case 'syntax' : $color = "#990066"; break;
            case 'not_vat' : $color = "#3300ff"; break;
        }
        
        if($status == 'not_vat') {
            
            return "<font color='{$color}'>{$value}</font>";
        } else {
            
            return "<font color='{$color}'>{$value}</font>";
        }
    }
}