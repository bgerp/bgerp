<?php

/**
 * Клас 'common_setup_Units'
 *
 * @category   Experta Framework
 * @package    common
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class common_setup_Units extends core_Mvc
{
    function act_Default()
    {
        $Units = cls::get('common_Units');
        
    	if (($handle = fopen(__DIR__ . "/csv/Units.csv", "r")) !== FALSE) {
            while (($csvRow = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rec->name          = $csvRow[0];
                $rec->shortName     = $csvRow[1];
                $rec->baseUnitId    = $Units->fetchField(array("#name = '[#1#]'", $csvRow[2]), 'id'); /* escape data! */
                $rec->baseUnitRatio = $csvRow[3];                
                
                // Ако има запис с този 'name'
                $rec->id = $Units->fetchField(array("#name = '[#1#]'", $rec->name), 'id'); /* escape data! */
                    
                $Units->save($rec);                
            }
            
            fclose($handle);
        }
        
        return new Redirect(array('common_Units'));
    }    
    
}