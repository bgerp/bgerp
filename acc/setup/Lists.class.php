<?php



/**
 * Клас 'acc_setup_Lists'
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_setup_Lists
{
    /**
     * Зареждане на номенклатурите от CSV файл
     */
    static function loadData()
    {
        $file = "acc/setup/csv/Lists.csv";

        $mvc = cls::get('acc_Lists');

    	$fields = array( 
	    	0 => "num", 
	    	1 => "name", 
	    	2 => "regInterfaceId", 
	    	3 => "systemId",
	    	4 => "isDimensional");
    	
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields);
    	
        $res .= $cntObj->html;
        
        return $res;
    }

}
