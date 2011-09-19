<?php

/**
 * Клас 'cat_setup_UoM'
 *
 * @category   Experta Framework
 * @package    cat
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class cat_setup_UoM extends core_Mvc
{
    function setup()
    {
        $Units = cls::get('cat_UoM');
        
    	if (($handle = fopen(__DIR__ . "/csv/UoM.csv", "r")) !== FALSE) {
            while (($csvRow = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rec->name          = $csvRow[0];
                $rec->shortName     = $csvRow[1];
                $rec->baseUnitId    = $Units->fetchField(array("#name = '[#1#]'", $csvRow[2]), 'id'); /* escape data! */
                $rec->baseUnitRatio = $csvRow[3];                
                $rec->createdBy     = -1; // Записите направени от системния потребител (-1) не могат да се редактират

                // Ако има запис с този 'name'
                $rec->id = $Units->fetchField(array("#name = '[#1#]'", $rec->name), 'id'); /* escape data! */
                    
                $Units->save($rec);                
            }
            
            fclose($handle);
        }
    }    
  
}