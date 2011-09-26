<?php


/**
 * Клас 'drdata_EgnType' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    drdata
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class drdata_EgnType extends type_Varchar
{
    
    /**
     *  Колко символа е дълго полето в базата
     */
    var $dbFieldLen = 10;
    
    
    /**
     *  @todo Чака за документация...
     */
    function isValid($value)
    {
        if(!$value) return NULL;
        
        $value = trim($value);
        
        try {
            $Egn = new drdata_BulgarianEGN($value);
        } catch( Exception $e ) {
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
     *  @todo Чака за документация...
     */
    function toVerbal($value)
    {
        if(!$value) return NULL;
        
        try {
            $Egn = new drdata_BulgarianEGN($value);
        } catch( Exception $e ) {
            $err = $e->getMessage();
        }
        
        if($err) {
            $color = 'green';
            $type = 'ЛНЧ';
        } else {
            $color = 'black';
            $type = 'EGN';
        }
        
        return "<font color='{$color}'>" . tr($type) . " {$value}</font>";
    }
}