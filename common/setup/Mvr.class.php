<?php

/**
 * Клас 'common_setup_Mvr'
 *
 * @category   Experta Framework
 * @package    common
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class common_setup_Mvr extends core_Mvc
{
    function act_Default()
    {
        $Mvr = cls::get('common_Mvr');
        
    	if (($handle = fopen(__DIR__ . "/csv/Mvr.csv", "r")) !== FALSE) {
            while (($csvRow = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rec->city    = $csvRow[0];
                $rec->account = $csvRow[1];
                
                // Ако има запис с това 'city'
                $rec->id = $Mvr->fetchField(array("#city = '[#1#]'", $rec->city), 'id'); /* escape data! */
                    
                $Mvr->save($rec);                
            }
            
            fclose($handle);
        }
        
        return new Redirect(array('common_Mvr'));
    }    
    
}