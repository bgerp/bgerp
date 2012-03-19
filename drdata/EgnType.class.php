<?php



/**
 * Клас 'drdata_EgnType' -
 *
 *
 * @category  all
 * @package   drdata
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class drdata_EgnType extends type_Varchar
{
    
    
    /**
     * Колко символа е дълго полето в базата
     */
    var $dbFieldLen = 10;
    
    
    /**
     * @todo Чака за документация...
     */
    function isValid($value)
    {
        if(!$value) return NULL;
        
        $value = trim($value);
        
        try {
            $Egn = new drdata_BulgarianEGN($value);
        } catch(Exception $e) {
            $err = $e->getMessage();
        }
        
        $res = array();
        $res['value'] = $value;
        
        if($err) {
            $res['error'] = $err;
            $Lnc = new drdata_BulgarianLNC();
            
            if ($Lnc->isLnc($value) === TRUE) {
                unset($res['error']);
            } else {
                $res['error'] .= $Lnc->isLnc($value);
            }
        }
        
        return $res;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function toVerbal($value)
    {
        if(!$value) return NULL;
        
        try {
            $Egn = new drdata_BulgarianEGN($value);
        } catch(Exception $e) {
            $err = $e->getMessage();
        }
        
        if($err) {
            $color = 'green';
            $type = 'ЛНЧ';
        } else {
            $color = 'black';
            $type = 'ЕГН';
        }
        
        return "<font color='{$color}'>" . tr($type) . " {$value}</font>";
    }
}